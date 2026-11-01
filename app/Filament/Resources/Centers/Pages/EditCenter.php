<?php

declare(strict_types=1);

namespace App\Filament\Resources\Centers\Pages;

use App\Filament\Resources\Centers\CenterResource;
use App\Models\Center;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EditCenter extends EditRecord
{
    protected static string $resource = CenterResource::class;

    /**
     * التحقق من صلاحية الوصول إلى صفحة التعديل.
     */
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $record = $this->getRecord();

        abort_unless(
            $record instanceof Center
            && Gate::forUser($user)->allows('update', $record),
            403,
            'ليس لديك صلاحية تعديل هذا المركز.',
        );
    }

    /**
     * حماية البيانات قبل الحفظ.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $center = $this->getRecord();

        abort_unless($center instanceof Center, 404);

        // التحقق من سياسة تعديل المركز.
        Gate::forUser($user)->authorize('update', $center);

        /*
         * منع تعديل البيانات الإدارية الحساسة
         * من نموذج التعديل العادي.
         */
        unset(
            $data['manager_mode'],
            $data['manager_id'],
            $data['manager_name'],
            $data['manager_email'],
            $data['manager_password'],
            $data['manager_password_confirmation'],

            // الحقول الهرمية.
            $data['parent_center_id'],

            // حقول الاعتماد.
            $data['approval_status'],
            $data['is_active'],
            $data['parent_approved_by'],
            $data['parent_approved_at'],
            $data['approved_by'],
            $data['approved_at'],
            $data['rejection_reason'],
        );

        /*
         * المنطقة:
         * المدير العام يستطيع تعديل منطقة المركز الرئيسي.
         * بقية المستخدمين لا يستطيعون تغييرها.
         */
        if (! $user->hasRole('super_admin')) {
            unset($data['region_id']);
        }

        return $data;
    }

    /**
     * حفظ التعديلات داخل Transaction.
     */
    protected function handleRecordUpdate(
        Model $record,
        array $data,
    ): Model {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        abort_unless($record instanceof Center, 404);

        return DB::transaction(function () use (
            $record,
            $data,
            $user,
        ): Center {

            /*
             * قفل سجل المركز أثناء التعديل.
             */
            $center = Center::query()
                ->whereKey($record->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * إعادة التحقق من الصلاحية قبل الحفظ.
             */
            Gate::forUser($user)->authorize('update', $center);

            /*
             * منع تغيير المنطقة الموروثة للفروع.
             */
            if ($center->parent_center_id !== null) {
                $parent = Center::query()
                    ->whereKey($center->parent_center_id)
                    ->first();

                if (! $parent) {
                    throw ValidationException::withMessages([
                        'parent_center_id' =>
                            'المركز الأب غير موجود.',
                    ]);
                }

                $data['region_id'] = $parent->region_id;
            }

            /*
             * حفظ البيانات المسموح بتعديلها.
             */
            $center->fill($data);
            $center->save();

            /*
             * لا نغير أدوار المديرين هنا.
             *
             * لأن المستخدم قد يدير أكثر من مركز،
             * وتغيير دوره عند تعديل مركز واحد قد يؤثر
             * على صلاحياته في المراكز الأخرى.
             *
             * تعيين الأدوار يتم عند إنشاء المركز
             * أو من خلال إدارة المستخدمين.
             */

            return $center;
        });
    }

    /**
     * إجراءات صفحة تعديل المركز.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف المركز')
                ->icon('heroicon-o-trash')
                ->color('danger')

                /*
                 * صلاحية الحذف تعتمد على CenterPolicy.
                 * السياسة تمنع حذف مركز لديه فروع.
                 */
                ->visible(
                    fn (): bool =>
                        Gate::allows(
                            'delete',
                            $this->getRecord(),
                        )
                )
                ->authorize(
                    fn (): bool =>
                        Gate::allows(
                            'delete',
                            $this->getRecord(),
                        )
                ),
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
