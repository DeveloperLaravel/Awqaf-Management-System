<?php

namespace App\Filament\Resources\MemorizationRecords\Pages;

use App\Filament\Resources\MemorizationRecords\MemorizationRecordResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMemorizationRecord extends ViewRecord
{
    protected static string $resource = MemorizationRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
