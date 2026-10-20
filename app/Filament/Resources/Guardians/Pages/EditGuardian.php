<?php

namespace App\Filament\Resources\Guardians\Pages;

use App\Filament\Resources\Guardians\GuardianResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditGuardian extends EditRecord
{
    protected static string $resource = GuardianResource::class;

    protected function getHeaderActions(): array
    {
        
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
protected function mutateFormDataBeforeSave(array $data): array
{
    $user = \Illuminate\Support\Facades\Auth::user();

    if (! $user) {
        abort(403);
    }

    // المستخدم المخوّل بالوصول لجميع المراكز
    if (\Illuminate\Support\Facades\Gate::allows('centers.view_all')) {
        $centerId = $data['center_id']
            ?? $this->record->center_id;

        if (
            ! \App\Models\Center::query()
                ->whereKey($centerId)
                ->exists()
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'center_id' => 'يرجى اختيار مركز صحيح.',
            ]);
        }

        $data['center_id'] = $centerId;

        return $data;
    }

    // المستخدم العادي يجب أن يكون مرتبطًا بمركز
    if (! $user->center_id) {
        abort(403, 'حسابك غير مرتبط بمركز.');
    }

    // منع تعديل ولي أمر تابع لمركز آخر
    if (
        (int) $this->record->center_id !==
        (int) $user->center_id
    ) {
        abort(403, 'لا يمكنك تعديل ولي أمر تابع لمركز آخر.');
    }

    // فرض مركز المستخدم من جهة الخادم
    $data['center_id'] = $user->center_id;

    return $data;
}
}
