<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Circle;
use App\Models\User;
use App\Services\CenterHierarchy;
use Illuminate\Database\Eloquent\Builder;

class CirclePolicy
{
    /**
     * التحقق من امتلاك الصلاحية.
     */
    private function hasPermission(
        User $user,
        string $permission
    ): bool {
        return $user->getAllPermissions()
            ->contains(
                fn ($item) => $item->name === $permission
            );
    }

    /**
     * هل المستخدم مدير؟
     */
    private function isManager(User $user): bool
    {
        return $user->hasAnyRole([
            'super_admin',
            'center_admin',
            'center_manager',
        ]);
    }

    /**
     * هل المركز ضمن نطاق المستخدم؟
     */
    private function canAccessCenter(
        User $user,
        int $centerId
    ): bool {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $centerIds = app(CenterHierarchy::class)
            ->accessibleIdsForUser($user);

        return collect($centerIds)
            ->contains(fn ($id) => (int) $id === $centerId);
    }

    /**
     * هل المعلم هو صاحب الحلقة وله ارتباط نشط بالمركز؟
     */
    private function isAssignedTeacher(
        User $user,
        Circle $circle
    ): bool {
        $teacher = $circle->teacher;

        if (
            ! $teacher ||
            (int) $teacher->user_id !== (int) $user->id ||
            ! $teacher->is_active
        ) {
            return false;
        }

        return $teacher->centers()
            ->where('centers.id', $circle->center_id)
            ->where('center_teacher.is_active', true)
            ->exists();
    }

    /**
     * هل الطالب مسجل في الحلقة؟
     */
    private function isEnrolledStudent(
        User $user,
        Circle $circle
    ): bool {
        return $circle->students()
            ->where('students.user_id', $user->id)
            ->where('students.is_active', true)
            ->wherePivotNull('left_at')
            ->wherePivot('center_id', $circle->center_id)
            ->exists();
    }

    /**
     * عرض قائمة الحلقات.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($this->isManager($user)) {
            return $this->hasPermission(
                $user,
                'ViewAny:Circle'
            );
        }

        if (
            $user->hasRole('teacher') ||
            $user->hasRole('student')
        ) {
            return $this->hasPermission(
                $user,
                'ViewAny:Circle'
            );
        }

        return false;
    }

    /**
     * عرض حلقة واحدة.
     */
    public function view(
        User $user,
        Circle $circle
    ): bool {
        if ($user->hasRole('super_admin')) {
            return $this->hasPermission(
                $user,
                'View:Circle'
            );
        }

        if ($this->isManager($user)) {
            return $this->hasPermission(
                $user,
                'View:Circle'
            ) && $this->canAccessCenter(
                $user,
                (int) $circle->center_id
            );
        }

        if ($user->hasRole('teacher')) {
            return $this->hasPermission(
                $user,
                'View:Circle'
            ) && $this->isAssignedTeacher($user, $circle);
        }

        if ($user->hasRole('student')) {
            return $this->hasPermission(
                $user,
                'View:Circle'
            ) && $this->isEnrolledStudent($user, $circle);
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

        return $this->isManager($user)
            && $this->hasPermission(
                $user,
                'Create:Circle'
            );
    }

    /**
     * تعديل حلقة.
     */
    public function update(
        User $user,
        Circle $circle
    ): bool {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($this->isManager($user)) {
            return $this->hasPermission(
                $user,
                'Update:Circle'
            ) && $this->canAccessCenter(
                $user,
                (int) $circle->center_id
            );
        }

        if ($user->hasRole('teacher')) {
            return $this->hasPermission(
                $user,
                'Update:Circle'
            ) && $this->isAssignedTeacher($user, $circle);
        }

        return false;
    }

    /**
     * حذف حلقة.
     */
    public function delete(
        User $user,
        Circle $circle
    ): bool {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $this->isManager($user)
            && $this->hasPermission(
                $user,
                'Delete:Circle'
            )
            && $this->canAccessCenter(
                $user,
                (int) $circle->center_id
            );
    }
}
