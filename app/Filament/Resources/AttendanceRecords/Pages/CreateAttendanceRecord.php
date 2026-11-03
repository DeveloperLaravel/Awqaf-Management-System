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

    $circle = Circle::query()
        ->with('center')
        ->find($data['circle_id'] ?? null);

    if (
        ! $circle ||
        ! $circle->is_active ||
        ! $circle->center ||
        ! $circle->center->is_active ||
        $circle->center->approval_status !== 'active'
    ) {
        throw ValidationException::withMessages([
            'data.circle_id' => 'الحلقة أو المركز غير موجود أو غير نشط.',
        ]);
    }

    // التحقق من صلاحية الوصول إلى الحلقة.
    $teacher = Teacher::query()
        ->where('user_id', $user->id)
        ->first();

    if (! $user->hasRole('super_admin')) {
        if ($teacher) {
            if ((int) $circle->teacher_id !== (int) $teacher->id) {
                abort(403, 'لا يمكنك تسجيل الحضور إلا لحلقاتك.');
            }

            $teacherBelongsToCenter = $teacher->centers()
                ->where('centers.id', $circle->center_id)
                ->wherePivot('is_active', true)
                ->exists();

            if (! $teacherBelongsToCenter) {
                abort(403, 'المعلم غير مرتبط بهذا المركز.');
            }
        } elseif (! app(CenterHierarchy::class)->canUserAccess(
            $user,
            (int) $circle->center_id
        )) {
            abort(403, 'لا يمكنك تسجيل الحضور لهذا المركز.');
        }
    }

    // التحقق من الطالب.
    $studentId = $data['student_id'] ?? null;

    $student = Student::query()
        ->whereKey($studentId)
        ->where('center_id', $circle->center_id)
        ->first();

    if (! $student) {
        throw ValidationException::withMessages([
            'data.student_id' => 'الطالب غير تابع للمركز المحدد.',
        ]);
    }

    // التحقق من تسجيل الطالب في الحلقة.
    $studentIsInCircle = $circle->students()
        ->where('students.id', $student->id)
        ->wherePivotNull('left_at')
        ->exists();

    if (! $studentIsInCircle) {
        throw ValidationException::withMessages([
            'data.student_id' => 'الطالب غير مسجل حالياً في هذه الحلقة.',
        ]);
    }

    // التحقق من التاريخ.
    $date = $data['attendance_date'] ?? null;

    if (! $date) {
        throw ValidationException::withMessages([
            'data.attendance_date' => 'يرجى تحديد تاريخ الحضور.',
        ]);
    }

    // منع تسجيل الحضور مرتين للطالب نفسه في الحلقة والتاريخ نفسه.
    $alreadyExists = AttendanceRecord::query()
        ->where('center_id', $circle->center_id)
        ->where('student_id', $student->id)
        ->where('circle_id', $circle->id)
        ->whereDate('attendance_date', $date)
        ->exists();

    if ($alreadyExists) {
        throw ValidationException::withMessages([
            'data.student_id' => 'تم تسجيل حضور هذا الطالب في هذه الحلقة بهذا التاريخ مسبقاً.',
        ]);
    }

    // تحديد البيانات من قاعدة البيانات وليس من مدخلات المستخدم.
    $data['center_id'] = $circle->center_id;
    $data['teacher_id'] = $circle->teacher_id;

    return $data;
}
}
