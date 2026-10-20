<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CircleSeeder extends Seeder
{
    public function run(): void
    {
        $centers = DB::table('centers')->get();

        if ($centers->isEmpty()) {
            $this->command->warn(
                'لا توجد مراكز. شغّل CenterSeeder أولاً.'
            );
            return;
        }

        $circles = [
            [
                'name' => 'حلقة الفجر',
                'code' => 'CIR-001',
                'level' => 'مبتدئ',
                'room' => 'قاعة 1',
                'capacity' => 20,
                'starts_at' => '06:00:00',
                'ends_at' => '07:30:00',
                'days' => 'الأحد، الثلاثاء، الخميس',
            ],
            [
                'name' => 'حلقة النور',
                'code' => 'CIR-002',
                'level' => 'متوسط',
                'room' => 'قاعة 2',
                'capacity' => 25,
                'starts_at' => '09:00:00',
                'ends_at' => '10:30:00',
                'days' => 'السبت، الاثنين، الأربعاء',
            ],
            [
                'name' => 'حلقة الإتقان',
                'code' => 'CIR-003',
                'level' => 'متقدم',
                'room' => 'قاعة 3',
                'capacity' => 15,
                'starts_at' => '16:00:00',
                'ends_at' => '17:30:00',
                'days' => 'الأحد، الثلاثاء، الخميس',
            ],
            [
                'name' => 'حلقة البراعم',
                'code' => 'CIR-004',
                'level' => 'أطفال',
                'room' => 'قاعة الأطفال',
                'capacity' => 20,
                'starts_at' => '17:00:00',
                'ends_at' => '18:00:00',
                'days' => 'السبت، الاثنين، الأربعاء',
            ],
        ];

        foreach ($centers as $center) {
            // اختيار معلم من المركز نفسه
            $teachers = DB::table('teachers')
                ->where('center_id', $center->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            foreach ($circles as $index => $circle) {
                $teacher = $teachers->isNotEmpty()
                    ? $teachers[$index % $teachers->count()]
                    : null;

                DB::table('circles')->updateOrInsert(
                    [
                        'center_id' => $center->id,
                        'code' => $circle['code'],
                    ],
                    [
                        'teacher_id' => $teacher?->id,
                        'name' => $circle['name'],
                        'level' => $circle['level'],
                        'room' => $circle['room'],
                        'capacity' => $circle['capacity'],
                        'starts_at' => $circle['starts_at'],
                        'ends_at' => $circle['ends_at'],
                        'days' => $circle['days'],
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        $this->command->info(
            'تم إنشاء الحلقات التجريبية وربطها بالمراكز والمعلمين.'
        );
    }
}
