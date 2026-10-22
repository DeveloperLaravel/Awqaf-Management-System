<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Guardian;
use App\Models\User;
use App\Services\CenterHierarchy;

class GuardianPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('guardians.view');
    }

    public function view(User $user, Guardian $guardian): bool
    {
        return $user->can('guardians.view')
            && $this->canAccessCenter($user, $guardian);
    }

    public function create(User $user): bool
    {
        return $user->can('guardians.create');
    }

    public function update(User $user, Guardian $guardian): bool
    {
        return $user->can('guardians.update')
            && $this->canAccessCenter($user, $guardian);
    }

    public function delete(User $user, Guardian $guardian): bool
    {
        return $user->can('guardians.delete')
            && $this->canAccessCenter($user, $guardian);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('guardians.delete');
    }

    private function canAccessCenter(
        User $user,
        Guardian $guardian
    ): bool {
        if ($guardian->center_id === null) {
            return false;
        }

        return app(CenterHierarchy::class)->canUserAccess(
            $user,
            (int) $guardian->center_id
        );
    }
}
