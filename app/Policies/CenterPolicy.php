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
            || $user->hasPermissionTo('ViewAll:Center', 'web');
    }

    public function view(User $user, Center $center): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if (
            ! $user->hasPermissionTo('View:Center', 'web')
            && ! $user->hasPermissionTo('ViewAny:Center', 'web')
        ) {
            return false;
        }

        return app(CenterHierarchy::class)->canUserAccess(
            $user,
            (int) $center->id,
        );
    }

    /**
     * إنشاء مركز رئيسي أو فرع.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('super_admin')) {
            return $user->hasPermissionTo('Create:Center', 'web');
        }

        return $user->hasPermissionTo('CreateChild:Center', 'web')
            && app(CenterHierarchy::class)
                ->directlyManagedCenters($user)
                ->isNotEmpty();
    }

    /**
     * تعديل مركز ضمن النطاق المسموح.
     */
    public function update(User $user, Center $center): bool
    {
        if (! $this->canAccess($user, $center)) {
            return false;
        }

        return $user->hasPermissionTo('Update:Center', 'web');
    }

    /**
     * حذف مركز لا يحتوي على فروع.
     */
    public function delete(User $user, Center $center): bool
    {
        if (! $this->canAccess($user, $center)) {
            return false;
        }

        if (! $user->hasPermissionTo('Delete:Center', 'web')) {
            return false;
        }

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

    /**
 * موافقة مدير المركز الأب على إنشاء فرع جديد.
 */
/**
 * موافقة مدير المركز الأب على إنشاء فرع جديد.
 */
/**
 * موافقة مدير المركز الأب على إنشاء فرع جديد.
 */
public function approveChild(User $user, Center $center): bool
{
    if (
        $center->parent_center_id === null
        || $center->approval_status !== 'pending_parent_approval'
    ) {
        return false;
    }

    if (! $user->hasPermissionTo('ApproveChild:Center', 'web')) {
        return false;
    }

    return app(CenterHierarchy::class)
        ->directlyManagedCenters($user)
        ->contains('id', (int) $center->parent_center_id);
}

/**
 * الاعتماد النهائي من المدير العام.
 */
public function approveFinal(User $user, Center $center): bool
{
    return $user->hasRole('super_admin')
        && $user->hasPermissionTo('ApproveFinal:Center', 'web')
        && $center->approval_status === 'pending_super_admin_approval'
        && $center->parent_approved_at !== null;
}
    /**
     * التحقق من الوصول الهرمي.
     */
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
