<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Circle;
use App\Models\User;

class CirclePolicy
{
    /**
     * عرض قائمة الحلقات.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('center_manager')) {
            return filled($user->center_id)
                && $user->hasPermissionTo('circles.view', 'web');
        }

        if ($user->hasRole('teacher')) {
            return $user->hasAnyPermission([
                'circles.view',
                'circles.view_own',
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
            return $user->hasPermissionTo('circles.view', 'web');
        }

        return $user->hasAnyPermission([
            'circles.view',
            'circles.view_own',
        ]);
    }

    /**
     * إنشاء حلقة.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('center_manager')
            && filled($user->center_id)
            && $user->hasPermissionTo('circles.create', 'web');
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
            && $user->hasPermissionTo('circles.update', 'web');
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
            && $user->hasPermissionTo('circles.delete', 'web');
    }

    /**
     * حذف عدة حلقات.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasRole('super_admin')
            || (
                $user->hasRole('center_manager')
                && filled($user->center_id)
                && $user->hasPermissionTo('circles.delete', 'web')
            );
    }

    /**
     * التحقق من أن الحلقة ضمن نطاق المستخدم.
     */
    private function canAccessCircle(
        User $user,
        Circle $circle
    ): bool {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('center_manager')) {
            return filled($user->center_id)
                && (int) $circle->center_id === (int) $user->center_id;
        }

        if ($user->hasRole('teacher')) {
            return $circle->teacher()
                ->where('user_id', $user->getKey())
                ->exists();
        }

        return false;
    }
}
