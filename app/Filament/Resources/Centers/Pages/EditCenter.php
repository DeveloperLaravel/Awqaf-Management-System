<?php

declare(strict_types=1);

namespace App\Filament\Resources\Centers\Pages;

use App\Filament\Resources\Centers\CenterResource;
use App\Models\Center;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class EditCenter extends EditRecord
{
    protected static string $resource = CenterResource::class;

    /**
     * التحقق من صلاحية الوصول إلى صفحة التعديل.
     */
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $user = request()->user();

        abort_unless($user instanceof User, 403);

        $this->authorizeCenterAction($user, 'centers.update');
    }

    /**
     * التحقق من صلاحية تنفيذ العملية على المركز.
     */
    protected function authorizeCenterAction(
        User $user,
        string $permission,
    ): void {
        abort_unless(
            Gate::forUser($user)->allows($permission),
            403,
            'ليست لديك صلاحية تنفيذ هذه العملية.',
        );

        abort_unless(
            app(CenterHierarchy::class)->canUserAccess(
                $user,
                (int) $this->getRecord()->getKey(),
            ),
            403,
            'لا يمكنك تنفيذ هذه العملية على هذا المركز.',
        );
    }

    /**
     * حماية البيانات قبل حفظ التعديلات.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = request()->user();

        abort_unless($user instanceof User, 403);

        $this->authorizeCenterAction($user, 'centers.update');

        // منع تعديل بيانات المدير من النموذج العادي.
        unset(
            $data['manager_mode'],
            $data['manager_id'],
            $data['manager_name'],
            $data['manager_email'],
            $data['manager_password'],
            $data['manager_password_confirmation'],
        );

        // لا نسمح بتغيير التسلسل الهرمي أو الاعتماد من النموذج.
        unset(
            $data['parent_center_id'],
            $data['approval_status'],
            $data['is_active'],
            $data['parent_approved_by'],
            $data['parent_approved_at'],
            $data['approved_by'],
            $data['approved_at'],
            $data['rejection_reason'],
        );

        // المنطقة قابلة للتغيير للمدير العام فقط.
        if (! $user->hasRole('super_admin')) {
            unset($data['region_id']);
        }

        return $data;
    }

    /**
     * حفظ التعديلات وضمان دور مدير المركز.
     */
    protected function handleRecordUpdate(
        \Illuminate\Database\Eloquent\Model $record,
        array $data,
    ): \Illuminate\Database\Eloquent\Model {
        $user = request()->user();

        abort_unless($user instanceof User, 403);

        $this->authorizeCenterAction($user, 'centers.update');

        abort_unless($record instanceof Center, 404);

        return DB::transaction(function () use (
            $record,
            $data,
            $user,
        ): Center {
            /** @var Center $center */
            $center = Center::query()
                ->whereKey($record->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // حفظ البيانات المسموح بتعديلها.
            $center->fill($data);
            $center->save();

            /*
             * تحديد الدور بناءً على نوع المركز المحفوظ.
             *
             * المركز الرئيسي: center_admin
             * المركز الفرعي: center_manager
             */
            $roleName = $center->parent_center_id === null
                ? 'center_admin'
                : 'center_manager';

            /*
             * لا نفترض أن منشئ المركز هو مديره.
             * نزامن الدور مع المستخدمين المرتبطين حاليًا
             * بهذا المركز عبر center_user.
             */
            $managerIds = $center->managedByUsers()
                ->pluck('users.id');

            foreach ($managerIds as $managerId) {
                $manager = User::query()
                    ->whereKey($managerId)
                    ->lockForUpdate()
                    ->first();

                if (! $manager) {
                    continue;
                }

                $this->assignCenterRole($manager, $roleName);
            }

            return $center;
        });
    }

    /**
     * إسناد الدور دون حذف الأدوار الأخرى.
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
     * إجراءات صفحة تعديل المركز.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف المركز')
                ->visible(function (): bool {
                    $user = request()->user();

                    if (! $user instanceof User) {
                        return false;
                    }

                    return Gate::forUser($user)->allows('centers.delete')
                        && app(CenterHierarchy::class)->canUserAccess(
                            $user,
                            (int) $this->getRecord()->getKey(),
                        );
                })
                ->authorize(function (): bool {
                    $user = request()->user();

                    if (! $user instanceof User) {
                        return false;
                    }

                    return Gate::forUser($user)->allows('centers.delete')
                        && app(CenterHierarchy::class)->canUserAccess(
                            $user,
                            (int) $this->getRecord()->getKey(),
                        );
                }),
        ];
    }

    /**
     * العودة إلى قائمة المراكز بعد الحفظ.
     */
    protected function getRedirectUrl(): string
    {
        return CenterResource::getUrl('index');
    }
}
