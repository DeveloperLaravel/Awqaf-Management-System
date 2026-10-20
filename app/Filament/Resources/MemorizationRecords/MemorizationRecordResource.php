<?php

namespace App\Filament\Resources\MemorizationRecords;

use App\Filament\Resources\MemorizationRecords\Pages\CreateMemorizationRecord;
use App\Filament\Resources\MemorizationRecords\Pages\EditMemorizationRecord;
use App\Filament\Resources\MemorizationRecords\Pages\ListMemorizationRecords;
use App\Filament\Resources\MemorizationRecords\Pages\ViewMemorizationRecord;
use App\Filament\Resources\MemorizationRecords\Schemas\MemorizationRecordForm;
use App\Filament\Resources\MemorizationRecords\Schemas\MemorizationRecordInfolist;
use App\Filament\Resources\MemorizationRecords\Tables\MemorizationRecordsTable;
use App\Models\MemorizationRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MemorizationRecordResource extends Resource
{
    protected static ?string $model = MemorizationRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'surah_name';

    public static function form(Schema $schema): Schema
    {
        return MemorizationRecordForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MemorizationRecordInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MemorizationRecordsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMemorizationRecords::route('/'),
            'create' => CreateMemorizationRecord::route('/create'),
            'view' => ViewMemorizationRecord::route('/{record}'),
            'edit' => EditMemorizationRecord::route('/{record}/edit'),
        ];
    }
}
