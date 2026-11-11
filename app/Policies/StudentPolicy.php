<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use App\Support\StudentCenterAccess;

class StudentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    private function isCenterManager(User $user): bool
    {
        return $user->hasRole('center_manager');
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return $user->hasPermissionTo($permission, 'web');
    }

    public function viewAny(User $user): bool
    {
        return $this->isCenterManager($user)
            && StudentCenterAccess::branchIdsFor($user) !== []
            && $this->hasPermission($user, 'ViewAny:Student');
    }

    public function view(User $user, Student $student): bool
    {
        return $this->isCenterManager($user)
            && StudentCenterAccess::canAccessBranch($user, $student->center_id)
            && $this->hasPermission($user, 'View:Student');
    }

    public function create(User $user): bool
    {
        return $this->isCenterManager($user)
            && StudentCenterAccess::branchIdsFor($user) !== []
            && $this->hasPermission($user, 'Create:Student');
    }

    public function update(User $user, Student $student): bool
    {
        return $this->isCenterManager($user)
            && StudentCenterAccess::canAccessBranch($user, $student->center_id)
            && $this->hasPermission($user, 'Update:Student');
    }

    public function delete(User $user, Student $student): bool
    {
        return $this->isCenterManager($user)
            && StudentCenterAccess::canAccessBranch($user, $student->center_id)
            && $this->hasPermission($user, 'Delete:Student');
    }

    public function deleteAny(User $user): bool
    {
        return $this->isCenterManager($user)
            && StudentCenterAccess::branchIdsFor($user) !== []
            && $this->hasPermission($user, 'Delete:Student');
    }

    public function restore(User $user, Student $student): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Student $student): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}