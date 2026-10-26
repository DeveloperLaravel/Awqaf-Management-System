<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Center;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;

class CreateCenterAdminAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::query()
            ->where('name', 'center_admin')
            ->where('guard_name', 'web')
            ->first();

        if (! $role) {
            throw new RuntimeException(
                'دور center_admin غير موجود. تحقق من RolesAndPermissionsSeeder.'
            );
        }

        // المراكز الرئيسية فقط، باستخدام الأكواد الموجودة في Seeder الحالي.
        $mainCenters = [
            'CTR-DEMO-001' => 'مدير إداري - مركز النور الرئيسي الأول',
            'CTR-DEMO-002' => 'مدير إداري - مركز النور الرئيسي الثاني',
        ];

        foreach ($mainCenters as $code => $name) {
            $center = Center::query()
                ->where('code', $code)
                ->first();

            if (! $center) {
                throw new RuntimeException(
                    "لم يتم العثور على المركز الرئيسي: {$code}"
                );
            }

            $email = strtolower($code) . '.admin@example.com';

            $user = User::query()->firstOrNew([
                'email' => $email,
            ]);

            // لا نغيّر حسابًا موجودًا إذا كان مرتبطًا بمركز آخر.
            if (
                $user->exists &&
                (int) $user->center_id !== (int) $center->id
            ) {
                throw new RuntimeException(
                    "البريد {$email} مستخدم لحساب مرتبط بمركز آخر."
                );
            }

            if (! $user->exists) {
                $user->name = $name;
                $user->password = Hash::make('12345678');
                $user->center_id = $center->id;
                $user->save();
            }

            // تعيين الدور للحساب الإداري الجديد.
            $user->syncRoles([$role]);
        }

        $this->command?->info(
            'تم إنشاء حسابات center_admin للمراكز الرئيسية أو التحقق منها.'
        );
    }
}
