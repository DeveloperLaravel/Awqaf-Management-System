<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StudentService
{
    public function __construct(
        protected CenterHierarchy $hierarchy
    ) {}

    /**
     * جلب الطلاب حسب صلاحيات المستخدم.
     *
     * الطالب: سجله الشخصي فقط.
     * المعلم: طلاب حلقاته النشطة.
     * الإدارة: طلاب المراكز المسموح بها.
     */
    public function getStudents(
        User $user,
        ?string $search = null
    ): Collection|LengthAwarePaginator {

        // الطالب يرى سجله الشخصي فقط.
        if ($user->hasRole('student')) {
            $student = Student::query()
                ->with([
                    'center:id,name',
                    'circles:id,name,center_id,teacher_id',
                    'circles.teacher:id,full_name',
                ])
                ->where('user_id', $user->id)
                ->first();

            return $student
                ? collect([$student])
                : collect();
        }

        // المعلم يرى طلاب حلقاته فقط.
        if ($user->hasRole('teacher')) {
            $teacher = $user->teacher;

            if (! $teacher) {
                return Student::query()
                    ->whereRaw('1 = 0')
                    ->paginate(20);
            }

            $query = Student::query()
                ->whereHas('circles', function (Builder $query) use ($teacher) {
                    $query->where('circles.teacher_id', $teacher->id)
                        ->whereNull('circle_student.left_at');
                })
                ->with([
                    'center:id,name',
                    'circles:id,name,center_id,teacher_id',
                ])
                ->distinct();

            $this->applySearch($query, $search);

            return $query->paginate(20);
        }

        // الإدارة ترى طلاب المراكز المسموح بها.
        $centerIds = $this->hierarchy
            ->activeAccessibleIdsForUser($user);

        $query = Student::query()
            ->whereIn('center_id', $centerIds)
            ->with([
                'center:id,name',
                'user:id,name,email',
            ]);

        $this->applySearch($query, $search);

        return $query->paginate(20);
    }

    /**
     * البحث باسم الطالب أو رقمه.
     */
    protected function applySearch(
        Builder $query,
        ?string $search
    ): void {
        if (blank($search)) {
            return;
        }

        $query->where(function (Builder $q) use ($search) {
            $q->where('full_name', 'ilike', "%{$search}%")
                ->orWhere('student_code', 'ilike', "%{$search}%");
        });
    }

    /**
     * جلب طالب محدد.
     */
    public function findStudent(int $studentId): ?Student
    {
        return Student::query()
            ->with([
                'center:id,name',
                'guardians',
                'circles.teacher',
            ])
            ->find($studentId);
    }

    /**
     * التحقق من إمكانية المستخدم الوصول إلى الطالب.
     */
    public function canAccessStudent(
        User $user,
        Student $student
    ): bool {

        // الطالب لا يرى إلا سجله.
        if ($user->hasRole('student')) {
            return (int) $student->user_id === (int) $user->id;
        }

        // المعلم يرى طلاب حلقاته فقط.
        if ($user->hasRole('teacher')) {
            $teacher = $user->teacher;

            return $teacher !== null
                && $student->circles()
                    ->where('circles.teacher_id', $teacher->id)
                    ->whereNull('circle_student.left_at')
                    ->exists();
        }

        // الإدارة حسب التسلسل الهرمي للمراكز.
        return $this->hierarchy->canUserAccess(
            $user,
            (int) $student->center_id
        );
    }
}
