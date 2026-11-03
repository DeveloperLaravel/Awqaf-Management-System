<?php

declare(strict_types=1);

namespace App\Filament\Resources\Guardians\Pages;

use App\Filament\Resources\Guardians\GuardianResource;
use App\Models\Center;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateGuardian extends CreateRecord
{
    protected static string $resource = GuardianResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        $centerId = (int) ($data['center_id'] ?? 0);

        // المراكز التي يحق للمستخدم الوصول إليها.
        if ($user->hasRole('super_admin')) {
            $accessibleIds = Center::query()->pluck('id')->all();
        } else {
            $accessibleIds = app(CenterHierarchy::class)
                ->accessibleIdsForUser($user);
        }

        // التحقق من أن المركز أو الفرع مسموح للمستخدم.
        $center = Center::query()
            ->whereKey($centerId)
            ->whereIn('id', $accessibleIds)
            ->where('is_active', true)
            ->where('approval_status', 'active')
            ->first();

        if (! $center) {
            throw ValidationException::withMessages([
                'center_id' => 'المركز أو الفرع المحدد غير متاح أو لا تملك صلاحية الوصول إليه.',
            ]);
        }

        $data['center_id'] = $center->id;

        return $data;
    }
}