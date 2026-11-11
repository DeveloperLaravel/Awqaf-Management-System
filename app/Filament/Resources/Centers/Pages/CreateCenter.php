<?php

declare(strict_types=1);

namespace App\Filament\Resources\Centers\Pages;

use App\Filament\Resources\Centers\CenterResource;
use App\Models\Center;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
class CreateCenter extends CreateRecord
{
    protected static string $resource = CenterResource::class;

    protected bool $createAsChild = false;

    protected ?int $parentCenterId = null;

    protected string $managerMode = 'new';

    protected ?int $managerId = null;

    protected ?string $managerName = null;

    protected ?string $managerEmail = null;

    protected ?string $managerPassword = null;

    /**
     * التحقق من صلاحية إنشاء المركز.
     */
    protected function authorizeCenterCreation(User $user): void
    {
        Gate::forUser($user)->authorize('create', Center::class);
    }

    /**
     * تجهيز بيانات النموذج قبل الحفظ.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $this->authorizeCenterCreation($user);

        $isSuperAdmin = $user->hasRole('super_admin');

        // المدير العام يختار نوع المركز.
        // بقية المستخدمين ينشئون فروعاً فقط.
        $this->createAsChild = ! $isSuperAdmin
            || ($data['center_creation_type'] ?? 'root') === 'child';

        // حفظ المركز الأب.
        $this->parentCenterId = filled($data['parent_center_id'] ?? null)
            ? (int) $data['parent_center_id']
            : null;

        // بيانات مدير المركز الرئيسي.
        if ($isSuperAdmin && ! $this->createAsChild) {
            $this->managerMode = $data['manager_mode'] ?? 'new';

            $this->managerId = filled($data['manager_id'] ?? null)
                ? (int) $data['manager_id']
                : null;

            $this->managerName = $data['manager_name'] ?? null;
            $this->managerEmail = $data['manager_email'] ?? null;
            $this->managerPassword = $data['manager_password'] ?? null;
        }

        // منع التلاعب بحقول الاعتماد.
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

        // المنطقة لا يحددها مدير الفرع.
        if (! $isSuperAdmin || $this->createAsChild) {
            unset($data['region_id']);
        }

        // توليد رمز المركز تلقائياً.
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
     * إنشاء المركز وربطه بالمدير.
     */
    protected function handleRecordCreation(array $data): Center
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $this->authorizeCenterCreation($user);

        $isSuperAdmin = $user->hasRole('super_admin');
        // super_admin ينشئ مراكز رئيسية فقط.
        // center_admin و center_manager ينشئان فروعاً فقط.
         $this->createAsChild = ! $isSuperAdmin;

        $isRootCenter = $isSuperAdmin;

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
            }

            /*
             * الحالة الثانية: إنشاء مركز فرعي.
             */
            else {
                if ($this->parentCenterId === null) {
                    throw ValidationException::withMessages([
                        'parent_center_id' =>
                            'يرجى اختيار المركز الأب.',
                    ]);
                }

// جلب المركز الأب مع قفل السجل أثناء الإنشاء.
$parent = Center::query()
    ->whereKey($this->parentCenterId)
    ->lockForUpdate()
    ->first();

if (! $parent) {
    throw ValidationException::withMessages([
        'parent_center_id' => 'المركز الأب غير موجود.',
    ]);
}

// تطبيق صلاحية إنشاء فرع تحت هذا المركز تحديداً.
Gate::forUser($user)->authorize('createChild', $parent);

// التأكد من أن المركز الأب معتمد ونشط.

if (
    ! $parent->is_active
    || $parent->approval_status !== 'active'
    || $parent->approved_at === null
) {
    throw ValidationException::withMessages([
        'parent_center_id' => 'المركز الأب غير معتمد أو غير مفعل.',
    ]);
}
// وراثة المنطقة من المركز الأب.
$data['parent_center_id'] = $parent->id;
$data['region_id'] = $parent->region_id;
// الفرع ينتظر موافقة المركز الأب.
$data['approval_status'] = 'pending_parent_approval';
$data['is_active'] = false;

$data['parent_approved_by'] = null;
$data['parent_approved_at'] = null;
$data['approved_by'] = null;
$data['approved_at'] = null;
$data['rejection_reason'] = null;
    
            }

           
           /*
 * تحديد مدير المركز.
 */
if ($isRootCenter) {

    // المركز الرئيسي: نحدد له مديراً بدور center_admin.
    $manager = $this->resolveRootCenterManager();

    $roleName = 'center_admin';

} else {

    // الفرع: المستخدم الذي أنشأه يصبح مديره.
    $manager = $user;

    // لا نضيف دوراً عاماً جديداً للمستخدم.
    $roleName = null;
}


            /*
             * حفظ المركز.
             */
            $center = new Center();

            $center->forceFill($data);

            $center->save();

            /*
             * ربط المركز بالمدير.
             */
            $manager->managedCenters()
                ->syncWithoutDetaching([
                    $center->id,
                ]);

         /*
 * تعيين دور مدير المركز الرئيسي فقط.
 */
if ($roleName !== null) {
    $this->assignCenterRole($manager, $roleName);
}
            /*
             * تعيين المركز الافتراضي إذا لم يكن موجوداً.
             */
            if ($manager->center_id === null) {
                $manager->forceFill([
                    'center_id' => $center->id,
                ])->save();
            }

            return $center;
        });
    }

    /**
     * تحديد مدير المركز الأب عند إنشاء فرع بواسطة المدير العام.
     */
    protected function resolveParentCenterManager(Center $parent): User
    {
        $manager = $parent->users()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', [
                    'center_admin',
                    'center_manager',
                ])->where('guard_name', 'web');
            })
            ->lockForUpdate()
            ->first();

        if (! $manager) {
            throw ValidationException::withMessages([
                'parent_center_id' =>
                    'لا يوجد مدير مرتبط بالمركز الأب. يجب تعيين مدير أولاً.',
            ]);
        }

        return $manager;
    }

    /**
     * اختيار مدير موجود أو إنشاء حساب جديد للمركز الرئيسي.
     */
    public static function getActiveCenterIds(): array
{
    $user = auth()->user();

    if (! $user instanceof User) {
        return [];
    }

    return app(CenterHierarchy::class)
        ->activeAccessibleIdsForUser($user);
}
    protected function resolveRootCenterManager(): User
    {
        // مدير موجود.
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
                    'manager_id' => 'حساب المدير غير موجود.',
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

        // إنشاء مدير جديد.
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
                'manager_email' =>
                    'البريد الإلكتروني مستخدم بالفعل.',
            ]);
        }

        return User::query()->create([
            'name' => $this->managerName,
            'email' => $this->managerEmail,
            'password' => Hash::make($this->managerPassword),
        ]);
    }

    /**
     * إضافة الدور للمستخدم.
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

    /**
     * العودة إلى قائمة المراكز بعد الحفظ.
     */
    protected function getRedirectUrl(): string
    {
        return CenterResource::getUrl('index');
    }
}
