<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\GuardianStudent;
use App\Models\User;
use App\Services\CenterHierarchy;

class GuardianStudentPolicy
{
    /**
     * عرض قائمة العلاقات.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:GuardianStudent');
    }

    /**
     * عرض علاقة محددة.
     */
    public function view(User $user, GuardianStudent $record): bool
    {
        return $user->can('View:GuardianStudent')
            && $this->canAccessCenter($user, $record);
    }

    /**
     * إنشاء علاقة جديدة.
     */
    public function create(User $user): bool
    {
        return $user->can('Create:GuardianStudent');
    }

    /**
     * تعديل علاقة.
     */
    public function update(User $user, GuardianStudent $record): bool
    {
        return $user->can('Update:GuardianStudent')
            && $this->canAccessCenter($user, $record);
    }

    /**
     * حذف علاقة.
     */
    public function delete(User $user, GuardianStudent $record): bool
    {
        return $user->can('Delete:GuardianStudent')
            && $this->canAccessCenter($user, $record);
    }

    /**
     * حذف مجموعة علاقات.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('Delete:GuardianStudent');
    }

    /**
     * التحقق من أن المستخدم يستطيع الوصول إلى المركز.
     */
    protected function canAccessCenter(
        User $user,
        GuardianStudent $record
    ): bool {
        $centerIds = app(CenterHierarchy::class)
            ->accessibleIdsForUser($user);

        return in_array(
            (int) $record->center_id,
            array_map('intval', $centerIds),
            true
        );
    }
}