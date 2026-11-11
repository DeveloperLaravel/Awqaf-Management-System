<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Circle;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    /**
     * الحلقات المتاحة للمستخدم.
     */
    public function allowedCircles(User $user): Builder
    {
        $query = Circle::query()
            ->where('circles.is_active', true)
            ->whereHas('center', function (Builder $query) {
                $query
                    ->where('is_active', true)
                    ->where('approval_status', 'active');
            });

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        $teacher = Teacher::query()
            ->where('user_id', $user->id)
            ->first();

        if ($teacher) {
            return $query->where('teacher_id', $teacher->id);
        }

        $centerIds = app(CenterHierarchy::class)
            ->activeAccessibleIdsForUser($user);

        return $query->whereIn('center_id', $centerIds);
    }

    /**
     * جلب حلقات المستخدم.
     */
    public function getCircles(User $user): Collection
    {
        return $this->allowedCircles($user)
            ->with('center:id,name')
            ->orderBy('name')
            ->get();
    }

    /**
     * جلب طلاب الحلقة.
     */
 public function getStudents(
    User $user,
    Circle $circle
): Collection {
    abort_unless(
        $this->allowedCircles($user)
            ->whereKey($circle->id)
            ->exists(),
        403,
        'غير مسموح بالوصول إلى هذه الحلقة.'
    );

    return $circle->students()
        ->wherePivotNull('left_at')
        ->orderBy('full_name')
        ->get([
            'students.id',
            'students.full_name',
        ]);
}

    /**
     * تسجيل حضور طالب.
     */
   public function store(
    User $user,
    array $data
): AttendanceRecord {

    return DB::transaction(function () use ($user, $data) {

        // التحقق من صلاحية الوصول للحلقة.
        $circle = $this->allowedCircles($user)
            ->whereKey($data['circle_id'])
            ->firstOrFail();

        // التحقق من أن الطالب تابع للمركز والحلقة.
        $student = Student::query()
            ->whereKey($data['student_id'])
            ->where('center_id', $circle->center_id)
            ->whereHas('circles', function (Builder $query) use ($circle) {
                $query
                    ->where('circles.id', $circle->id)
                    ->whereNull('circle_student.left_at');
            })
            ->firstOrFail();

        // منع تكرار الحضور في اليوم نفسه.
        $exists = AttendanceRecord::query()
            ->where('circle_id', $circle->id)
            ->where('student_id', $student->id)
            ->whereDate(
                'attendance_date',
                $data['attendance_date']
            )
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'student_id' => [
                    'تم تسجيل حضور هذا الطالب مسبقاً في هذا اليوم.',
                ],
            ]);
        }

        // حفظ سجل الحضور.
        $record = AttendanceRecord::create([
            'center_id' => $circle->center_id,
            'circle_id' => $circle->id,
            'student_id' => $student->id,
            'teacher_id' => $circle->teacher_id,
            'attendance_date' => $data['attendance_date'],
            'status' => $data['status'],
            'notes' => $data['notes'] ?? null,
        ]);

        return $record->load([
            'center:id,name',
            'student:id,full_name',
            'circle:id,name',
            'teacher:id,full_name',
        ]);
    });
}
}
