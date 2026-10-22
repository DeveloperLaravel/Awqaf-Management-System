<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\Circle;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CenterHierarchy;

class AttendanceRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasPermission($user, 'ViewAny:AttendanceRecord')
            || $this->hasPermission($user, 'attendance.view');
    }

    public function view(User $user, AttendanceRecord $record): bool
    {
        return (
            $this->hasPermission($user, 'View:AttendanceRecord')
            || $this->hasPermission($user, 'attendance.view')
        ) && $this->canAccessRecord($user, $record);
    }

    public function create(User $user): bool
    {
        return $this->hasPermission($user, 'Create:AttendanceRecord')
            || $this->hasPermission($user, 'attendance.create');
    }

    public function update(User $user, AttendanceRecord $record): bool
    {
        return (
            $this->hasPermission($user, 'Update:AttendanceRecord')
            || $this->hasPermission($user, 'attendance.update')
        ) && $this->canAccessRecord($user, $record);
    }

    public function delete(User $user, AttendanceRecord $record): bool
    {
        return (
            $this->hasPermission($user, 'Delete:AttendanceRecord')
            || $this->hasPermission($user, 'attendance.delete')
        ) && $this->canAccessRecord($user, $record);
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasPermission($user, 'DeleteAny:AttendanceRecord');
    }

    private function hasPermission(User $user, string $permission): bool
    {
        // نستخدم can() لتجنب رمي استثناء إذا لم تكن الصلاحية معرفة.
        return $user->can($permission);
    }

    private function canAccessRecord(
        User $user,
        AttendanceRecord $record
    ): bool {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $circle = Circle::query()
            ->whereKey($record->circle_id)
            ->where('center_id', $record->center_id)
            ->first();

        if (! $circle) {
            return false;
        }

        // المعلم يصل فقط إلى الحلقات المسندة إليه.
        $teacher = Teacher::query()
            ->where('user_id', $user->id)
            ->first();

        if ($teacher) {
            return (int) $circle->teacher_id === (int) $teacher->id;
        }

        // بقية المستخدمين يخضعون لنطاق المراكز.
        return app(CenterHierarchy::class)->canUserAccess(
            $user,
            (int) $record->center_id
        );
    }
}
