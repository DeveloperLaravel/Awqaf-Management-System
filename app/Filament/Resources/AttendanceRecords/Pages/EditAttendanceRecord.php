<?php

declare(strict_types=1);

namespace App\Filament\Resources\AttendanceRecords\Pages;

use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Models\Circle;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditAttendanceRecord extends EditRecord
{
    protected static string $resource = AttendanceRecordResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = request()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        /** @var AttendanceRecord $record */
        $record = $this->record;

        $circle = Circle::query()->find($data['circle_id'] ?? null);

        if (! $circle || ! $circle->is_active) {
            throw ValidationException::withMessages([
                'data.circle_id' => 'الحلقة غير موجودة أو غير نشطة.',
            ]);
        }

        if (! $user->hasRole('super_admin')) {
            $teacher = Teacher::query()
                ->where('user_id', $user->id)
                ->first();

            if ($teacher) {
                if ((int) $circle->teacher_id !== (int) $teacher->id) {
                    abort(403, 'لا يمكنك تعديل حضور حلقة ليست من حلقاتك.');
                }
            } elseif (! app(CenterHierarchy::class)->canUserAccess(
                $user,
                (int) $circle->center_id
            )) {
                abort(403, 'لا يمكنك تعديل حضور هذا المركز.');
            }
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

        $duplicate = AttendanceRecord::query()
            ->where('student_id', $studentId)
            ->where('circle_id', $circle->id)
            ->whereDate('attendance_date', $date)
            ->where('id', '!=', $record->getKey())
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'data.student_id' => 'يوجد سجل حضور لهذا الطالب في الحلقة والتاريخ نفسيهما.',
            ]);
        }

        $data['center_id'] = $circle->center_id;
        $data['teacher_id'] = $circle->teacher_id;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف')
                ->visible(
                    fn (): bool =>
                        AttendanceRecordResource::canDelete($this->record)
                ),
        ];
    }
}
