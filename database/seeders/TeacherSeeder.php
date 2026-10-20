<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $centers = DB::table('centers')->get();

        if ($centers->isEmpty()) {
            $this->command->warn('لا توجد مراكز. أضف المراكز أولاً.');
            return;
        }

        $teachersData = [
            [
                'full_name' => 'محمد عبدالسلام الورفلي',
                'phone' => '0910000001',
                'email' => 'mohammed.teacher@example.com',
                'qualification' => 'إجازة في القرآن الكريم',
            ],
            [
                'full_name' => 'أحمد علي المصراتي',
                'phone' => '0910000002',
                'email' => 'ahmed.teacher@example.com',
                'qualification' => 'حافظ للقرآن الكريم',
            ],
            [
                'full_name' => 'خالد محمود الترهوني',
                'phone' => '0910000003',
                'email' => 'khaled.teacher@example.com',
                'qualification' => 'دبلوم دراسات إسلامية',
            ],
        ];

        foreach ($centers as $center) {
            foreach ($teachersData as $index => $teacher) {
                DB::table('teachers')->updateOrInsert(
                    [
                        'center_id' => $center->id,
                        'email' => $teacher['email'],
                    ],
                    [
                        'user_id' => null,
                        'full_name' => $teacher['full_name'],
                        'phone' => $teacher['phone'],
                        'qualification' => $teacher['qualification'],
                        'hire_date' => now()->subYears($index + 1)->toDateString(),
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        $this->command->info('تمت إضافة المعلمين التجريبيين بنجاح.');
    }
}
