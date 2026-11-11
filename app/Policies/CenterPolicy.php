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

        $allowed = $user->hasPermissionTo('View:Center', 'web')
            || $user->hasPermissionTo('ViewAny:Center', 'web')
            || $user->hasPermissionTo('ViewAll:Center', 'web');

        return $allowed && $this->canAccess($user, $center);
    }

    /**
     * السماح بفتح صفحة إنشاء مركز.
     *
     * super_admin: إنشاء مركز رئيسي.
     * center_admin / center_manager: إنشاء فرع.
     */
    public function create(User $user): bool
    {
        // إنشاء المراكز الرئيسية للمدير العام فقط.
        if ($user->hasRole('super_admin')) {
            return $user->hasPermissionTo('Create:Center', 'web');
        }

        // إنشاء الفروع للمديرين الذين لديهم مراكز يديرونها مباشرة.
        if (! $user->hasAnyRole([
            'center_admin',
            'center_manager',
        ])) {
            return false;
        }

        return $user->hasPermissionTo('CreateChild:Center', 'web')
            && app(CenterHierarchy::class)
                ->directlyManagedCenters($user)
                ->isNotEmpty();
    }

    /**
     * إنشاء فرع تحت مركز محدد.
     */
    public function createChild(
        User $user,
        Center $parent
    ): bool {
        // المدير العام لا ينشئ فروعاً.
        if ($user->hasRole('super_admin')) {
            return false;
        }

        if (! $user->hasAnyRole([
            'center_admin',
            'center_manager',
        ])) {
            return false;
        }

        if (! $user->hasPermissionTo('CreateChild:Center', 'web')) {
            return false;
        }

        // يجب أن يكون المركز الأب تحت إدارة المستخدم مباشرة.
        return app(CenterHierarchy::class)
            ->canCreateChildUnder($user, $parent);
    }

    public function update(User $user, Center $center): bool
    {
        return $user->hasPermissionTo('Update:Center', 'web')
            && $this->canAccess($user, $center);
    }

    public function delete(User $user, Center $center): bool
    {
        if (! $user->hasPermissionTo('Delete:Center', 'web')) {
            return false;
        }

        if (! $this->canAccess($user, $center)) {
            return false;
        }

        // لا نحذف مركزاً لديه فروع.
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
     * موافقة مدير المركز الأب على طلب الفرع.
     */
    public function approveChild(
        User $user,
        Center $center
    ): bool {
        if (
            $center->parent_center_id === null
            || $center->approval_status !== 'pending_parent_approval'
        ) {
            return false;
        }

        if (! $user->hasPermissionTo('ApproveChild:Center', 'web')) {
            return false;
        }

        return app(CenterHierarchy::class)->managesDirectly(
            $user,
            (int) $center->parent_center_id
        );
    }

    /**
     * الاعتماد النهائي من المدير العام.
     */
    public function approveFinal(
        User $user,
        Center $center
    ): bool {
        return $user->hasRole('super_admin')
            && $user->hasPermissionTo('ApproveFinal:Center', 'web')
            && $center->approval_status === 'pending_super_admin_approval'
            && $center->parent_approved_at !== null;
    }

    /**
     * التحقق من إمكانية الوصول إلى المركز.
     */
    private function canAccess(
        User $user,
        Center $center
    ): bool {
        return app(CenterHierarchy::class)->canUserAccess(
            $user,
            (int) $center->id
        );
    }
}
