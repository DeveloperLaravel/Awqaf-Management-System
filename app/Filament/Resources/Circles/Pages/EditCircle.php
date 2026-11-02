<?php

declare(strict_types=1);

namespace App\Filament\Resources\Circles\Pages;

use App\Filament\Resources\Circles\CircleResource;
use App\Models\Circle;
use App\Services\CircleAssignmentValidator;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditCircle extends EditRecord
{
    protected static string $resource = CircleResource::class;

    /**
     * التحقق من صلاحية الوصول إلى صفحة التعديل.
     *
     * يعتمد على CirclePolicy للتحقق من صلاحية العرض.
     */
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $record = $this->getRecord();

        abort_unless(
            $record instanceof Circle,
            404
        );

        Gate::authorize('view', $record);
    }

    /**
     * التحقق من صلاحية تعديل الحلقة قبل الحفظ.
     */
    protected function authorizeRecordUpdate(Circle $record): void
    {
        Gate::authorize('update', $record);
    }

    /**
     * التحقق من البيانات قبل حفظ التعديلات.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Circle $record */
        $record = $this->getRecord();

        // التأكد من أن المستخدم يملك صلاحية تعديل هذه الحلقة.
        $this->authorizeRecordUpdate($record);

        $user = auth()->user();

        abort_unless($user, 403);

        // التحقق من المركز والمعلم قبل الحفظ.
        app(CircleAssignmentValidator::class)
            ->validate($user, $data);

        return $data;
    }

    /**
     * زر حذف الحلقة.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool =>
                    Gate::allows('delete', $this->getRecord())
                ),
        ];
    }
}
