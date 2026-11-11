<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Center;
use App\Models\Circle;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use App\Services\CenterHierarchy;
class CircleAssignmentValidator
{
    public function validate(
        User $user,
        array $data,
        ?Circle $circle = null
    ): void {

        /*
         * المعلم: يسمح له بتعديل حلقته الحالية فقط.
         */
        if ($user->hasRole('teacher')) {
            $this->validateTeacherUpdate($user, $data, $circle);

            return;
        }

        /*
         * المدير العام ومديرو المراكز.
         */
        $centerId = (int) (
            $data['center_id'] ?? $circle?->center_id ?? 0
        );

        $teacherId = isset($data['teacher_id'])
            ? (int) $data['teacher_id']
            : null;

        if (! $this->canAccessCenter($user, $centerId)) {
            throw ValidationException::withMessages([
                'center_id' => 'ليس لديك صلاحية إنشاء أو تعديل حلقة في هذا المركز.',
            ]);
        }

        // المعلم اختياري.
        if (! $teacherId) {
            return;
        }

        $teacherBelongsToCenter = Teacher::query()
            ->whereKey($teacherId)
            ->where('teachers.is_active', true)
            ->whereHas(
                'centers',
                fn (Builder $query) => $query
                    ->where('centers.id', $centerId)
                    ->where('center_teacher.is_active', true)
            )
            ->exists();

        if (! $teacherBelongsToCenter) {
            throw ValidationException::withMessages([
                'teacher_id' => 'المعلم غير مرتبط بهذا المركز أو أن ارتباطه غير نشط.',
            ]);
        }
    }

    /**
     * التحقق من تعديل المعلم لحلقته الخاصة.
     */
    private function validateTeacherUpdate(
        User $user,
        array $data,
        ?Circle $circle
    ): void {

        if (! $circle || ! $circle->exists) {
            throw ValidationException::withMessages([
                'circle' => 'لا يمكنك إنشاء حلقة جديدة بحساب المعلم.',
            ]);
        }

        $circle->loadMissing('teacher');

        /*
         * التأكد أن الحلقة تابعة لهذا المعلم.
         */
        if (
            ! $circle->teacher
            || (int) $circle->teacher->user_id !== (int) $user->id
        ) {
            throw ValidationException::withMessages([
                'circle' => 'لا يمكنك تعديل حلقة ليست تابعة لك.',
            ]);
        }

        /*
         * منع تغيير المركز.
         */
        $centerId = (int) (
            $data['center_id'] ?? $circle->center_id
        );

        if ($centerId !== (int) $circle->center_id) {
            throw ValidationException::withMessages([
                'center_id' => 'لا يمكنك تغيير مركز الحلقة.',
            ]);
        }

        /*
         * منع تغيير المعلم المسؤول.
         */
        $teacherId = isset($data['teacher_id'])
            ? (int) $data['teacher_id']
            : (int) $circle->teacher_id;

        if ($teacherId !== (int) $circle->teacher_id) {
            throw ValidationException::withMessages([
                'teacher_id' => 'لا يمكنك تغيير المعلم المسؤول عن الحلقة.',
            ]);
        }

        /*
         * التأكد من أن المعلم ما زال نشطاً ومرتبطاً بالمركز.
         */
        $teacherIsActiveAndAssigned = Teacher::query()
            ->whereKey($circle->teacher_id)
            ->where('user_id', $user->id)
            ->where('teachers.is_active', true)
            ->whereHas(
                'centers',
                fn (Builder $query) => $query
                    ->where('centers.id', $circle->center_id)
                    ->where('center_teacher.is_active', true)
            )
            ->exists();

        if (! $teacherIsActiveAndAssigned) {
            throw ValidationException::withMessages([
                'teacher_id' => 'ارتباطك بهذه الحلقة أو بالمركز غير نشط.',
            ]);
        }
    }

    /**
     * التحقق من صلاحية المستخدم على المركز.
     */
    private function canAccessCenter(
        User $user,
        int $centerId
    ): bool {

        if ($centerId <= 0) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return Center::query()
                ->whereKey($centerId)
                ->exists();
        }

        if ($user->hasAnyRole([
            'center_admin',
            'center_manager',
        ])) {
            $allowedIds = array_map(
                'intval',
                app(CenterHierarchy::class)
                    ->accessibleIdsForUser($user)
            );

            return in_array($centerId, $allowedIds, true);
        }

        return false;
    }
}