<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Center;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?int $selectedRoleId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = request()->user();

       if (
    ! $user ||
    (
        ! $user->hasRole('super_admin') &&
        ! $user->hasPermissionTo('Create:User', 'web')
    )
) {
    abort(403, 'ليس لديك صلاحية إنشاء المستخدمين.');
}

        $isSuperAdmin = $user->hasRole('super_admin');

        /*
         * تحديد المركز.
         * المدير العام يختار المركز، أما بقية المستخدمين
         * فيُربط الحساب بمركزهم الحالي فقط.
         */
        if (! $isSuperAdmin) {
            if (blank($user->center_id)) {
                abort(403, 'حسابك غير مرتبط بمركز.');
            }

            $data['center_id'] = $user->center_id;
        }

        $centerId = $data['center_id'] ?? null;

        if (
            ! filled($centerId) ||
            ! Center::query()->whereKey($centerId)->exists()
        ) {
            throw ValidationException::withMessages([
                'center_id' => 'يجب اختيار مركز صالح.',
            ]);
        }

        /*
         * التحقق من الدور على الخادم،
         * وعدم الاكتفاء بالخيارات الظاهرة في النموذج.
         */
        $roleId = $data['role_id'] ?? null;

        $roleQuery = Role::query()
            ->where('guard_name', 'web');

        if (! $isSuperAdmin) {
            $roleQuery->whereIn('name', [
                'center_manager',
                'circle_supervisor',
                'teacher',
                'accountant',
                'guardian',
            ]);
        }

        $role = $roleQuery->whereKey($roleId)->first();

        if (! $role) {
            throw ValidationException::withMessages([
                'role_id' => 'الدور المحدد غير صالح أو غير مسموح به.',
            ]);
        }

        $this->selectedRoleId = (int) $role->getKey();

        // role_id ليس حقلًا في جدول users عادةً.
        unset($data['role_id']);

        /*
         * التحقق من كلمة المرور وتشفيرها.
         */
        if (! filled($data['password'] ?? null)) {
            throw ValidationException::withMessages([
                'password' => 'كلمة المرور مطلوبة.',
            ]);
        }

        $data['password'] = Hash::make($data['password']);

        return $data;
    }

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
            ->firstOrFail();

        // منح الدور فعليًا باستخدام Spatie Permission.
        $this->record->assignRole($role);
    }
}
