<?php

namespace App\Filament\Resources\MemorizationRecords\Pages;

use App\Filament\Resources\MemorizationRecords\MemorizationRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMemorizationRecord extends EditRecord
{
    protected static string $resource = MemorizationRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
