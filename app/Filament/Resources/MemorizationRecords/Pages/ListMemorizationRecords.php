<?php

namespace App\Filament\Resources\MemorizationRecords\Pages;

use App\Filament\Resources\MemorizationRecords\MemorizationRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMemorizationRecords extends ListRecords
{
    protected static string $resource = MemorizationRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
