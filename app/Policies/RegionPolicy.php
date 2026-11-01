<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Center;
use App\Models\Region;
use App\Models\User;

class RegionPolicy
{
    /**
     * الحصول على المنطقة الفعلية للمستخدم.
     *
     * المركز هو المصدر الأساسي إذا كان المستخدم مرتبطاً به.
     */
    private function getUserRegionId(User $user): ?int
    {
        if ($user->center_id !== null) {
            $regionId = Center::query()
                ->whereKey($user->center_id)
                ->value('region_id');

            return $regionId !== null
                ? (int) $regionId
                : null;
        }

        return $user->region_id !== null
            ? (int) $user->region_id
            : null;
    }

    /**
     * التحقق من أن المنطقة ضمن نطاق المستخدم.
     */
    private function canAccessRegion(
        User $user,
        Region $region
    ): bool {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $userRegionId = $this->getUserRegionId($user);

        return $userRegionId !== null
            && $userRegionId === (int) $region->getKey();
    }

    /**
     * مشاهدة قائمة المناطق.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin')
            || $user->hasPermissionTo('View:Region', 'web');
    }

    /**
     * مشاهدة منطقة محددة.
     */
    public function view(User $user, Region $region): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $this->canAccessRegion($user, $region);
    }

    /**
     * إنشاء منطقة.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('super_admin')
            || $user->hasPermissionTo('Create:Region', 'web');
    }

    /**
     * تعديل منطقة.
     */
    public function update(User $user, Region $region): bool
    {
        if (! $this->canAccessRegion($user, $region)) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->hasPermissionTo('Update:Region', 'web');
    }

    /**
     * حذف منطقة.
     */
    public function delete(User $user, Region $region): bool
    {
        if (! $this->canAccessRegion($user, $region)) {
            return false;
        }
            // لا نحذف منطقة مرتبطة بمراكز.
    if ($region->centers()->exists()) {
        return false;
    }

        return $user->hasRole('super_admin')
            || $user->hasPermissionTo('regions.delete', 'web');
    }

    /**
     * تعطيل الحذف الجماعي.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }

    /**
     * العمليات المتقدمة غير المفعلة.
     */
    public function restore(User $user, Region $region): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Region $region): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, Region $region): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
