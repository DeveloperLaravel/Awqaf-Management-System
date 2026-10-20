<?php

namespace App\Filament\Resources\MemorizationRecords\Pages;

use App\Filament\Resources\MemorizationRecords\MemorizationRecordResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
class CreateMemorizationRecord extends CreateRecord
{
    protected static string $resource = MemorizationRecordResource::class;
    protected function mutateFormDataBeforeCreate(
    array $data
): array {
    $user = Auth::user();

    if (! $user) {
        abort(403);
    }

    if (! Gate::allows('centers.view_all')) {
        if (! $user->center_id) {
            abort(403);
        }

        // فرض مركز المستخدم، حتى لو أرسل قيمة مختلفة.
        $data['center_id'] = $user->center_id;
    }

    return $data;
}
}
