<?php

namespace App\Filament\Resources\MemorizationRecords\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MemorizationRecordInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('center.name')
                    ->label('Center'),
                TextEntry::make('student.id')
                    ->label('Student'),
                TextEntry::make('circle.name')
                    ->label('Circle'),
                TextEntry::make('teacher.id')
                    ->label('Teacher')
                    ->placeholder('-'),
                TextEntry::make('record_date')
                    ->date(),
                TextEntry::make('record_type'),
                TextEntry::make('surah_name'),
                TextEntry::make('from_ayah')
                    ->numeric(),
                TextEntry::make('to_ayah')
                    ->numeric(),
                TextEntry::make('grade')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
