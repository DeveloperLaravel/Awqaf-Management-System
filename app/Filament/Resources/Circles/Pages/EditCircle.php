<?php

declare(strict_types=1);

namespace App\Filament\Resources\Circles\Pages;

use App\Filament\Resources\Circles\CircleResource;
use App\Models\Circle;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EditCircle extends EditRecord
{
    protected static string $resource = CircleResource::class;

    /**
     * التحقق من أن المستخدم يستطيع الوصول إلى الحلقة.
     */
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $user = request()->user();
        /** @var Circle $record */
        $record = $this->getRecord();

        abort_unless($user, 403);

        // المدير العام يستطيع الوصول إلى جميع الحلقات.
        if ($user->hasRole('super_admin')) {
            return;
        }

        // مدير المركز يستطيع الوصول إلى حلقات مركزه فقط.
        if ($user->hasRole('center_manager')) {
            abort_unless(
                filled($user->center_id)
                && (int) $record->center_id === (int) $user->center_id,
                403
            );

            return;
        }

        // المعلم يستطيع الوصول إلى حلقاته المسندة إليه فقط.
        if ($user->hasRole('teacher')) {
            $isAssignedTeacher = $record->teacher()
                ->where('user_id', $user->getKey())
                ->exists();

            abort_unless($isAssignedTeacher, 403);

            return;
        }

        abort(403);
    }

    /**
     * التحقق من صلاحية التعديل.
     */
    protected function authorizeRecordUpdate(Circle $record): void
    {
        $user = request()->user();

        abort_unless($user, 403);

        if ($user->hasRole('super_admin')) {
            return;
        }

        if (
            $user->hasRole('center_manager')
            && filled($user->center_id)
            && (int) $record->center_id === (int) $user->center_id
            && Gate::allows('update', $record)
        ) {
            return;
        }

        if (
            $user->hasRole('teacher')
            && $record->teacher()
                ->where('user_id', $user->getKey())
                ->exists()
            && Gate::allows('update', $record)
        ) {
            return;
        }

        abort(403);
    }

    /**
     * التحقق من السجل قبل حفظ التعديلات.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Circle $record */
        $record = $this->getRecord();

        $this->authorizeRecordUpdate($record);

        $user = request()->user();

        // منع نقل الحلقة إلى مركز آخر بواسطة مدير المركز.
        if ($user?->hasRole('center_manager')) {
            $data['center_id'] = $user->center_id;
        }

        // لا نسمح للمعلم بتغيير المركز أو إسناد الحلقة لمعلم آخر.
        if ($user?->hasRole('teacher')) {
            $data['center_id'] = $record->center_id;
            $data['teacher_id'] = $record->teacher_id;
        }

        return $data;
    }

    /**
     * زر حذف الحلقة.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(function (): bool {
                    $user = request()->user();

                    if (! $user) {
                        return false;
                    }

                    /** @var Circle $record */
                    $record = $this->getRecord();

                    if ($user->hasRole('super_admin')) {
                        return true;
                    }

                    return $user->hasRole('center_manager')
                        && filled($user->center_id)
                        && (int) $record->center_id === (int) $user->center_id
                        && Gate::allows('delete', $record);
                }),
        ];
    }
}
