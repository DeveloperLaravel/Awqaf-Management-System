<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Models\Center;
use App\Models\Guardian;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;
    

    /**
     * التحقق من البيانات قبل تحديث الطالب.
     */
      /**
     * التحقق من البيانات قبل تحديث الطالب.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();

        // التأكد من تسجيل الدخول.
        abort_unless($user, 403);

        // التأكد من امتلاك صلاحية تعديل الطلاب.
        abort_unless(Gate::allows('students.update'), 403);

        /*
         * مدير النظام يستطيع تحديد مركز الطالب،
         * بشرط أن يكون المركز موجودًا.
         */
        if (Gate::allows('centers.view_all')) {
            $centerId = $data['center_id']
                ?? $this->record->center_id;

            $centerExists = Center::query()
                ->whereKey($centerId)
                ->exists();

            if (! $centerExists) {
                throw ValidationException::withMessages([
                    'center_id' => 'يرجى اختيار مركز صحيح.',
                ]);
            }

            $data['center_id'] = $centerId;
        } else {
            /*
             * المستخدم العادي لا يستطيع نقل الطالب
             * إلى مركز آخر أو تعديل طالب خارج مركزه.
             */
            abort_unless($user->center_id, 403);

            abort_unless(
                (int) $this->record->center_id ===
                (int) $user->center_id,
                403
            );

            // فرض مركز المستخدم من جهة الخادم.
            $data['center_id'] = $user->center_id;
        }

        /*
         * التأكد من أن ولي الأمر يتبع مركز الطالب نفسه.
         */
        if (! empty($data['guardian_id'])) {
            $guardianExists = Guardian::query()
                ->whereKey($data['guardian_id'])
                ->where('center_id', $data['center_id'])
                ->exists();

            if (! $guardianExists) {
                throw ValidationException::withMessages([
                    'guardian_id' =>
                        'ولي الأمر المحدد لا يتبع مركز الطالب.',
                ]);
            }
        }

        return $data;
    }

    /**
     * أزرار الإجراءات أعلى صفحة التعديل.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('حذف الطالب')
                ->visible(
                    fn (): bool =>
                        StudentResource::canDelete($this->record)
                ),
        ];
    }
}
