<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Center;
use App\Models\Circle;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class CircleAssignmentValidator
{
    public function validate(User $user, array $data): void
    {
        $centerId = (int) ($data['center_id'] ?? 0);
        $teacherId = (int) ($data['teacher_id'] ?? 0);

        // تحديد المراكز المسموح بها.
        if ($user->hasRole('super_admin')) {
            $allowed = Center::query()
                ->whereKey($centerId)
                ->exists();
        } elseif ($user->hasRole('center_manager')) {
            $allowedIds = collect(
                app(CenterHierarchy::class)
                    ->accessibleIdsForUser($user)
            );

            $allowed = $allowedIds->contains($centerId);
        } else {
            $allowed = false;
        }

        if (! $allowed) {
            throw ValidationException::withMessages([
                'center_id' => 'ليس لديك صلاحية إنشاء حلقة في هذا المركز.',
            ]);
        }

        // يجب أن يكون المعلم نشطاً ومرتبطاً بالمركز عبر Pivot.
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
}