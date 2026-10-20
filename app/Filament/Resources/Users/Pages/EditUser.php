<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = request()->user();
        $record = $this->getRecord();

        if ($user === null || ! UserResource::canEdit($record)) {
            abort(403);
        }

        if ($record->name === 'super_admin') {
            abort(403);
        }

        if (! $user->hasRole('super_admin')) {
            if ($user->center_id === null) {
                abort(403);
            }

            $data['center_id'] = $user->center_id;
        }

        if (filled($data['password'] ?? null)) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return $data;
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        abort_unless(
            UserResource::canEdit($this->getRecord()),
            403,
        );
    }
}
