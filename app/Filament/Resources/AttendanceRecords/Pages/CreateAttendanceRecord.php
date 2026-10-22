<?php

declare(strict_types=1);

namespace App\Filament\Resources\AttendanceRecords\Pages;

use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Models\Circle;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAttendanceRecord extends CreateRecord
{
    protected static string $resource = AttendanceRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = request()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $circle = Circle::query()->find($data['circle_id'] ?? null);

        if (! $circle || ! $circle->is_active) {
            throw ValidationException::withMessages([
                'data.circle_id' => 'الحلقة غير موجودة أو غير نشطة.',
            ]);
        }

        $teacher = Teacher::query()
            ->where('user_id', $user->id)
            ->first();

        if ($user->hasRole('super_admin')) {
            // المدير العام يمكنه التسجيل وفق صلاحياته.
        } elseif ($teacher) {
            if ((int) $circle->teacher_id !== (int) $teacher->id) {
                abort(403, 'لا يمكنك تسجيل الحضور إلا لحلقاتك.');
            }
        } elseif (! app(CenterHierarchy::class)->canUserAccess(
            $user,
            (int) $circle->center_id
        )) {
            abort(403, 'لا يمكنك تسجيل الحضور لهذا المركز.');
        }

        $studentId = $data['student_id'] ?? null;

        $studentIsInCircle = $circle->students()
            ->where('students.id', $studentId)
            ->wherePivotNull('left_at')
            ->exists();

        if (! $studentIsInCircle) {
            throw ValidationException::withMessages([
                'data.student_id' => 'الطالب غير مسجل حاليًا في هذه الحلقة.',
            ]);
        }

        $date = $data['attendance_date'] ?? null;

        if (! $date) {
            throw ValidationException::withMessages([
                'data.attendance_date' => 'يرجى تحديد تاريخ الحضور.',
            ]);
        }

        $alreadyExists = AttendanceRecord::query()
            ->where('student_id', $studentId)
            ->where('circle_id', $circle->id)
            ->whereDate('attendance_date', $date)
            ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'data.student_id' => 'تم تسجيل حضور هذا الطالب في الحلقة بهذا التاريخ مسبقًا.',
            ]);
        }

        $data['center_id'] = $circle->center_id;
        $data['teacher_id'] = $circle->teacher_id;

        return $data;
    }
}
