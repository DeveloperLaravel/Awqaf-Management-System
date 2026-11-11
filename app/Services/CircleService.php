<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Circle;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CircleService
{
    /**
     * جلب الحلقات حسب دور المستخدم ونطاق صلاحياته.
     */
    public function getCircles(
        User $user,
        array $filters = [],
        int $perPage = 100
    ): LengthAwarePaginator {

        $query = Circle::query()->with([
            'center:id,name',
            'teacher:id,user_id,full_name',
        ]);

        // المدير العام: جميع الحلقات.
        if ($user->hasRole('super_admin')) {
            // لا نضيف قيد المركز.
        }

        // مدير المركز ومدير الفرع.
        elseif ($user->hasAnyRole([
            'center_admin',
            'center_manager',
        ])) {
            $centerIds = app(CenterHierarchy::class)
                ->accessibleIdsForUser($user);

            $query->whereIn(
                'circles.center_id',
                $centerIds
            );
        }

        // المعلم: الحلقات المرتبطة به فقط.
        elseif ($user->hasRole('teacher')) {

            $query->whereHas(
                'teacher',
                function (Builder $teacherQuery) use ($user) {

                    $teacherQuery
                        ->where('teachers.user_id', $user->id)
                        ->where('teachers.is_active', true)
                        ->whereHas(
                            'centers',
                            function (Builder $centerQuery) {

                                $centerQuery
                                    ->whereColumn(
                                        'centers.id',
                                        'circles.center_id'
                                    )
                                    ->where(
                                        'center_teacher.is_active',
                                        true
                                    );
                            }
                        );
                }
            );
        }

        // الطالب: الحلقات المسجل فيها فقط.
        elseif ($user->hasRole('student')) {

            $query->whereHas(
                'students',
                function (Builder $studentQuery) use ($user) {

                    $studentQuery
                        ->where('students.user_id', $user->id)
                        ->where('students.is_active', true)
                        ->whereNull('circle_student.left_at')
                        ->whereColumn(
                            'circle_student.center_id',
                            'circles.center_id'
                        );
                }
            );
        }

        // أي دور آخر لا يرى الحلقات.
        else {
            $query->whereRaw('1 = 0');
        }

        // فلترة المركز.
        if (!empty($filters['center_id'])) {
            $query->where(
                'circles.center_id',
                $filters['center_id']
            );
        }

        // فلترة المستوى.
        if (!empty($filters['level'])) {
            $query->where(
                'circles.level',
                $filters['level']
            );
        }

        // فلترة النشاط.
        if (array_key_exists('is_active', $filters)) {
            $query->where(
                'circles.is_active',
                $filters['is_active']
            );
        }

        return $query
            ->latest('circles.id')
            ->paginate(
                min(max($perPage, 1), 100)
            );
    }

    /**
     * جلب حلقة واحدة.
     */
    public function getCircle(Circle $circle): Circle
    {
        return $circle->load([
            'center:id,name',
            'teacher:id,user_id,full_name',
        ]);
    }

    /**
     * إنشاء حلقة جديدة.
     */
    public function createCircle(array $data): Circle
    {
        return DB::transaction(function () use ($data) {

            $circle = Circle::create($data);

            return $circle->load([
                'center:id,name',
                'teacher:id,user_id,full_name',
            ]);
        });
    }

    /**
     * تعديل حلقة.
     */
    public function updateCircle(
        Circle $circle,
        array $data
    ): Circle {

        return DB::transaction(function () use ($circle, $data) {

            $circle->update($data);

            return $circle->refresh()->load([
                'center:id,name',
                'teacher:id,user_id,full_name',
            ]);
        });
    }

    /**
     * حذف حلقة.
     */
    public function deleteCircle(Circle $circle): bool
    {
        return DB::transaction(function () use ($circle) {
            return (bool) $circle->delete();
        });
    }
}
