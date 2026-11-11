<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuardianStudents\Pages;

use App\Filament\Resources\GuardianStudents\GuardianStudentResource;
use App\Models\Guardian;
use App\Models\Student;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditGuardianStudent extends EditRecord
{
    protected static string $resource = GuardianStudentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        $centerId = (int) ($data['center_id'] ?? 0);
        $studentId = (int) ($data['student_id'] ?? 0);
        $guardianId = (int) ($data['guardian_id'] ?? 0);

        if ($centerId !== (int) $record->center_id) {
            throw ValidationException::withMessages([
                'data.center_id' => 'لا يمكن نقل العلاقة إلى مركز آخر.',
            ]);
        }

        if (! Student::query()
            ->whereKey($studentId)
            ->where('center_id', $centerId)
            ->exists()) {
            throw ValidationException::withMessages([
                'data.student_id' => 'الطالب لا يتبع المركز المحدد.',
            ]);
        }

        if (! Guardian::query()
            ->whereKey($guardianId)
            ->where('center_id', $centerId)
            ->where('is_active', true)
            ->exists()) {
            throw ValidationException::withMessages([
                'data.guardian_id' => 'ولي الأمر غير موجود أو غير نشط.',
            ]);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}