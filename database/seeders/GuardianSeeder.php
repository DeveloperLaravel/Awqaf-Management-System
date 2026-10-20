<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GuardianSeeder extends Seeder
{
    public function run(): void
    {
        $centers = DB::table('centers')->get();

        if ($centers->isEmpty()) {
            $this->command->warn('لا توجد مراكز. شغّل CenterSeeder أولاً.');
            return;
        }

        $guardians = [
            [
                'full_name' => 'عبدالله محمد الورفلي',
                'phone' => '0920000001',
                'email' => 'guardian1@example.com',
                'address' => 'بنغازي',
                'relationship' => 'الأب',
            ],
            [
                'full_name' => 'علي سالم المصراتي',
                'phone' => '0920000002',
                'email' => 'guardian2@example.com',
                'address' => 'طرابلس',
                'relationship' => 'الأب',
            ],
            [
                'full_name' => 'فاطمة أحمد الترهوني',
                'phone' => '0920000003',
                'email' => 'guardian3@example.com',
                'address' => 'مصراتة',
                'relationship' => 'الأم',
            ],
            [
                'full_name' => 'خديجة محمود العبيدي',
                'phone' => '0920000004',
                'email' => 'guardian4@example.com',
                'address' => 'البيضاء',
                'relationship' => 'الأم',
            ],
            [
                'full_name' => 'يوسف خالد الزوي',
                'phone' => '0920000005',
                'email' => 'guardian5@example.com',
                'address' => 'أجدابيا',
                'relationship' => 'الأب',
            ],
        ];

        foreach ($centers as $center) {
            foreach ($guardians as $guardian) {
                DB::table('guardians')->updateOrInsert(
                    [
                        'center_id' => $center->id,
                        'phone' => $guardian['phone'],
                    ],
                    [
                        'full_name' => $guardian['full_name'],
                        'email' => $guardian['email'],
                        'address' => $guardian['address'],
                        'relationship' => $guardian['relationship'],
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        $this->command->info(
            'تمت إضافة أولياء الأمور التجريبيين بنجاح.'
        );
    }
}
