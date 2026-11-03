<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\MemorizationRecord;
use App\Models\Student;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StudentWeeklyAssessmentCalculator
{
    public function calculate(
        Student $student,
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        if ($end->lt($start)) {
            throw new InvalidArgumentException(
                'تاريخ نهاية الأسبوع يجب ألا يسبق تاريخ البداية.'
            );
        }

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $memorizationScore = MemorizationRecord::query()
            ->where('center_id', $student->center_id)
            ->where('student_id', $student->id)
            ->whereBetween('record_date', [$startDate, $endDate])
            ->where('record_type', 'new_memorization')
            ->whereNotNull('grade')
            ->avg('grade');

        $reviewScore = MemorizationRecord::query()
            ->where('center_id', $student->center_id)
            ->where('student_id', $student->id)
            ->whereBetween('record_date', [$startDate, $endDate])
            ->where('record_type', 'revision')
            ->whereNotNull('grade')
            ->avg('grade');

        $tajweedScore = MemorizationRecord::query()
            ->where('center_id', $student->center_id)
            ->where('student_id', $student->id)
            ->whereBetween('record_date', [$startDate, $endDate])
            ->whereNotNull('tajweed_grade')
            ->avg('tajweed_grade');

        $attendance = AttendanceRecord::query()
            ->where('center_id', $student->center_id)
            ->where('student_id', $student->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->selectRaw("
                COUNT(*) FILTER (
                    WHERE status IN ('present', 'late', 'absent')
                ) AS eligible_days
            ")
            ->selectRaw("
                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'present' THEN 100
                            WHEN status = 'late' THEN 75
                            WHEN status = 'absent' THEN 0
                            ELSE 0
                        END
                    ) FILTER (
                        WHERE status IN ('present', 'late', 'absent')
                    ),
                    0
                ) AS attendance_points
            ")
            ->first();

        $eligibleDays = (int) ($attendance->eligible_days ?? 0);

        $attendanceScore = $eligibleDays > 0
            ? round(
                (float) $attendance->attendance_points / $eligibleDays,
                2
            )
            : null;

        return [
            'memorization_score' => $memorizationScore !== null
                ? round((float) $memorizationScore, 2)
                : null,

            'review_score' => $reviewScore !== null
                ? round((float) $reviewScore, 2)
                : null,

            'tajweed_score' => $tajweedScore !== null
                ? round((float) $tajweedScore, 2)
                : null,

            'attendance_score' => $attendanceScore,

            'eligible_attendance_days' => $eligibleDays,
        ];
    }
}
