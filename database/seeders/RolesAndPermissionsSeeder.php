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



    private const TEACHERS_PER_BRANCH = 6;

    private const CIRCLES_PER_BRANCH = 3;

    private const STUDENTS_PER_BRANCH = 9;



    /**

     * توزيع الفروع:

     * المركز الرئيسي الأول: 3 فروع.

     * المركز الرئيسي الثاني: فرعان.

     \*

     * هذه بيانات اختبار، وليست بيانات إنتاج.

     */

    private const ROOTS = [

        [

            'code' => 'CTR-DEMO-001',

            'name' => 'مركز النور الرئيسي الأول',

            'phone' => '0911000001',

            'branches' => [

                [

                    'code' => 'CTR-DEMO-011',

                    'name' => 'مركز النور فرع 1-1',

                ],

                [

                    'code' => 'CTR-DEMO-012',

                    'name' => 'مركز النور فرع 1-2',

                ],

                [

                    'code' => 'CTR-DEMO-013',

                    'name' => 'مركز النور فرع 1-3',

                ],

            ],

        ],

        [

            'code' => 'CTR-DEMO-002',

            'name' => 'مركز النور الرئيسي الثاني',

            'phone' => '0911000002',

            'branches' => [

                [

                    'code' => 'CTR-DEMO-021',

                    'name' => 'مركز النور فرع 2-1',

                ],

                [

                    'code' => 'CTR-DEMO-022',

                    'name' => 'مركز النور فرع 2-2',

                ],

            ],

        ],

    ];



    public function run(): void

    {

        app(PermissionRegistrar::class)->forgetCachedPermissions();



        DB::transaction(function (): void {

            $this->createPermissions();

            $this->createRoles();



            $superAdmin = $this->createSuperAdmin();

            $region = $this->createDemoRegion();



            foreach (self::ROOTS as $rootData) {

                $root = $this->createDemoCenter(

                    region: $region,

                    code: $rootData['code'],

                    name: $rootData['name'],

                    parent: null,

                    approvedBy: $superAdmin->getKey(),

                    phone: $rootData['phone'],

                );



                $rootManager = $this->createUser(

                    name: 'مدير ' . $rootData['name'],

                    email: strtolower($rootData['code'])

                        . '.manager@example.com',

                    password: '12345678',

                    role: 'center_manager',

                    center: $root,

                );



                $this->attachManager($rootManager, $root);



                foreach ($rootData['branches'] as $branchData) {

                    $branch = $this->createDemoCenter(

                        region: $region,

                        code: $branchData['code'],

                        name: $branchData['name'],

                        parent: $root,

                        approvedBy: $superAdmin->getKey(),

                        parentApprovedBy: $rootManager->getKey(),

                        phone: '091' . substr(

                            preg_replace(

                                '/\D/',

                                '',

                                $branchData['code']

                            ) ?: '100000',

                            -7

                        ),

                    );



                    $branchManager = $this->createUser(

                        name: 'مدير ' . $branchData['name'],

                        email: strtolower($branchData['code'])

                            . '.manager@example.com',

                        password: '12345678',

                        role: 'center_manager',

                        center: $branch,

                    );



                    $this->attachManager($branchManager, $branch);



                    $this->createBranchData(

                        center: $branch,

                        prefix: $branchData['code'],

                    );

                }

            }

        });



        app(PermissionRegistrar::class)->forgetCachedPermissions();



        $this->command?->info(

            'اكتمل إعداد المركزين الرئيسيين والخمسة فروع.'

        );



        $this->command?->info(

            'لكل فرع: 6 معلمين، 3 حلقات، 9 طلاب، '

            . '9 أولياء أمور، وسجلات حضور.'

        );



        $this->command?->warn(

            'حسابات الاختبار وكلمات مرورها غير مخصصة للإنتاج.'

        );

    }



    // ---------------------------------------------------------

    // الصلاحيات

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

    // الأدوار

    // ---------------------------------------------------------



    private function createRoles(): void

    {

        $allPermissions = Permission::query()

            ->where('guard_name', self::GUARD)

            ->pluck('name')

            ->all();



        $centerManagement = [

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



            'profile.view',

            'profile.update',

        ];



        $rolePermissions = [

            'super_admin' => $allPermissions,



            'center_manager' => $centerManagement,



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



            // يضبط صلاحيات الأدوار المذكورة أعلاه.

            $role->syncPermissions($permissions);

        }



        app(PermissionRegistrar::class)->forgetCachedPermissions();

    }



    // ---------------------------------------------------------

    // المدير العام

    // ---------------------------------------------------------



    private function createSuperAdmin(): User

    {

        /** @var User $user */

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



        return $user;

    }



    // ---------------------------------------------------------

    // المنطقة

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

    // المراكز الرئيسية والفرعية

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

            'phone' => $phone,

            'email' => strtolower($code) . '@example.com',

            'address' => 'عنوان تجريبي - طرابلس',

            'is_active' => true,



            'approval_status' => 'active',

            'approved_at' => now(),

            'approved_by' => $approvedBy,



            'parent_approved_by' => $parent

                ? $parentApprovedBy

                : null,



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

    // بيانات كل مركز فرعي

    // ---------------------------------------------------------



    private function createBranchData(

        Center $center,

        string $prefix,

    ): void {

        $teachers = [];

        $circles = [];

        $guardians = [];



        $centerCode = strtolower((string) $center->code);



        // 6 معلمين لكل فرع.

        for ($i = 1; $i <= self::TEACHERS_PER_BRANCH; $i++) {

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

                        $i,

                    ),

                    'email' => $email,

                    'qualification' => 'حافظ للقرآن الكريم',

                    'hire_date' => today()->toDateString(),

                    'is_active' => true,

                ],

            );



            $this->attachTeacherToCenter($teacher, $center);

            $teachers[] = $teacher;

        }



        // 3 حلقات لكل فرع؛ كل حلقة لها معلم.

        for ($i = 1; $i <= self::CIRCLES_PER_BRANCH; $i++) {

            $number = str_pad((string) $i, 3, '0', STR_PAD_LEFT);

            $circleCode = "{$center->code}-CIRCLE-{$number}";



            /** @var Circle $circle */

            $circle = $this->saveModel(

                Circle::class,

                [

                    'center_id' => $center->getKey(),

                    'code' => $circleCode,

                ],

                [

                    'center_id' => $center->getKey(),

                    'teacher_id' => $teachers[$i - 1]->getKey(),

                    'name' => "حلقة {$prefix} {$i}",

                    'code' => $circleCode,

                    'level' => match ($i) {

                        1 => 'beginner',

                        2 => 'intermediate',

                        default => 'advanced',

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



        // 9 أولياء أمور لكل فرع.

        for ($i = 1; $i <= self::STUDENTS_PER_BRANCH; $i++) {

            $name = "ولي الأمر {$prefix} {$i}";

            $email = "demo.{$centerCode}.guardian{$i}@example.com";



            $phone = sprintf(

                '094%02d%04d',

                (int) $center->getKey() % 100,

                $i,

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



        // 9 طلاب: 3 طلاب لكل حلقة.

        for ($i = 1; $i <= self::STUDENTS_PER_BRANCH; $i++) {

            $number = str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            $studentCode = "{$center->code}-STU-{$number}";



            /** @var Student $student */

            $student = $this->saveModel(

                Student::class,

                [

                    'center_id' => $center->getKey(),

                    'student_code' => $studentCode,

                ],

                [

                    'center_id' => $center->getKey(),

                    'guardian_id' => $guardians[$i - 1]->getKey(),

                    'student_code' => $studentCode,

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



            // توزيع الطلاب على الحلقات الثلاث.

            $circleIndex = intdiv($i - 1, 3);

            $circle = $circles[$circleIndex];



            $this->attachStudentToCircle($circle, $student);



            // سجل حضور واحد لكل طالب.

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

                    'teacher_id' => $circle->teacher_id,

                    'attendance_date' => today()->toDateString(),

                    'status' => $this->attendanceStatus($i),

                    'notes' => 'سجل حضور تجريبي.',

                ],

            );

        }



        $this->command?->info(

            "اكتملت بيانات الفرع: {$center->name}"

        );

    }



    // ---------------------------------------------------------

    // إنشاء المستخدم وربطه بدوره ومركزه

    // ---------------------------------------------------------



    private function createUser(

        string $name,

        string $email,

        string $password,

        string $role,

        Center $center,

    ): User {

        /** @var User $user */

        $user = User::query()->firstOrNew([

            'email' => $email,

        ]);



        $isNew = ! $user->exists;



        $user->name = $name;

        $user->center_id = $center->getKey();

        // لا نستخدم is_active إلا إذا كان العمود موجودًا في قاعدة البيانات.
        if (Schema::hasColumn('users', 'is_active')) {
            $user->is_active = true;
        }

        if (Schema::hasColumn('users', 'email_verified_at')) {
            $user->email_verified_at ??= now();
        }



        // لا تغيّر كلمة المرور لحساب موجود.

        if ($isNew || blank($user->password)) {

            $user->password = Hash::make($password);

        }



        $user->save();



        $roleModel = Role::query()->firstOrCreate([

            'name' => $role,

            'guard_name' => self::GUARD,

        ]);



        $user->syncRoles([$roleModel]);



        return $user;

    }



    // ---------------------------------------------------------

    // ربط المدير بالمركز

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



        if (

            Schema::hasColumn($user->getTable(), 'center_id')

            && (string) $user->center_id

                !== (string) $center->getKey()

        ) {

            $user->forceFill([

                'center_id' => $center->getKey(),

            ])->save();

        }

    }



    // ---------------------------------------------------------

    // ربط المعلم بالمركز

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

            $pivot = $this->existingColumns(

                'center_teacher',

                $pivot,

            );

        }



        $relation->syncWithoutDetaching([

            $center->getKey() => $pivot,

        ]);

    }



    // ---------------------------------------------------------

    // ربط الطالب بالحلقة

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



        // بديل إذا كان الطلاب مرتبطين عبر students.circle_id.

        if (Schema::hasColumn('students', 'circle_id')) {

            $student->forceFill([

                'circle_id' => $circle->getKey(),

            ])->save();



            return;

        }



        throw new RuntimeException(

            'تعذر ربط الطالب بالحلقة. تحقق من علاقة '

            . 'Circle::students() أو عمود students.circle_id.'

        );

    }



    // ---------------------------------------------------------

    // حالات الحضور

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

    // حفظ السجلات دون إنشاء نسخة مكررة عند إعادة التشغيل

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

                "الجدول {$table} غير موجود. تحقق من migrations."

            );

        }



        foreach (array_keys($identity) as $column) {

            if (! Schema::hasColumn($table, $column)) {

                throw new RuntimeException(

                    "العمود {$table}.{$column} غير موجود."

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

    // استخدام الأعمدة الموجودة فقط

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
