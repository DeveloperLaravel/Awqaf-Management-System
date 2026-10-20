<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        // جلب جميع مراكز التحفيظ
        $centers = DB::table('centers')
            ->orderBy('id')
            ->get();

        if ($centers->isEmpty()) {
            $this->command->error(
                'لا توجد مراكز. شغّل CenterSeeder أولاً.'
            );

            return;
        }

        // أولياء الأمور، إن وجدوا
        $guardianIds = DB::table('guardians')
            ->pluck('id')
            ->toArray();

        // أسماء الطلاب
        $firstNames = [
            'محمد', 'أحمد', 'محمود', 'علي', 'عمر',
            'يوسف', 'عبدالله', 'إبراهيم', 'مصطفى', 'خالد',
            'سالم', 'أنس', 'أيمن', 'معاذ', 'بلال',
            'أسامة', 'طارق', 'حمزة', 'سفيان', 'رامي',
            'فاطمة', 'عائشة', 'مريم', 'سارة', 'آمنة',
            'نور', 'هدى', 'سلمى', 'إسراء', 'رنا',
            'خديجة', 'زينب', 'رحمة', 'شهد', 'هبة',
            'آلاء', 'جنى', 'لينا', 'دعاء', 'ياسمين',
            'عبدالرحمن', 'أيوب', 'فارس', 'زياد', 'حسن',
            'حسين', 'مالك', 'ريان', 'تميم', 'إياد',
        ];

        $lastNames = [
            'محمد', 'علي', 'الورفلي', 'المصراتي', 'العبيدي',
            'الزوي', 'السنوسي', 'المنفي', 'بن عمران', 'الشيباني',
        ];

        $grades = [
            'الأول الابتدائي',
            'الثاني الابتدائي',
            'الثالث الابتدائي',
            'الرابع الابتدائي',
            'الخامس الابتدائي',
            'السادس الابتدائي',
            'الأول الإعدادي',
            'الثاني الإعدادي',
            'الثالث الإعدادي',
        ];

        $notes = [
            'منتظم في الحضور',
            'يحتاج إلى متابعة الحفظ',
            'مستواه جيد في المراجعة',
            'ملتزم بالحضور',
            null,
        ];

        $createdCount = 0;

        // إنشاء 10 طلاب لكل مركز
        foreach ($centers as $center) {
            for ($i = 1; $i <= 10; $i++) {
                $index = (
                    ($center->id - 1) * 10 + $i - 1
                ) % count($firstNames);

                $guardianId = !empty($guardianIds)
                    ? $guardianIds[
                        ($center->id + $i - 2)
                        % count($guardianIds)
                    ]
                    : null;

                $studentCode = sprintf('ST-%03d', $i);

                $birthDate = now()
                    ->subYears(6 + ($index % 13))
                    ->subDays(($index * 17) % 365)
                    ->toDateString();

                $enrollmentDate = now()
                    ->subDays(($index * 13) % 730)
                    ->toDateString();

                DB::table('students')->updateOrInsert(
                    [
                        'center_id' => $center->id,
                        'student_code' => $studentCode,
                    ],
                    [
                        'guardian_id' => $guardianId,
                        'full_name' => $firstNames[$index]
                            . ' '
                            . $lastNames[$index % count($lastNames)],
                        'birth_date' => $birthDate,
                        'enrollment_date' => $enrollmentDate,
                        'gender' => $index % 2 === 0
                            ? 'ذكر'
                            : 'أنثى',
                        'school_grade' => $grades[$index % count($grades)],
                        'notes' => $notes[$index % count($notes)],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $createdCount++;
            }

            $this->command->info(
                "تم تجهيز 10 طلاب للمركز: {$center->name}"
            );
        }

        $this->command->info(
            "اكتملت العملية. عدد سجلات الطلاب التي تمت معالجتها: {$createdCount}"
        );
    }
}