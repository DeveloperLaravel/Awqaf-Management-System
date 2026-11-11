<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TeacherService
{
    public function __construct(
        protected CenterHierarchy $hierarchy
    ) {}

    /**
     * جلب المعلمين حسب صلاحيات المستخدم.
     *
     * المعلم: بياناته الشخصية فقط.
     * الطالب: لا يسمح له بعرض قائمة المعلمين.
     * الإدارة: المعلمون المرتبطون بمراكزها المسموح بها.
     */
    public function getTeachers(
        User $user,
        ?string $search = null
    ): Collection|LengthAwarePaginator {

        // المعلم يرى بياناته فقط.
        if ($user->hasRole('teacher')) {
            $teacher = Teacher::query()
                ->with([
                    'centers:id,name',
                    'circles',
                ])
                ->where('user_id', $user->id)
                ->first();

            return $teacher
                ? collect([$teacher])
                : collect();
        }

        // الطالب لا يحصل على قائمة المعلمين.
        abort_if(
            $user->hasRole('student'),
            403,
            'ليس لديك صلاحية عرض قائمة المعلمين.'
        );

        // الإدارة ترى المعلمين في المراكز المسموح بها.
        $centerIds = $this->hierarchy
            ->activeAccessibleIdsForUser($user);

        $query = Teacher::query()
            ->whereHas('activeCenters', function (Builder $query) use ($centerIds) {
                $query->whereIn('centers.id', $centerIds);
            })
            ->with([
                'centers' => function (Builder $query) use ($centerIds) {
                    $query->whereIn('centers.id', $centerIds);
                },
                'circles',
            ]);

        $this->applySearch($query, $search);

        return $query
            ->distinct()
            ->paginate(20);
    }

    /**
     * البحث عن المعلم بالاسم.
     */
    protected function applySearch(
        Builder $query,
        ?string $search
    ): void {
        if (blank($search)) {
            return;
        }

        $query->where(
            'full_name',
            'ilike',
            '%' . trim($search) . '%'
        );
    }

    /**
     * التحقق من إمكانية الوصول إلى بيانات المعلم.
     */
    public function canAccessTeacher(
        User $user,
        Teacher $teacher
    ): bool {

        // المعلم يرى بياناته فقط.
        if ($user->hasRole('teacher')) {
            return (int) $teacher->user_id === (int) $user->id;
        }

        // الطالب يرى معلمي حلقاته المسجل فيها.
        if ($user->hasRole('student')) {
            $student = $user->student;

            return $student !== null
                && $student->circles()
                    ->where('circles.teacher_id', $teacher->id)
                    ->whereNull('circle_student.left_at')
                    ->exists();
        }

        // الإدارة حسب المراكز التي يمكنها الوصول إليها.
        $centerIds = $this->hierarchy
            ->activeAccessibleIdsForUser($user);

        return $teacher->activeCenters()
            ->whereIn('centers.id', $centerIds)
            ->exists();
    }

    /**
     * جلب بيانات معلم محدد مع علاقاته.
     */
    public function findTeacher(int $teacherId): ?Teacher
    {
        return Teacher::query()
            ->with([
                'centers',
                'circles.students',
            ])
            ->find($teacherId);
    }
}
