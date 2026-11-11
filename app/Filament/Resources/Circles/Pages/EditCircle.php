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
     * حماية صفحة التعديل.
     */
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $record = $this->getRecord();

        abort_unless($record instanceof Circle, 404);

        // لا يكفي امتلاك صلاحية العرض لفتح صفحة التعديل.
        Gate::authorize('update', $record);
    }

    /**
     * التحقق قبل حفظ التعديلات.
     */
   protected function mutateFormDataBeforeSave(array $data): array
{
    $user = auth()->user();

    abort_unless($user, 403);

    $record = $this->getRecord();

    Gate::authorize('update', $record);

    app(CircleAssignmentValidator::class)->validate(
        $user,
        $data,
        $record
    );

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
