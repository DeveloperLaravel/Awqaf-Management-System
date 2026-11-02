<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Center;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CenterHierarchy;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeacherPolicy
{
    use HandlesAuthorization;

    /**
     * التحقق من الصلاحية المسجلة في قاعدة البيانات.
     */
    private function hasPermission(User $user, string $permission): bool
    {
        return $user->getAllPermissions()
            ->contains(
                fn ($item): bool =>
                    $item->name === $permission
                    && $item->guard_name === 'web'
            );
    }

    /**
     * المراكز التي يستطيع المستخدم الوصول إليها.
     */
    private function allowedCenterIds(User $user): array
    {
        // المدير العام يستطيع الوصول إلى جميع المراكز.
        // الصلاحيات الفعلية تتحكم بها دوال Policy الأخرى.
        if ($user->hasRole('super_admin')) {
            return Center::query()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        return collect(
            app(CenterHierarchy::class)->accessibleIdsForUser($user)
        )
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * التأكد من ارتباط المعلم بمركز داخل نطاق المستخدم.
     */
    private function inAllowedScope(
        User $user,
        Teacher $teacher
    ): bool {
        $allowedIds = $this->allowedCenterIds($user);

        if ($allowedIds === []) {
            return false;
        }

        return $teacher->activeCenters()
            ->whereIn('centers.id', $allowedIds)
            ->exists();
    }

    /**
     * عرض قائمة المعلمين.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'ViewAny:Teacher')
            && $this->allowedCenterIds($user) !== [];
    }

    /**
     * عرض معلم محدد.
     */
    public function view(User $user, Teacher $teacher): bool
    {
        return $this->hasPermission($user, 'View:Teacher')
            && $this->inAllowedScope($user, $teacher);
    }

    /**
     * إنشاء معلم.
     */
    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'Create:Teacher')
            && $this->allowedCenterIds($user) !== [];
    }

    /**
     * تعديل معلم.
     */
    public function update(User $user, Teacher $teacher): bool
    {
        return $this->hasPermission($user, 'Update:Teacher')
            && $this->inAllowedScope($user, $teacher);
    }

    /**
     * حذف معلم.
     */
    public function delete(User $user, Teacher $teacher): bool
    {
        return $this->hasPermission($user, 'Delete:Teacher')
            && $this->inAllowedScope($user, $teacher);
    }

    /**
     * حذف عدة معلمين.
     */
    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'DeleteAny:Teacher')
            && $this->allowedCenterIds($user) !== [];
    }

    /**
     * استعادة معلم.
     */
    public function restore(User $user, Teacher $teacher): bool
    {
        return $this->hasPermission($user, 'Restore:Teacher')
            && $this->inAllowedScope($user, $teacher);
    }

    /**
     * استعادة عدة معلمين.
     */
    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'RestoreAny:Teacher')
            && $this->allowedCenterIds($user) !== [];
    }

    /**
     * حذف نهائي لمعلم.
     */
    public function forceDelete(User $user, Teacher $teacher): bool
    {
        return $this->hasPermission($user, 'ForceDelete:Teacher')
            && $this->inAllowedScope($user, $teacher);
    }

    /**
     * حذف نهائي لعدة معلمين.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'ForceDeleteAny:Teacher')
            && $this->allowedCenterIds($user) !== [];
    }
}
