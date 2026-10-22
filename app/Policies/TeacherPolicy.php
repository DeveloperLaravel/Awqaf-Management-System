<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Center;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeacherPolicy
{
    use HandlesAuthorization;

    /**
     * هل يملك المستخدم صلاحية محددة؟
     */
    private function hasPermission(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission, 'web');
    }

    /**
     * المراكز التي يستطيع المستخدم إدارتها.
     */
    private function allowedCenterIds(User $user): array
    {
        if ($user->hasRole('super_admin')) {
            return $this->hasPermission($user, 'teachers.manage_all')
                ? Center::query()->pluck('id')->map(
                    fn ($id): int => (int) $id
                )->all()
                : [];
        }

        if (! $user->center_id) {
            return [];
        }

        $allowed = [(int) $user->center_id];
        $pending = $allowed;

        // جمع الفروع المتداخلة التابعة للمركز.
        while ($pending !== []) {
            $children = Center::query()
                ->whereIn('parent_center_id', $pending)
                ->whereNotIn('id', $allowed)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            if ($children === []) {
                break;
            }

            $allowed = array_values(array_unique(
                array_merge($allowed, $children)
            ));

            $pending = $children;
        }

        return $allowed;
    }

    /**
     * التأكد من ارتباط المعلم بمركز داخل النطاق.
     */
    private function inAllowedScope(
        User $user,
        Teacher $teacher
    ): bool {
        $allowedIds = $this->allowedCenterIds($user);

        if ($allowedIds === []) {
            return false;
        }

        return $teacher->centers()
            ->whereIn('centers.id', $allowedIds)
            ->exists();
    }

    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'teachers.view');
    }

    public function view(User $user, Teacher $teacher): bool
    {
        return $this->hasPermission($user, 'teachers.view')
            && $this->inAllowedScope($user, $teacher);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'teachers.create')
            && $this->allowedCenterIds($user) !== [];
    }

    public function update(User $user, Teacher $teacher): bool
    {
        return $this->hasPermission($user, 'teachers.update')
            && $this->inAllowedScope($user, $teacher);
    }

    public function delete(User $user, Teacher $teacher): bool
    {
        return $this->hasPermission($user, 'teachers.delete')
            && $this->inAllowedScope($user, $teacher);
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'teachers.delete');
    }

    public function restore(User $user, Teacher $teacher): bool
    {
        return $this->hasPermission($user, 'teachers.restore')
            && $this->inAllowedScope($user, $teacher);
    }

    public function restoreAny(User $user): bool
    {
        return $this->hasPermission($user, 'teachers.restore');
    }

    public function forceDelete(User $user, Teacher $teacher): bool
    {
        return $this->hasPermission($user, 'teachers.force_delete')
            && $this->inAllowedScope($user, $teacher);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'teachers.force_delete');
    }
}
