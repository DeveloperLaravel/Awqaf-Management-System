<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\TeacherResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['center_id'])) {
            abort(422, 'يجب اختيار مركز واحد على الأقل.');
        }

        $data['center_id'] = (int) $data['center_id'];

        return $data;
    }
}