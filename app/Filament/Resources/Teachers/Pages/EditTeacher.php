<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\TeacherResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class EditTeacher extends EditRecord
{
    protected static string $resource = TeacherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
      protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! Gate::allows('centers.view_all')) {
            $centerId = Auth::user()?->center_id;

            abort_if($centerId === null, 403);

            $data['center_id'] = $centerId;
        }

        return $data;
    }
}
