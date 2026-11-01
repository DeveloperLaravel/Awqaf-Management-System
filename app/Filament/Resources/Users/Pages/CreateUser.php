<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Center;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * الاحتفاظ بمعرف الدور المحدد حتى يتم إسناده بعد إنشاء المستخدم.
     */
    protected ?int $selectedRoleId = null;

    /**
     * التحقق من البيانات قبل إنشاء المستخدم.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $authUser = Auth::user();

        if (! $authUser instanceof User) {
            abort(403, 'يجب تسجيل الدخول أولاً.');
        }

        /*
        |--------------------------------------------------------------------------
        | التحقق من صلاحية إنشاء المستخدم
        |--------------------------------------------------------------------------
        */

        Gate::authorize('create', User::class);

        $isSuperAdmin = $authUser->hasRole('super_admin');

        /*
        |--------------------------------------------------------------------------
        | تحديد المركز
        |--------------------------------------------------------------------------
        |
        | المدير العام يستطيع اختيار أي مركز موجود.
        | بقية المستخدمين يتم ربط الحساب بمركزهم فقط.
        |
        */

        if (! $isSuperAdmin) {
            if (blank($authUser->center_id)) {
                throw ValidationException::withMessages([
                    'center_id' => 'حسابك غير مرتبط بمركز.',
                ]);
            }

            // منع تغيير المركز عن طريق إرسال طلب يدوي.
            $data['center_id'] = $authUser->center_id;
        }

        $centerId = $data['center_id'] ?? null;

        $centerExists = filled($centerId)
            && Center::query()->whereKey($centerId)->exists();

        if (! $centerExists) {
            throw ValidationException::withMessages([
                'center_id' => 'يجب اختيار مركز صالح.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | التحقق من الدور
        |--------------------------------------------------------------------------
        */

        $roleId = $data['role_id'] ?? null;

        if (! filled($roleId)) {
            throw ValidationException::withMessages([
                'role_id' => 'يجب اختيار دور للمستخدم.',
            ]);
        }

        $roleQuery = Role::query()
            ->where('guard_name', 'web')
            ->whereKey($roleId)

            // لا يُسمح بإنشاء حساب مدير عام من هذا المورد.
            ->where('name', '!=', 'super_admin');

        /*
         * الأدوار المسموح بها للمستخدمين غير المدير العام.
         *
         * يجب أن تتطابق الأسماء مع الأدوار الموجودة في قاعدة البيانات.
         */
        if (! $isSuperAdmin) {
            $roleQuery->whereIn('name', [
                'teacher',
                'accountant',
                'guardian',
                'supervisor',
                'center_manager',
            ]);
        }

        $role = $roleQuery->first();

        if (! $role) {
            throw ValidationException::withMessages([
                'role_id' => 'الدور المحدد غير صالح أو غير مسموح لك بإسناده.',
            ]);
        }

        $this->selectedRoleId = (int) $role->getKey();

        /*
        |--------------------------------------------------------------------------
        | منع إرسال بيانات غير مسموح بها
        |--------------------------------------------------------------------------
        */

        // هذا الحقل غير موجود في جدول users.
        unset($data['role_id']);

        /*
        |--------------------------------------------------------------------------
        | كلمة المرور
        |--------------------------------------------------------------------------
        */

        if (! filled($data['password'] ?? null)) {
            throw ValidationException::withMessages([
                'password' => 'كلمة المرور مطلوبة.',
            ]);
        }

        /*
         * موديل User يحتوي على cast باسم hashed،
         * ولذلك لا نحتاج إلى Hash::make هنا.
         */

        return $data;
    }

    /**
     * إسناد الدور بعد إنشاء المستخدم.
     */
    protected function afterCreate(): void
    {
        if ($this->selectedRoleId === null) {
            throw ValidationException::withMessages([
                'role_id' => 'تعذر تحديد دور المستخدم.',
            ]);
        }

        $role = Role::query()
            ->whereKey($this->selectedRoleId)
            ->where('guard_name', 'web')
            ->where('name', '!=', 'super_admin')
            ->first();

        if (! $role) {
            throw ValidationException::withMessages([
                'role_id' => 'الدور المحدد لم يعد صالحاً.',
            ]);
        }

        // إسناد الدور باستخدام Spatie Permission.
        $this->record->assignRole($role);
    }
}
