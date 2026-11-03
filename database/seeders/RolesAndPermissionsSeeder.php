<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        // Reset permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | 1. Roles - الأدوار
        |--------------------------------------------------------------------------
        */

        $roles = [
            'super_admin',
            'center_admin',
            'center_manager',
            'supervisor',
            'teacher',
            'accountant',
            'attendance_officer',
            'data_entry',
            'guardian',
            'student',
            'viewer',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guard,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Permissions - الصلاحيات
        |--------------------------------------------------------------------------
        */

        $permissions = [

            // Dashboard
            'View:Dashboard',

            // Centers
            'ViewAny:Center',
            'View:Center',
            'Create:Center',
            'CreateChild:Center',
            'Update:Center',
            'Delete:Center',
            'ViewAll:Center',
            'ApproveChild:Center',
            'ApproveFinal:Center',
            

            // Users
            'ViewAny:User',
            'View:User',
            'Create:User',
            'Update:User',
            'Delete:User',

            // Regions
            'ViewAny:Region',
            'View:Region',
            'Create:Region',
            'Update:Region',
            'Delete:Region',

            // Teachers
            'ViewAny:Teacher',
            'View:Teacher',
            'Create:Teacher',
            'Update:Teacher',
            'Delete:Teacher',
            'ManageAll:Teacher',
            'Restore:Teacher',
            'ForceDelete:Teacher',

            // Guardians
            'ViewAny:Guardian',
            'View:Guardian',
            'Create:Guardian',
            'Update:Guardian',
            'Delete:Guardian',

            // Students
            'ViewAny:Student',
            'View:Student',
            'Create:Student',
            'Update:Student',
            'Delete:Student',

            // Circles
            'ViewAny:Circle',
            'View:Circle',
            'ViewOwn:Circle',
            'Create:Circle',
            'Update:Circle',
            'Delete:Circle',

            'ViewAny:CircleStudent',
            'View:CircleStudent',
            'Create:CircleStudent',
            'Update:CircleStudent',

            'ViewAny:StudentAssessment',
            'View:StudentAssessment',
            'Create:StudentAssessment',
            'Update:StudentAssessment',
            'Delete:StudentAssessment',



            // Attendance
            'ViewAny:AttendanceRecord',
            'View:AttendanceRecord',
            'ViewOwn:AttendanceRecord',
            'Create:AttendanceRecord',
            'Update:AttendanceRecord',
            'Delete:AttendanceRecord',

            // Memorization
            'ViewAny:MemorizationRecord',
            'View:MemorizationRecord',
            'ViewOwn:MemorizationRecord',
            'Create:MemorizationRecord',
            'Update:MemorizationRecord',
            'Delete:MemorizationRecord',

            // Evaluations
            'ViewAny:Evaluation',
            'View:Evaluation',
            'Create:Evaluation',
            'Update:Evaluation',
            'Delete:Evaluation',

            // Payments
            'ViewAny:Payment',
            'View:Payment',
            'ViewOwn:Payment',
            'Create:Payment',
            'Update:Payment',
            'Delete:Payment',

            // Reports
            'ViewAny:Report',
            'View:Report',
            'View:FinancialReport',

            // Settings
            'ViewAny:Setting',
            'View:Setting',
            'Update:Setting',

            // Profile
            'View:Profile',
            'Update:Profile',

            // Children
            'ViewOwn:Child',

            // Roles
            'ViewAny:Role',
            'View:Role',
            'Create:Role',
            'Update:Role',
            'Delete:Role',
        ];

        /*
        |--------------------------------------------------------------------------
        | 3. Create Permissions
        |--------------------------------------------------------------------------
        */

        foreach (array_unique($permissions) as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guard,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Super Admin - جميع الصلاحيات
        |--------------------------------------------------------------------------
        */

        $superAdminRole = Role::findByName(
            'super_admin',
            $guard
        );

        $allPermissions = Permission::where(
            'guard_name',
            $guard
        )->get();

        $superAdminRole->syncPermissions($allPermissions);

        /*
        |--------------------------------------------------------------------------
        | 5. Create Super Admin User
        |--------------------------------------------------------------------------
        */

        $user = User::query()->firstOrCreate(
            [
                'email' => 'hnarfr20063@gmail.com',
            ],
            [
                'name' => 'المدير العام',
                'password' => Hash::make('12345678'),
            ],
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Assign Super Admin Role
        |--------------------------------------------------------------------------
        */

        $user->syncRoles([
            $superAdminRole,
        ]);

        /*
        |--------------------------------------------------------------------------
        | 7. Clear Permission Cache
        |--------------------------------------------------------------------------
        */

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
