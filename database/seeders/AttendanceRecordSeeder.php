<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceRecordSeeder extends Seeder
{
    public function run(): void
    {
        $students = DB::table('students')
            ->where('is_active', true)
            ->get();

        if ($students->isEmpty()) {
            $this->command->warn(
                'لا يوجد طلاب نشطون. شغّل StudentSeeder أولاً.'
            );
            return;
        }

        $statuses = ['present', 'absent', 'late', 'excused'];
        $today = Carbon::today();
        $inserted = 0;

        foreach ($students as $student) {
            // اختيار حلقات المركز نفسه
            $circles = DB::table('circles')
                ->where('center_id', $student->center_id)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            if ($circles->isEmpty()) {
                continue;
            }

            // اختيار حلقة واحدة للطالب في هذا المثال
            $circle = $circles->first();

            // استخدام معلم الحلقة، إن وُجد
            $teacherId = $circle->teacher_id;

            for ($day = 6; $day >= 0; $day--) {
                $date = $today->copy()
                    ->subDays($day)
                    ->toDateString();

                DB::table('attendance_records')->updateOrInsert(
                    [
                        'student_id' => $student->id,
                        'circle_id' => $circle->id,
                        'attendance_date' => $date,
                    ],
                    [
                        'center_id' => $student->center_id,
                        'teacher_id' => $teacherId,
                        'status' => $statuses[array_rand($statuses)],
                        'notes' => null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $inserted++;
            }
        }

        $this->command->info(
            "تم تجهيز سجلات الحضور التجريبية. عدد عمليات التسجيل: {$inserted}"
        );
    }
}
