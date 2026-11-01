<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Services\CenterHierarchy;

class UserPolicy
{
    /**
     * هل يستطيع المستخدم الوصول إلى مركز المستخدم المستهدف؟
     */
    private function canAccessUser(User $authUser, User $user): bool
    {
        if ($user->center_id === null) {
            return false;
        }

        $centerIds = app(CenterHierarchy::class)
            ->accessibleIdsForUser($authUser);

        return in_array(
            (int) $user->center_id,
            array_map('intval', $centerIds),
            true,
        );
    }

    /**
     * المدير العام يرى جميع المستخدمين.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->hasRole('super_admin')
            || $authUser->can('ViewAny:User');
    }

    /**
     * مشاهدة مستخدم محدد.
     */
    public function view(User $authUser, User $user): bool
    {
        // حساب المدير العام لا يظهر للمستخدمين العاديين.
        if ($user->hasRole('super_admin')) {
            return $authUser->hasRole('super_admin');
        }

        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return $authUser->can('View:User')
            && $this->canAccessUser($authUser, $user);
    }

    /**
     * إنشاء مستخدم.
     */
    public function create(User $authUser): bool
    {
        return $authUser->hasRole('super_admin')
            || $authUser->can('Create:User');
    }

    /**
     * تعديل مستخدم.
     */
    public function update(User $authUser, User $user): bool
    {
        // منع تعديل أي حساب يحمل دور المدير العام.
        if ($user->hasRole('super_admin')) {
            return false;
        }

        // منع تعديل الحساب الشخصي من هذا المورد.
        if ($authUser->is($user)) {
            return false;
        }

        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return $authUser->can('Update:User')
            && $this->canAccessUser($authUser, $user);
    }

    /**
     * حذف مستخدم.
     */
    public function delete(User $authUser, User $user): bool
    {
        // حماية حساب المدير العام.
        if ($user->hasRole('super_admin')) {
            return false;
        }

        // منع حذف الحساب الحالي.
        if ($authUser->is($user)) {
            return false;
        }

        if ($authUser->hasRole('super_admin')) {
            return true;
        }

        return $authUser->can('Delete:User')
            && $this->canAccessUser($authUser, $user);
    }

    /**
     * الحذف الجماعي.
     *
     * لا نسمح به حالياً؛ لأن كل سجل يحتاج فحصاً مستقلاً.
     */
    public function deleteAny(User $authUser): bool
    {
        return false;
    }
}
