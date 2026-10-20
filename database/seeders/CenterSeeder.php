<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CenterSeeder extends Seeder
{
    public function run(): void
    {
        $centers = [
            [
                'name' => 'مركز النور لتحفيظ القرآن الكريم',
                'code' => 'CTR-001',
                'phone' => '0910000001',
                'email' => 'noor@example.com',
                'address' => 'بنغازي - ليبيا',
                'is_active' => true,
            ],
            [
                'name' => 'مركز الهدى لتحفيظ القرآن الكريم',
                'code' => 'CTR-002',
                'phone' => '0910000002',
                'email' => 'huda@example.com',
                'address' => 'طرابلس - ليبيا',
                'is_active' => true,
            ],
            [
                'name' => 'مركز الفرقان لتحفيظ القرآن الكريم',
                'code' => 'CTR-003',
                'phone' => '0910000003',
                'email' => 'furqan@example.com',
                'address' => 'مصراتة - ليبيا',
                'is_active' => true,
            ],
            [
                'name' => 'مركز الإتقان لتحفيظ القرآن الكريم',
                'code' => 'CTR-004',
                'phone' => '0910000004',
                'email' => 'itqan@example.com',
                'address' => 'البيضاء - ليبيا',
                'is_active' => true,
            ],
            [
                'name' => 'مركز الرحمة لتحفيظ القرآن الكريم',
                'code' => 'CTR-005',
                'phone' => '0910000005',
                'email' => 'rahma@example.com',
                'address' => 'أجدابيا - ليبيا',
                'is_active' => true,
            ],
        ];

        foreach ($centers as $center) {
            DB::table('centers')->updateOrInsert(
                ['code' => $center['code']],
                array_merge($center, [
                    'updated_at' => now(),
                ]) + [
                    'created_at' => now(),
                ]
            );
        }

        $this->command->info(
            'تمت إضافة أو تحديث 5 مراكز بنجاح.'
        );
    }
}