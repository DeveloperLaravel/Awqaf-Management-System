<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Center;
use App\Models\User;
use App\Services\CenterHierarchy;

class CenterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin')
            || $user->hasPermissionTo('ViewAny:Center', 'web')
            || $user->hasPermissionTo('centers.view', 'web')
            || $user->hasPermissionTo('centers.view_all', 'web');
    }

    public function view(User $user, Center $center): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $hasPermission =
            $user->hasPermissionTo('View:Center', 'web')
            || $user->hasPermissionTo('centers.view', 'web')
            || $user->hasPermissionTo('centers.view_all', 'web');

        if (! $hasPermission) {
            return false;
        }

        return app(CenterHierarchy::class)->canUserAccess(
            $user,
            (int) $center->id,
        );
    }

    /**
     * المدير العام ينشئ المراكز الرئيسية والفروع.
     * مدير المركز ينشئ الفروع فقط.
     */
    public function create(User $user): bool
    {
        // المدير العام ينشئ المراكز الرئيسية
    if ($user->hasRole('super_admin')) {
        return $user->hasPermissionTo('Create:Center', 'web')
            || $user->hasPermissionTo('centers.create', 'web');
    }

         // مدير المركز أو الفرع ينشئ فرعاً تحت مركز يديره مباشرة
    return $user->hasPermissionTo('centers.create_child', 'web')
        && app(CenterHierarchy::class)
            ->directlyManagedCenters($user)
            ->isNotEmpty();
    }

    public function update(User $user, Center $center): bool
    {
        if (! $this->canAccess($user, $center)) {
            return false;
        }

        return $user->hasPermissionTo('Update:Center', 'web')
            || $user->hasPermissionTo('centers.update', 'web');
    }

    public function delete(User $user, Center $center): bool
    {
        if (! $this->canAccess($user, $center)) {
            return false;
        }

        $hasPermission =
            $user->hasPermissionTo('Delete:Center', 'web')
            || $user->hasPermissionTo('centers.delete', 'web');

        if (! $hasPermission) {
            return false;
        }

        // منع حذف مركز لديه فروع.
        return ! $center->childCenters()->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Center $center): bool
    {
        return false;
    }

    public function forceDelete(User $user, Center $center): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, Center $center): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }

    private function canAccess(User $user, Center $center): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return app(CenterHierarchy::class)->canUserAccess(
            $user,
            (int) $center->id,
        );
    }
}
