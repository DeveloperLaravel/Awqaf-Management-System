<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AttendanceRecord;
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
    private const GUARD = 'web';

    private const DEMO_PASSWORD = 'Manager123!';

    private const TEACHERS_PER_BRANCH = 7;

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        DB::transaction(function (): void {
            $this->createPermissions();
            $this->createRoles();

            $superAdmin = $this->createSuperAdmin();
            $region = $this->createDemoRegion();

            // المركز الرئيسي.
            $root = $this->createDemoCenter(
                region: $region,
                code: 'CTR-DEMO-001',
                name: 'مركز النور الرئيسي',
                parent: null,
                approvedBy: $superAdmin->getKey(),
            );

            // مدير المركز الرئيسي.
            $rootManager = $this->createUser(
                name: 'مدير المركز الرئيسي التجريبي',
                email: 'demo.root.manager@example.com',
                password: self::DEMO_PASSWORD,
                role: 'center_manager',
                center: $root,
            );

            $this->attachManager($rootManager, $root);

            // إنشاء المركزين الفرعيين مع بيانات كل مركز.
            $branches = [
                [
                    'code' => 'CTR-DEMO-002',
                    'name' => 'مركز النور الفرعي الأول',
                    'manager_name' => 'مدير الفرع الأول',
                    'manager_email' => 'demo.branch1.manager@example.com',
                    'phone' => '0910000002',
                    'prefix' => 'الفرع الأول',
                ],
                [
                    'code' => 'CTR-DEMO-003',
                    'name' => 'مركز النور الفرعي الثاني',
                    'manager_name' => 'مدير الفرع الثاني',
                    'manager_email' => 'demo.branch2.manager@example.com',
                    'phone' => '0910000003',
                    'prefix' => 'الفرع الثاني',
                ],
            ];

            foreach ($branches as $branch) {
                $center = $this->createDemoCenter(
                    region: $region,
                    code: $branch['code'],
                    name: $branch['name'],
                    parent: $root,
                    approvedBy: $superAdmin->getKey(),
                    parentApprovedBy: $rootManager->getKey(),
                    phone: $branch['phone'],
                );

                $manager = $this->createUser(
                    name: $branch['manager_name'],
                    email: $branch['manager_email'],
                    password: self::DEMO_PASSWORD,
                    role: 'center_manager',
                    center: $center,
                );

                $this->attachManager($manager, $center);

                $this->createBranchData(
                    center: $center,
                    prefix: $branch['prefix'],
                );
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info(
            'اكتمل إعداد الصلاحيات والمركز الرئيسي والمركزين الفرعيين.'
        );

        $this->command?->info(
            'تم إعداد 7 معلمين و7 حلقات و7 أولياء أمور و7 طلاب '
            . 'و7 سجلات حضور لكل مركز فرعي.'
        );

        $this->command?->warn(
            'كلمات المرور تجريبية. لا تستخدمها في الإنتاج.'
        );
    }

    // ---------------------------------------------------------
    // 1. الصلاحيات
    // ---------------------------------------------------------

    private function createPermissions(): void
    {
        $permissions = [
            'dashboard.view',

            'centers.view',
            'centers.view_all',
            'centers.create',
            'centers.create_child',
            'centers.update',
            'centers.delete',
            'centers.approve_child',
            'centers.approve_final',

            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',

            'regions.view',
            'regions.create',
            'regions.update',
            'regions.delete',

            'teachers.view',
            'teachers.create',
            'teachers.update',
            'teachers.delete',
            'teachers.manage_all',
            'teachers.restore',
            'teachers.force_delete',

            'guardians.view',
            'guardians.create',
            'guardians.update',
            'guardians.delete',

            'students.view',
            'students.create',
            'students.update',
            'students.delete',

            'circles.view_own',
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

            'settings.view',
            'settings.update',

            'profile.view',
            'profile.update',

            'children.view_own',
            'attendance.view_own',
            'memorization.view_own',
            'payments.view_own',
        ];

        foreach (array_unique($permissions) as $name) {
            Permission::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => self::GUARD,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // ---------------------------------------------------------
    // 2. الأدوار
    // ---------------------------------------------------------

    private function createRoles(): void
    {
        $allPermissions = Permission::query()
            ->where('guard_name', self::GUARD)
            ->pluck('name')
            ->all();

        /*
         * مدير المركز لا يحصل على centers.view_all
         * ولا teachers.manage_all.
         *
         * صلاحيات البيانات وحدها لا تكفي للعزل؛
         * يجب أن تطبق Resources وPolicies نطاق المركز.
         */
        $centerManagement = [
            'dashboard.view',



            'centers.view',
            'circles.view_own',


            'centers.create_child',
            'centers.update',
            'centers.approve_child',

            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            'roles.view',
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
        ];

        $rolePermissions = [
            'super_admin' => $allPermissions,

            'center_manager' => $centerManagement,

            // لا نمنح مدير المركز القدرة على إنشاء أدوار
            // أو تعديل صلاحياتها من دون ضوابط إضافية.
            'center_admin' => [
                ...$centerManagement,
                'centers.delete',
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
                'guard_name' => self::GUARD,
            ]);

            $permissions = Permission::query()
                ->where('guard_name', self::GUARD)
                ->whereIn('name', array_unique($permissionNames))
                ->get();

            // مزامنة صلاحيات الدور مع القائمة المحددة.
            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // ---------------------------------------------------------
    // 3. المدير العام
    // ---------------------------------------------------------

    private function createSuperAdmin(): User
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
            'guard_name' => self::GUARD,
        ]);

        $user->syncRoles([$role]);

        $this->command?->info(
            'المدير العام: hnarfr20063@gmail.com / 12345678'
        );

        return $user;
    }

    // ---------------------------------------------------------
    // 4. المنطقة التجريبية
    // ---------------------------------------------------------

    private function createDemoRegion(): Region
    {
        /** @var Region $region */
        $region = $this->saveModel(
            Region::class,
            ['code' => 'TRP-DEMO-01'],
            [
                'name' => 'منطقة طرابلس التجريبية',
                'description' => 'منطقة مخصصة لبيانات الاختبار.',
                'is_active' => true,
            ],
        );

        return $region;
    }

    // ---------------------------------------------------------
    // 5. إنشاء المراكز
    // ---------------------------------------------------------

    private function createDemoCenter(
        Region $region,
        string $code,
        string $name,
        ?Center $parent,
        int|string|null $approvedBy,
        int|string|null $parentApprovedBy = null,
        ?string $phone = null,
    ): Center {
        $attributes = [
            'name' => $name,
            'code' => $code,
            'region_id' => $region->getKey(),
            'parent_center_id' => $parent?->getKey(),
            'phone' => $phone ?? '0910000001',
            'email' => strtolower($code) . '@example.com',
            'address' => 'عنوان تجريبي - طرابلس',
            'is_active' => true,

            // استُخدمت القيمة الموجودة في Seeder الأصلي.
            'approval_status' => 'active',

            'approved_at' => now(),
            'approved_by' => $approvedBy,

            'parent_approved_by' => $parentApprovedBy,
            'parent_approved_at' => $parent
                ? now()
                : null,

            'rejection_reason' => null,
        ];

        /** @var Center $center */
        $center = $this->saveModel(
            Center::class,
            ['code' => $code],
            $attributes,
        );

        return $center;
    }

    // ---------------------------------------------------------
    // 6. إنشاء بيانات كل مركز فرعي
    // ---------------------------------------------------------

    private function createBranchData(
        Center $center,
        string $prefix,
    ): void {
        $teachers = [];
        $circles = [];
        $guardians = [];

        $centerCode = strtolower((string) $center->code);

        // إنشاء 7 معلمين وحسابات دخولهم.
        for ($i = 1; $i <= self::TEACHERS_PER_BRANCH; $i++) {
            $number = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $name = "معلم {$prefix} {$i}";
            $email = "demo.{$centerCode}.teacher{$i}@example.com";

            $user = $this->createUser(
                name: $name,
                email: $email,
                password: 'Teacher123!',
                role: 'teacher',
                center: $center,
            );

            /** @var Teacher $teacher */
            $teacher = $this->saveModel(
                Teacher::class,
                [
                    'center_id' => $center->getKey(),
                    'email' => $email,
                ],
                [
                    'center_id' => $center->getKey(),
                    'user_id' => $user->getKey(),
                    'full_name' => $name,
                    'phone' => sprintf(
                        '093%02d%04d',
                        (int) $center->getKey() % 100,
                        $i
                    ),
                    'email' => $email,
                    'qualification' => 'حافظ للقرآن الكريم',
                    'hire_date' => today()->toDateString(),
                    'is_active' => true,
                ],
            );

            $this->attachTeacherToCenter($teacher, $center);

            $teachers[] = $teacher;

            // حلقة واحدة لكل معلم.
            /** @var Circle $circle */
            $circle = $this->saveModel(
                Circle::class,
                [
                    'center_id' => $center->getKey(),
                    'code' => "{$center->code}-CIRCLE-{$number}",
                ],
                [
                    'center_id' => $center->getKey(),
                    'teacher_id' => $teacher->getKey(),
                    'name' => "حلقة {$prefix} {$i}",
                    'code' => "{$center->code}-CIRCLE-{$number}",
                    'level' => match ($i % 3) {
                        0 => 'advanced',
                        1 => 'beginner',
                        default => 'intermediate',
                    },
                    'room' => 'قاعة ' . $i,
                    'capacity' => 20,
                    'starts_at' => sprintf('%02d:00:00', 7 + $i),
                    'ends_at' => sprintf('%02d:00:00', 8 + $i),
                    'days' => ['sunday', 'tuesday', 'thursday'],
                    'is_active' => true,
                ],
            );

            $circles[] = $circle;
        }

        // إنشاء 7 أولياء أمور.
        for ($i = 1; $i <= 7; $i++) {
            $name = "ولي الأمر {$prefix} {$i}";
            $email = "demo.{$centerCode}.guardian{$i}@example.com";
            $phone = sprintf(
                '094%02d%04d',
                (int) $center->getKey() % 100,
                $i
            );

            $user = $this->createUser(
                name: $name,
                email: $email,
                password: 'Guardian123!',
                role: 'guardian',
                center: $center,
            );

            /** @var Guardian $guardian */
            $guardian = $this->saveModel(
                Guardian::class,
                [
                    'center_id' => $center->getKey(),
                    'phone' => $phone,
                ],
                [
                    'center_id' => $center->getKey(),
                    'user_id' => $user->getKey(),
                    'full_name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => 'طرابلس - عنوان تجريبي',
                    'relationship' => 'father',
                    'is_active' => true,
                ],
            );

            $guardians[] = $guardian;
        }

        // إنشاء 7 طلاب وربط كل طالب بولي أمر وحلقة وحضور اليوم.
        for ($i = 1; $i <= 7; $i++) {
            $number = str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            /** @var Student $student */
            $student = $this->saveModel(
                Student::class,
                [
                    'center_id' => $center->getKey(),
                    'student_code' => "{$center->code}-STU-{$number}",
                ],
                [
                    'center_id' => $center->getKey(),
                    'guardian_id' => $guardians[$i - 1]->getKey(),
                    'student_code' => "{$center->code}-STU-{$number}",
                    'full_name' => "الطالب {$prefix} {$i}",
                    'birth_date' => today()
                        ->subYears(8 + ($i % 6))
                        ->toDateString(),
                    'enrollment_date' => today()->toDateString(),
                    'gender' => 'male',
                    'school_grade' => 'المرحلة الابتدائية',
                    'notes' => 'بيانات تجريبية.',
                    'is_active' => true,
                ],
            );

            $circle = $circles[$i - 1];
            $teacher = $teachers[$i - 1];

            $this->attachStudentToCircle($circle, $student);

            // سجل حضور واحد لكل طالب بتاريخ اليوم.
            $this->saveModel(
                AttendanceRecord::class,
                [
                    'student_id' => $student->getKey(),
                    'circle_id' => $circle->getKey(),
                    'attendance_date' => today()->toDateString(),
                ],
                [
                    'center_id' => $center->getKey(),
                    'student_id' => $student->getKey(),
                    'circle_id' => $circle->getKey(),
                    'teacher_id' => $teacher->getKey(),
                    'attendance_date' => today()->toDateString(),
                    'status' => $this->attendanceStatus($i),
                    'notes' => 'سجل حضور تجريبي.',
                ],
            );
        }

        $this->command?->info(
            "اكتملت البيانات التجريبية للمركز: {$center->name}"
        );
    }

    // ---------------------------------------------------------
    // 7. إنشاء المستخدم وربطه بالدور والمركز
    // ---------------------------------------------------------

    private function createUser(
        string $name,
        string $email,
        string $password,
        string $role,
        Center $center,
    ): User {
        $attributes = [
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'center_id' => $center->getKey(),
            'is_active' => true,
            'email_verified_at' => now(),
        ];

        /** @var User $user */
        $user = $this->saveModel(
            User::class,
            ['email' => $email],
            $attributes,
        );

        $roleModel = Role::query()->firstOrCreate([
            'name' => $role,
            'guard_name' => self::GUARD,
        ]);

        $user->syncRoles([$roleModel]);

        return $user;
    }

    // ---------------------------------------------------------
    // 8. ربط مدير المركز بالمركز
    // ---------------------------------------------------------

    private function attachManager(User $user, Center $center): void
    {
        if (method_exists($user, 'managedCenters')) {
            $relation = $user->managedCenters();

            if ($relation instanceof BelongsToMany) {
                $relation->syncWithoutDetaching([
                    $center->getKey(),
                ]);

                return;
            }
        }

        // user.center_id هو الربط الأساسي في تصميم المشروع.
        if (
            Schema::hasColumn($user->getTable(), 'center_id')
            && (string) $user->center_id !== (string) $center->getKey()
        ) {
            $user->forceFill([
                'center_id' => $center->getKey(),
            ])->save();
        }
    }

    // ---------------------------------------------------------
    // 9. ربط المعلم بالمركز عبر center_teacher
    // ---------------------------------------------------------

    private function attachTeacherToCenter(
        Teacher $teacher,
        Center $center,
    ): void {
        if (! method_exists($teacher, 'centers')) {
            return;
        }

        $relation = $teacher->centers();

        if (! $relation instanceof BelongsToMany) {
            return;
        }

        $pivot = [
            'is_active' => true,
            'joined_at' => today()->toDateString(),
        ];

        if (Schema::hasTable('center_teacher')) {
            $pivot = $this->existingColumns('center_teacher', $pivot);
        }

        $relation->syncWithoutDetaching([
            $center->getKey() => $pivot,
        ]);
    }

    // ---------------------------------------------------------
    // 10. ربط الطالب بالحلقة
    // ---------------------------------------------------------

    private function attachStudentToCircle(
        Circle $circle,
        Student $student,
    ): void {
        if (method_exists($circle, 'students')) {
            $relation = $circle->students();

            if ($relation instanceof BelongsToMany) {
                $relation->syncWithoutDetaching([
                    $student->getKey(),
                ]);

                return;
            }
        }

        // دعم تصميم آخر إذا كان students يحتوي circle_id.
        if (Schema::hasColumn('students', 'circle_id')) {
            $student->forceFill([
                'circle_id' => $circle->getKey(),
            ])->save();

            return;
        }

        throw new RuntimeException(
            'تعذر ربط الطالب بالحلقة. أضف علاقة students() '
            . 'في Circle أو تحقق من جدول الربط circle_student.'
        );
    }

    // ---------------------------------------------------------
    // 11. حالات الحضور
    // ---------------------------------------------------------

    private function attendanceStatus(int $number): string
    {
        return match ($number % 4) {
            0 => 'absent',
            1 => 'present',
            2 => 'late',
            default => 'excused',
        };
    }

    // ---------------------------------------------------------
    // 12. حفظ السجلات بصورة قابلة لإعادة التشغيل
    // ---------------------------------------------------------

    private function saveModel(
        string $modelClass,
        array $identity,
        array $attributes,
    ): Model {
        /** @var Model $prototype */
        $prototype = new $modelClass();

        $table = $prototype->getTable();

        if (! Schema::hasTable($table)) {
            throw new RuntimeException(
                "الجدول {$table} غير موجود. شغّل migrations أولًا."
            );
        }

        foreach (array_keys($identity) as $column) {
            if (! Schema::hasColumn($table, $column)) {
                throw new RuntimeException(
                    "العمود {$table}.{$column} غير موجود. "
                    . 'راجع ملف Migration.'
                );
            }
        }

        $identity = $this->existingColumns($table, $identity);
        $attributes = $this->existingColumns($table, $attributes);

        /** @var Model $record */
        $record = $modelClass::query()->firstOrNew($identity);

        $values = array_merge($identity, $attributes);
        $casts = $record->getCasts();

        foreach ($values as $column => $value) {
            if (
                is_array($value)
                && ! array_key_exists($column, $casts)
            ) {
                $values[$column] = json_encode(
                    $value,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
                );
            }
        }

        $record->forceFill($values);
        $record->save();

        return $record;
    }

    // ---------------------------------------------------------
    // 13. فلترة الأعمدة وفق قاعدة البيانات الفعلية
    // ---------------------------------------------------------

    private function existingColumns(
        string $table,
        array $attributes,
    ): array {
        if (! Schema::hasTable($table)) {
            throw new RuntimeException(
                "الجدول {$table} غير موجود."
            );
        }

        $columns = Schema::getColumnListing($table);

        return array_intersect_key(
            $attributes,
            array_flip($columns),
        );
    }
}
