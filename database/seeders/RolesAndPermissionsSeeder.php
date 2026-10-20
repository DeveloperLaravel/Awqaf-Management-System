<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Circle;
use App\Models\Guardian;
use App\Models\Region;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * تشغيل التهيئة.
     */
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        DB::transaction(function (): void {
            $this->createPermissions();
            $this->createRoles();
            $this->createSuperAdmin();

            $region = $this->createDemoRegion();
            $center = $this->createDemoCenter($region);

            $this->createDemoCenterUsers($center);
        });

        $registrar->forgetCachedPermissions();

        $this->command?->info(
            'تم إنشاء الصلاحيات والأدوار والبيانات التجريبية بنجاح.'
        );

        $this->command?->warn(
            'كلمات المرور التجريبية مخصصة للتطوير فقط. غيّرها قبل النشر.'
        );
    }

    /**
     * إنشاء جميع الصلاحيات.
     */
    private function createPermissions(): void
    {
        $permissions = [
            'dashboard.view',

            // المراكز
            'centers.view',
            'centers.view_all',
            'centers.create',
            'centers.create_child',
            'centers.update',
            'centers.delete',
            'centers.approve_child',
            'centers.approve_final',

            // المستخدمون
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // المناطق
            'regions.view',
            'regions.create',
            'regions.update',
            'regions.delete',

            // المعلمون
            'teachers.view',
            'teachers.create',
            'teachers.update',
            'teachers.delete',

            // أولياء الأمور
            'guardians.view',
            'guardians.create',
            'guardians.update',
            'guardians.delete',

            // الطلاب
            'students.view',
            'students.create',
            'students.update',
            'students.delete',

            // الحلقات
            'circles.view',
            'circles.create',
            'circles.update',
            'circles.delete',
            
                'roles.view',
                'roles.create',
                'roles.update',
                'roles.delete',

            // الحضور
            'attendance.view',
            'attendance.create',
            'attendance.update',
            'attendance.delete',

            // الحفظ والتسميع
            'memorization.view',
            'memorization.create',
            'memorization.update',
            'memorization.delete',

            // التقييمات
            'evaluations.view',
            'evaluations.create',
            'evaluations.update',
            'evaluations.delete',

            // المدفوعات
            'payments.view',
            'payments.create',
            'payments.update',
            'payments.delete',

            // التقارير
            'reports.view',
            'financial_reports.view',

            // الإعدادات والملف الشخصي
            'settings.view',
            'settings.update',
            'profile.view',
            'profile.update',

            // صلاحيات الطالب وولي الأمر
            'children.view_own',
            'attendance.view_own',
            'memorization.view_own',
            'payments.view_own',
        ];

        foreach (array_unique($permissions) as $permissionName) {
            Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * إنشاء الأدوار وإسناد الصلاحيات.
     *
     * يتم استبدال صلاحيات الدور بالقائمة المحددة عند كل تشغيل.
     */
    private function createRoles(): void
    {
        $rolePermissions = [
            'super_admin' => Permission::query()
                ->where('guard_name', 'web')
                ->pluck('name')
                ->all(),

            'center_manager' => [
                'dashboard.view',

                'centers.view',
                'centers.create_child',
                'centers.update',
                'centers.approve_child',

                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                
                'roles.view',
                'roles.create',
                'roles.update',
                'roles.delete',

                'regions.view',

                'teachers.view',
                'teachers.create',
                'teachers.update',
                'teachers.delete',

                'guardians.view',
                'guardians.create',
                'guardians.update',
                'guardians.delete',

                'students.view',
                'students.create',
                'students.update',
                'students.delete',

                'circles.view',
                'circles.create',
                'circles.update',
                'circles.delete',

                

                'attendance.view',
                'attendance.create',
                'attendance.update',
                'attendance.delete',

                'memorization.view',
                'memorization.create',
                'memorization.update',
                'memorization.delete',

                'evaluations.view',
                'evaluations.create',
                'evaluations.update',
                'evaluations.delete',

                'payments.view',
                'payments.create',
                'payments.update',
                'payments.delete',

                'reports.view',
                'financial_reports.view',

                'profile.view',
                'profile.update',
            ],

            'center_admin' => [
                'dashboard.view',

                'centers.view',
                'centers.create_child',
                'centers.update',
                'centers.delete',
                'centers.approve_child',

                'users.view',
                'users.create',
                'users.update',
                'users.update',

                'regions.view',
                'regions.create',
                'regions.update',
                'regions.delete',

                'teachers.view',
                'teachers.create',
                'teachers.update',
                'teachers.delete',

                'guardians.view',
                'guardians.create',
                'guardians.update',
                'guardians.delete',

                'students.view',
                'students.create',
                'students.update',
                'students.delete',

                'roles.view',
                'roles.create',
                'roles.update',
                'roles.delete',

                'circles.view',
                'circles.create',
                'circles.update',

                'attendance.view',
                'attendance.create',
                'attendance.update',

                'memorization.view',
                'memorization.create',
                'memorization.update',

                'evaluations.view',
                'evaluations.create',
                'evaluations.update',

                'payments.view',
                'payments.create',
                'payments.update',

                'reports.view',
                'financial_reports.view',

                'profile.view',
                'profile.update',
            ],

            'supervisor' => [
                'dashboard.view',
                'students.view',
                'teachers.view',
                'circles.view',

                'attendance.view',
                'attendance.create',
                'attendance.update',

                'memorization.view',
                'memorization.create',
                'memorization.update',

                'evaluations.view',
                'evaluations.create',
                'evaluations.update',

                'reports.view',
                'profile.view',
                'profile.update',
            ],

            'teacher' => [
                'dashboard.view',
                'students.view',
                'circles.view',

                'attendance.view',
                'attendance.create',
                'attendance.update',

                'memorization.view',
                'memorization.create',
                'memorization.update',

                'evaluations.view',
                'evaluations.create',
                'evaluations.update',

                'profile.view',
                'profile.update',
            ],

            'data_entry' => [
                'dashboard.view',

                'students.view',
                'students.create',
                'students.update',

                'guardians.view',
                'guardians.create',
                'guardians.update',

                'teachers.view',
                'circles.view',

                'profile.view',
                'profile.update',
            ],

            'attendance_officer' => [
                'dashboard.view',
                'students.view',
                'circles.view',

                'attendance.view',
                'attendance.create',
                'attendance.update',

                'profile.view',
                'profile.update',
            ],

            'accountant' => [
                'dashboard.view',
                'students.view',
                'guardians.view',

                'payments.view',
                'payments.create',
                'payments.update',

                'financial_reports.view',

                'profile.view',
                'profile.update',
            ],

            'guardian' => [
                'profile.view',
                'profile.update',
                'children.view_own',
                'attendance.view_own',
                'memorization.view_own',
                'payments.view_own',
            ],

            'student' => [
                'profile.view',
                'profile.update',
                'attendance.view_own',
                'memorization.view_own',
            ],

            'viewer' => [
                'dashboard.view',
                'centers.view',
                'students.view',
                'teachers.view',
                'guardians.view',
                'circles.view',
                'attendance.view',
                'memorization.view',
                'reports.view',
                'profile.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $permissions = Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('name', array_unique($permissionNames))
                ->get();

            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * إنشاء حساب المدير العام.
     */
    private function createSuperAdmin(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'hnarfr20063@gmail.com'],
            [
                'name' => 'المدير العام',
                'password' => Hash::make('12345678'),
            ],
        );

        $role = Role::query()->firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $user->syncRoles([$role]);

        $this->command?->info(
            'حساب المدير العام: hnarfr20063@gmail.com'
        );
    }

    /**
     * إنشاء المنطقة التجريبية.
     */
    private function createDemoRegion(): Region
    {
        return $this->firstOrCreateModel(
            Region::class,
            ['code' => 'TRP-DEMO-01'],
            [
                'name' => 'منطقة طرابلس التجريبية',
                'description' => 'منطقة مخصصة لبيانات الاختبار.',
                'is_active' => true,
            ],
        );
    }

    /**
     * إنشاء المركز الرئيسي التجريبي.
     */
    private function createDemoCenter(Region $region): Center
    {
        return $this->firstOrCreateModel(
            Center::class,
            ['code' => 'CTR-DEMO-001'],
            [
                'name' => 'مركز النور لتحفيظ القرآن',
                'region_id' => $region->getKey(),
                'phone' => '0910000001',
                'email' => 'center.demo@example.com',
                'address' => 'طرابلس - عنوان تجريبي',
                'is_active' => true,
                'approval_status' => 'active',
                'approved_at' => now(),
                'approved_by' => null,
                'parent_center_id' => null,
                'parent_approved_by' => null,
                'parent_approved_at' => null,
                'rejection_reason' => null,
            ],
        );
    }

    /**
     * إنشاء بيانات مستخدمي المركز.
     */
    private function createDemoCenterUsers(Center $center): void
    {
        $manager = $this->createUser(
            name: 'مدير المركز التجريبي',
            email: 'manager@example.com',
            password: 'Manager123!',
            role: 'center_manager',
            center: $center,
        );

        /*
         * لا نفترض أن managedCenters علاقة Many-to-Many.
         * ننفذ المزامنة فقط عندما تكون العلاقة من هذا النوع.
         */
        if (method_exists($manager, 'managedCenters')) {
            $relation = $manager->managedCenters();

            if ($relation instanceof BelongsToMany) {
                $relation->syncWithoutDetaching([$center->getKey()]);
            }
        }

        $this->command?->info(
            'مدير المركز: manager@example.com / Manager123!'
        );

        // المعلمون.
        $teachersData = [
            [
                'name' => 'محمد سالم',
                'email' => 'teacher1@example.com',
                'phone' => '0911000001',
                'qualification' => 'إجازة في القرآن الكريم',
                'hire_date' => '2025-01-01',
            ],
            [
                'name' => 'أحمد علي',
                'email' => 'teacher2@example.com',
                'phone' => '0911000002',
                'qualification' => 'حافظ للقرآن الكريم',
                'hire_date' => '2025-02-01',
            ],
            [
                'name' => 'خالد عمر',
                'email' => 'teacher3@example.com',
                'phone' => '0911000003',
                'qualification' => 'معلم قرآن كريم',
                'hire_date' => '2025-03-01',
            ],
        ];

        $teachers = [];

        foreach ($teachersData as $item) {
            $user = $this->createUser(
                name: $item['name'],
                email: $item['email'],
                password: 'Teacher123!',
                role: 'teacher',
                center: $center,
            );

            $teacher = $this->firstOrCreateModel(
                Teacher::class,
                [
                    'center_id' => $center->getKey(),
                    'email' => $item['email'],
                ],
                [
                    'user_id' => $user->getKey(),
                    'full_name' => $item['name'],
                    'phone' => $item['phone'],
                    'qualification' => $item['qualification'],
                    'hire_date' => $item['hire_date'],
                    'is_active' => true,
                ],
            );

            $teachers[] = $teacher;

            $this->command?->info(
                'حساب المعلم: ' . $item['email'] . ' / Teacher123!'
            );
        }

        // الحلقات.
        $circleData = [
            [
                'name' => 'حلقة الفجر',
                'level' => 'beginner',
                'room' => 'قاعة 1',
                'starts_at' => '08:00:00',
                'ends_at' => '09:00:00',
            ],
            [
                'name' => 'حلقة الضحى',
                'level' => 'beginner',
                'room' => 'قاعة 2',
                'starts_at' => '10:00:00',
                'ends_at' => '11:00:00',
            ],
            [
                'name' => 'حلقة العصر',
                'level' => 'beginner',
                'room' => 'قاعة 3',
                'starts_at' => '16:00:00',
                'ends_at' => '17:00:00',
            ],
        ];

        $circles = [];

        foreach ($teachers as $index => $teacher) {
            $data = $circleData[$index];

            $circles[] = $this->firstOrCreateModel(
                Circle::class,
                [
                    'center_id' => $center->getKey(),
                    'code' => sprintf('CIRCLE-%03d', $index + 1),
                ],
                [
                    'teacher_id' => $teacher->getKey(),
                    'name' => $data['name'],
                    'level' => $data['level'],
                    'room' => $data['room'],
                    'capacity' => 20,
                    'starts_at' => $data['starts_at'],
                    'ends_at' => $data['ends_at'],
                    'days' => ['sunday', 'tuesday', 'thursday'],
                    'is_active' => true,
                ],
            );
        }

        // أولياء الأمور.
        $guardiansData = [
            [
                'name' => 'سالم محمد',
                'email' => 'guardian1@example.com',
                'phone' => '0922000001',
            ],
            [
                'name' => 'علي أحمد',
                'email' => 'guardian2@example.com',
                'phone' => '0922000002',
            ],
            [
                'name' => 'عمر خالد',
                'email' => 'guardian3@example.com',
                'phone' => '0922000003',
            ],
            [
                'name' => 'محمد إبراهيم',
                'email' => 'guardian4@example.com',
                'phone' => '0922000004',
            ],
        ];

        $guardians = [];

        foreach ($guardiansData as $item) {
            $user = $this->createUser(
                name: $item['name'],
                email: $item['email'],
                password: 'Guardian123!',
                role: 'guardian',
                center: $center,
            );

            $guardian = $this->firstOrCreateModel(
                Guardian::class,
                [
                    'center_id' => $center->getKey(),
                    'phone' => $item['phone'],
                ],
                [
                    'user_id' => $user->getKey(),
                    'full_name' => $item['name'],
                    'email' => $item['email'],
                    'address' => 'طرابلس',
                    'relationship' => 'father',
                    'is_active' => true,
                ],
            );

            $guardians[] = $guardian;

            $this->command?->info(
                'حساب ولي الأمر: '
                . $item['email']
                . ' / Guardian123!'
            );
        }

        // الطلاب وتوزيعهم على الحلقات.
        for ($i = 1; $i <= 12; $i++) {
            $guardianIndex = ($i - 1) % count($guardians);
            $circleIndex = ($i - 1) % count($circles);

            $student = $this->firstOrCreateModel(
                Student::class,
                [
                    'center_id' => $center->getKey(),
                    'student_code' => sprintf('STU-%04d', $i),
                ],
                [
                    'guardian_id' => $guardians[$guardianIndex]->getKey(),
                    'full_name' => 'الطالب التجريبي ' . $i,
                    'birth_date' => now()
                        ->subYears(8 + ($i % 6))
                        ->toDateString(),
                    'enrollment_date' => now()
                        ->subMonths($i)
                        ->toDateString(),
                    'gender' => $i % 2 === 0 ? 'female' : 'male',
                    'school_grade' => 'الصف ' . (1 + ($i % 6)),
                    'notes' => 'بيانات طالب تجريبية.',
                    'is_active' => true,
                ],
            );

            $circle = $circles[$circleIndex];

            /*
             * الطلاب قد يرتبطون بالحلقة بعلاقة Many-to-Many
             * أو HasMany حسب تصميم قاعدة البيانات.
             */
            if (method_exists($circle, 'students')) {
                $relation = $circle->students();

                if ($relation instanceof BelongsToMany) {
                    $relation->syncWithoutDetaching([
                        $student->getKey(),
                    ]);
                } elseif (
                    method_exists($relation, 'getRelated')
                    && $relation->getRelated() instanceof Student
                ) {
                    $student->forceFill([
                        $relation->getForeignKeyName() => $circle->getKey(),
                    ])->save();
                }
            }
        }
    }

    /**
     * إنشاء المستخدم وربطه بالدور والمركز.
     */
    private function createUser(
        string $name,
        string $email,
        string $password,
        string $role,
        Center $center,
    ): User {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
            ],
        );

        if (Schema::hasColumn($user->getTable(), 'center_id')) {
            if ($user->center_id === null) {
                $user->forceFill([
                    'center_id' => $center->getKey(),
                ])->save();
            }
        }

        $roleModel = Role::query()->firstOrCreate([
            'name' => $role,
            'guard_name' => 'web',
        ]);

        $user->syncRoles([$roleModel]);

        return $user;
    }

    /**
     * البحث عن سجل وإنشاؤه إذا لم يكن موجودًا.
     *
     * أعمدة الهوية يجب أن تكون موجودة كلها؛
     * لا نسمح بإسقاطها بصمت حتى لا نطابق سجلًا خاطئًا.
     */
    private function firstOrCreateModel(
        string $modelClass,
        array $identity,
        array $attributes,
    ): Model {
        /** @var Model $prototype */
        $prototype = new $modelClass();

        $table = $prototype->getTable();

        if ($identity === []) {
            throw new RuntimeException(
                "لم يتم تحديد هوية السجل في الجدول {$table}."
            );
        }

        foreach (array_keys($identity) as $column) {
            if (! Schema::hasColumn($table, $column)) {
                throw new RuntimeException(
                    "العمود {$column} غير موجود في الجدول {$table}. "
                    . 'راجع Migration الخاص بهذا الجدول.'
                );
            }
        }

        $attributes = $this->existingColumns($table, $attributes);

        /** @var Model|null $record */
        $record = $modelClass::query()
            ->where($identity)
            ->first();

        if ($record !== null) {
            return $record;
        }

        $record = new $modelClass();

        $record->forceFill(array_merge(
            $identity,
            $attributes,
        ));

        $record->save();

        return $record;
    }

    /**
     * الاحتفاظ بالحقول الموجودة فقط.
     */
    private function existingColumns(
        string $table,
        array $attributes,
    ): array {
        return array_filter(
            $attributes,
            static fn (string $column): bool =>
                Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_KEY,
        );
    }
}