<?php

declare(strict_types=1);

namespace App\Filament\Resources\Centers\Pages;

use App\Filament\Resources\Centers\CenterResource;
use App\Models\Center;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CreateCenter extends CreateRecord
{
    protected static string $resource = CenterResource::class;

    /**
     * هل يتم إنشاء مركز فرعي؟
     */
    protected bool $createAsChild = false;

    /**
     * رقم المركز الأب.
     */
    protected ?int $parentCenterId = null;

    /**
     * طريقة تحديد مدير المركز الرئيسي.
     */
    protected string $managerMode = 'new';

    protected ?int $managerId = null;

    protected ?string $managerName = null;

    protected ?string $managerEmail = null;

    protected ?string $managerPassword = null;

    /**
     * التحقق من صلاحية إنشاء المركز.
     *
     * المدير العام يستطيع إنشاء مركز رئيسي أو فرعي.
     * بقية المستخدمين يستطيعون إنشاء مركز فرعي فقط،
     * بشرط امتلاك صلاحية Create:Center ووجود مركز مرتبط بهم.
     */
    protected function authorizeCenterCreation(User $user): void
    {
        abort_unless(
            $user->hasPermissionTo('Create:Center', 'web'),
            403,
            'ليس لديك صلاحية إنشاء المراكز.',
        );

        if ($user->hasRole('super_admin')) {
            return;
        }

        $hasManagedCenters = $user->managedCenters()->exists();

        abort_unless(
            $user->center_id !== null || $hasManagedCenters,
            403,
            'يجب ربط المستخدم بمركز قبل إنشاء مركز فرعي.',
        );
    }

    /**
     * تجهيز بيانات النموذج قبل الحفظ.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = request()->user();

        abort_unless($user instanceof User, 403);

        $this->authorizeCenterCreation($user);

        $isSuperAdmin = $user->hasRole('super_admin');

        /*
         * المدير العام يختار إنشاء مركز رئيسي أو فرعي.
         * بقية المستخدمين ينشئون مراكز فرعية فقط.
         */
        $this->createAsChild = ! $isSuperAdmin
            || ($data['center_creation_type'] ?? 'root') === 'child';

        /*
         * حفظ رقم المركز الأب قبل حذف الحقل من بيانات المركز.
         */
        $this->parentCenterId = filled($data['parent_center_id'] ?? null)
            ? (int) $data['parent_center_id']
            : null;

        /*
         * بيانات مدير المركز الرئيسي.
         */
        if ($isSuperAdmin && ! $this->createAsChild) {
            $this->managerMode = $data['manager_mode'] ?? 'new';

            $this->managerId = filled($data['manager_id'] ?? null)
                ? (int) $data['manager_id']
                : null;

            $this->managerName = $data['manager_name'] ?? null;
            $this->managerEmail = $data['manager_email'] ?? null;
            $this->managerPassword = $data['manager_password'] ?? null;
        }

        /*
         * منع المستخدم من التحكم في حقول الاعتماد مباشرة.
         * تُحدد هذه الحقول داخل منطق الإنشاء.
         */
        unset(
            $data['center_creation_type'],
            $data['manager_mode'],
            $data['manager_id'],
            $data['manager_name'],
            $data['manager_email'],
            $data['manager_password'],
            $data['manager_password_confirmation'],
            $data['parent_center_id'],
            $data['approval_status'],
            $data['parent_approved_by'],
            $data['parent_approved_at'],
            $data['approved_by'],
            $data['approved_at'],
            $data['rejection_reason'],
        );

        /*
         * المركز الفرعي يرث المنطقة من المركز الأب.
         */
        if (! $isSuperAdmin || $this->createAsChild) {
            unset($data['region_id']);
        }

        /*
         * إنشاء رمز فريد تلقائيًا عند عدم إدخاله.
         */
        if (blank($data['code'] ?? null)) {
            do {
                $data['code'] = 'CENTER-' . Str::upper(Str::random(8));
            } while (
                Center::query()
                    ->where('code', $data['code'])
                    ->exists()
            );
        }

        return $data;
    }

    /**
     * إنشاء المركز وربطه بالمدير وتعيين الدور.
     */
    protected function handleRecordCreation(array $data): Center
    {
        $user = request()->user();

        abort_unless($user instanceof User, 403);

        /*
         * إعادة التحقق من الصلاحية عند الحفظ.
         */
        $this->authorizeCenterCreation($user);

        $isSuperAdmin = $user->hasRole('super_admin');

        $isRootCenter = $isSuperAdmin && ! $this->createAsChild;

        return DB::transaction(function () use (
            $data,
            $user,
            $isSuperAdmin,
            $isRootCenter,
        ): Center {
            $parent = null;

            /*
             * الحالة الأولى: إنشاء مركز رئيسي.
             */
            if ($isRootCenter) {
                if (blank($data['region_id'] ?? null)) {
                    throw ValidationException::withMessages([
                        'region_id' => 'يرجى اختيار المنطقة للمركز الرئيسي.',
                    ]);
                }

                $data['parent_center_id'] = null;
                $data['approval_status'] = 'active';
                $data['is_active'] = true;

                $data['parent_approved_by'] = null;
                $data['parent_approved_at'] = null;

                $data['approved_by'] = $user->id;
                $data['approved_at'] = now();

                $data['rejection_reason'] = null;
            } else {
                /*
                 * الحالة الثانية: إنشاء مركز فرعي.
                 */
                if ($this->parentCenterId === null) {
                    throw ValidationException::withMessages([
                        'parent_center_id' =>
                            'يرجى اختيار المركز الأب للمركز الفرعي.',
                    ]);
                }

                /*
                 * المستخدم العادي لا يستطيع إنشاء مركز
                 * إلا تحت مركز يديره مباشرة.
                 */
                if (! $isSuperAdmin) {
                    $directParentIds = app(CenterHierarchy::class)
                        ->directlyManagedCenters($user)
                        ->modelKeys();

                    abort_unless(
                        in_array(
                            $this->parentCenterId,
                            array_map('intval', $directParentIds),
                            true,
                        ),
                        403,
                        'لا يمكنك إنشاء مركز تابع لمركز لا تديره مباشرة.',
                    );
                }

                /*
                 * قفل المركز الأب والتحقق من اعتماده.
                 */
                $parent = Center::query()
                    ->whereKey($this->parentCenterId)
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $parent
                    || ! $parent->is_active
                    || $parent->approval_status !== 'active'
                    || $parent->approved_at === null
                ) {
                    throw ValidationException::withMessages([
                        'parent_center_id' =>
                            'المركز الأب غير معتمد أو غير مفعل.',
                    ]);
                }

                /*
                 * ربط المركز الفرعي بالأب ووراثة منطقته.
                 */
                $data['parent_center_id'] = $parent->id;
                $data['region_id'] = $parent->region_id;

                /*
                 * انتظار موافقة المركز الأب.
                 */
                $data['approval_status'] = 'pending_parent_approval';
                $data['is_active'] = false;

                $data['parent_approved_by'] = null;
                $data['parent_approved_at'] = null;

                $data['approved_by'] = null;
                $data['approved_at'] = null;

                $data['rejection_reason'] = null;
            }

            /*
             * تحديد المدير والدور.
             *
             * المركز الرئيسي: مديره center_admin.
             * المركز الفرعي: منشئه center_manager.
             */
            if ($isRootCenter) {
                $manager = $this->resolveRootCenterManager();
                $roleName = 'center_admin';
            } else {
                $manager = $user;
                $roleName = 'center_manager';
            }

            /*
             * حفظ المركز.
             */
            $center = new Center();
            $center->forceFill($data);
            $center->save();

            /*
             * ربط المركز بالمدير دون حذف ارتباطاته السابقة.
             */
            $manager->managedCenters()
                ->syncWithoutDetaching([$center->id]);

            /*
             * إضافة الدور المطلوب دون حذف الأدوار الأخرى.
             */
            $this->assignCenterRole($manager, $roleName);

            /*
             * تعيين المركز الافتراضي إن لم يكن محددًا.
             */
            if ($manager->center_id === null) {
                $manager->forceFill([
                    'center_id' => $center->id,
                ])->save();
            }

            return $center;
        });
    }
    protected function getRedirectUrl(): string
{
    return CenterResource::getUrl('index');
}

    /**
     * اختيار مدير موجود أو إنشاء حساب جديد للمركز الرئيسي.
     */
    protected function resolveRootCenterManager(): User
    {
        /*
         * الخيار الأول: مدير موجود.
         */
        if ($this->managerMode === 'existing') {
            if ($this->managerId === null) {
                throw ValidationException::withMessages([
                    'manager_id' => 'يرجى اختيار مدير المركز.',
                ]);
            }

            $manager = User::query()
                ->whereKey($this->managerId)
                ->lockForUpdate()
                ->first();

            if (! $manager) {
                throw ValidationException::withMessages([
                    'manager_id' => 'حساب المدير المحدد غير موجود.',
                ]);
            }

            if (! $manager->hasAnyRole([
                'center_manager',
                'center_admin',
            ])) {
                throw ValidationException::withMessages([
                    'manager_id' =>
                        'المستخدم المحدد ليس لديه دور مدير مركز.',
                ]);
            }

            return $manager;
        }

        /*
         * الخيار الثاني: إنشاء حساب مدير جديد.
         */
        if (
            blank($this->managerName)
            || blank($this->managerEmail)
            || blank($this->managerPassword)
        ) {
            throw ValidationException::withMessages([
                'manager_name' => 'يرجى إدخال اسم المدير.',
                'manager_email' => 'يرجى إدخال بريد المدير.',
                'manager_password' => 'يرجى إدخال كلمة مرور المدير.',
            ]);
        }

        if (
            User::query()
                ->where('email', $this->managerEmail)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'manager_email' => 'البريد الإلكتروني مستخدم بالفعل.',
            ]);
        }

        $manager = User::query()->create([
            'name' => $this->managerName,
            'email' => $this->managerEmail,
            'password' => Hash::make($this->managerPassword),
        ]);

        return $manager;
    }

    /**
     * إضافة الدور المطلوب للمستخدم.
     */
    protected function assignCenterRole(
        User $user,
        string $roleName,
    ): void {
        $role = Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
