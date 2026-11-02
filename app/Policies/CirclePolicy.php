<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Circle;
use App\Models\User;
use App\Services\CenterHierarchy;

class CirclePolicy
{
    /**
     * التحقق من امتلاك إحدى الصلاحيات.
     */
 private function hasAnyPermission(
    User $user,
    array $permissions
): bool {
    $grantedPermissions = $user->getAllPermissions()
        ->where('guard_name', 'web')
        ->pluck('name');

    return $grantedPermissions->intersect($permissions)->isNotEmpty();
}
    /**
     * عرض قائمة الحلقات.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('center_manager')) {
            return $this->hasAnyPermission($user, [
                'circles.view',
                'ViewAny:Circle',
            ]);
        }

        if ($user->hasRole('teacher')) {
            return $this->hasAnyPermission($user, [
                'circles.view',
                'circles.view_own',
                'ViewAny:Circle',
            ]);
        }

        return false;
    }

    /**
     * عرض حلقة محددة.
     */
    public function view(User $user, Circle $circle): bool
    {
        if (! $this->canAccessCircle($user, $circle)) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('center_manager')) {
            return $this->hasAnyPermission($user, [
                'circles.view',
                'View:Circle',
                'ViewAny:Circle',
            ]);
        }

        if ($user->hasRole('teacher')) {
            return $this->hasAnyPermission($user, [
                'circles.view',
                'circles.view_own',
                'View:Circle',
                'ViewAny:Circle',
            ]);
        }

        return false;
    }

    /**
     * إنشاء حلقة.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $centerIds = app(CenterHierarchy::class)
        ->accessibleIdsForUser($user);

    return $user->hasRole('center_manager')
        && collect($centerIds)->isNotEmpty()
        && $this->hasAnyPermission($user, [
            'circles.create',
            'Create:Circle',
        ]);
    }

    /**
     * تعديل حلقة.
     */
    public function update(User $user, Circle $circle): bool
    {
        if (! $this->canAccessCircle($user, $circle)) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('center_manager')
            && $this->hasAnyPermission($user, [
                'circles.update',
                'Update:Circle',
            ]);
    }

    /**
     * حذف حلقة.
     */
    public function delete(User $user, Circle $circle): bool
    {
        if (! $this->canAccessCircle($user, $circle)) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('center_manager')
            && $this->hasAnyPermission($user, [
                'circles.delete',
                'Delete:Circle',
            ]);
    }

    /**
     * حذف عدة حلقات.
     */
    public function deleteAny(User $user): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('center_manager')
            && $this->hasAnyPermission($user, [
                'circles.delete',
                'Delete:Circle',
            ]);
    }

    /**
     * التحقق من نطاق وصول المستخدم للحلقة.
     */
    private function canAccessCircle(
        User $user,
        Circle $circle
    ): bool {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('center_manager')) {
            return app(CenterHierarchy::class)->canUserAccess(
                $user,
                (int) $circle->center_id
            );
        }

        if ($user->hasRole('teacher')) {
            return $circle->teacher()
                ->where('user_id', $user->getKey())
                ->exists();
        }

        return false;
    }
}
