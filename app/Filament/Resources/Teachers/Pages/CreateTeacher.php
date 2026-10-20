<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\TeacherResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! Gate::allows('centers.view_all')) {
            $centerId = Auth::user()?->center_id;

            abort_if($centerId === null, 403);

            $data['center_id'] = $centerId;
        }

        return $data;
    }
}
