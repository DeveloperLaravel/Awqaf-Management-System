<?php

declare(strict_types=1);

namespace App\Filament\Resources\CircleStudents\Pages;

use App\Filament\Resources\CircleStudents\CircleStudentResource;
use App\Models\CircleStudent;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditCircleStudent extends EditRecord
{
    protected static string $resource = CircleStudentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var CircleStudent $record */
        $record = $this->record;

        if (! in_array(
            (int) $record->center_id,
            CircleStudentResource::accessibleCenterIds(),
            true
        )) {
            throw ValidationException::withMessages([
                'data.center_id' => 'ليس لديك صلاحية تعديل هذا التسجيل.',
            ]);
        }

        $joinedAt = $data['joined_at'] ?? null;
        $leftAt = $data['left_at'] ?? null;

        if ($joinedAt && $leftAt && $leftAt < $joinedAt) {
            throw ValidationException::withMessages([
                'data.left_at' => 'تاريخ المغادرة لا يمكن أن يسبق تاريخ الانضمام.',
            ]);
        }

        // إذا أعيد فتح التسجيل، نتحقق من عدم وجود تسجيل نشط آخر.
        if ($leftAt === null) {
            $anotherActiveEnrollment = CircleStudent::query()
                ->where('student_id', $record->student_id)
                ->whereNull('left_at')
                ->whereKeyNot($record->getKey())
                ->exists();

            if ($anotherActiveEnrollment) {
                throw ValidationException::withMessages([
                    'data.left_at' => 'لا يمكن إعادة تفعيل التسجيل؛ الطالب لديه تسجيل نشط آخر.',
                ]);
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            // لا نحذف التسجيلات حفاظاً على السجل التاريخي.
        ];
    }
}
