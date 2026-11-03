<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentAssessments\Pages;

use App\Filament\Resources\StudentAssessments\StudentAssessmentResource;
use App\Models\Circle;
use App\Models\Student;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditStudentAssessment extends EditRecord
{
    protected static string $resource = StudentAssessmentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $allowedCenters = StudentAssessmentResource::availableCenters();

        if (! array_key_exists($data['center_id'] ?? '', $allowedCenters)) {
            throw ValidationException::withMessages([
                'center_id' => 'ليس لديك صلاحية التعديل في هذا المركز.',
            ]);
        }

        $studentExists = Student::query()
            ->whereKey($data['student_id'] ?? null)
            ->where('center_id', $data['center_id'])
            ->exists();

        if (! $studentExists) {
            throw ValidationException::withMessages([
                'student_id' => 'الطالب لا يتبع المركز المحدد.',
            ]);
        }

        $circleExists = Circle::query()
            ->whereKey($data['circle_id'] ?? null)
            ->where('center_id', $data['center_id'])
            ->exists();

        if (! $circleExists) {
            throw ValidationException::withMessages([
                'circle_id' => 'الحلقة لا تتبع المركز المحدد.',
            ]);
        }

        // لا نغير صاحب التقييم الأصلي عند التعديل.
        unset($data['assessed_by']);

        return $data;
    }
}
