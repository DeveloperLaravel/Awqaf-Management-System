<?php

namespace App\Filament\Resources\Guardians\Pages;

use App\Filament\Resources\Guardians\GuardianResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGuardian extends CreateRecord
{
    protected static string $resource = GuardianResource::class;
protected function mutateFormDataBeforeCreate(array $data): array
{
    $user = \Illuminate\Support\Facades\Auth::user();

    if (! $user) {
        abort(403);
    }

    // المستخدم المخوّل بالوصول لجميع المراكز
    if (\Illuminate\Support\Facades\Gate::allows('centers.view_all')) {
        $centerId = $data['center_id'] ?? null;

        if (
            ! $centerId ||
            ! \App\Models\Center::query()
                ->whereKey($centerId)
                ->exists()
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'center_id' => 'يرجى اختيار مركز صحيح.',
            ]);
        }

        $data['center_id'] = $centerId;
    } else {
        // المستخدم العادي يُربط بمركزه فقط
        if (! $user->center_id) {
            abort(403, 'حسابك غير مرتبط بمركز.');
        }

        $data['center_id'] = $user->center_id;
    }

    return $data;
}
}

