<?php

namespace App\Filament\Resources\Circles\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CircleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('center.name')
                    ->label('Center'),
                TextEntry::make('teacher.id')
                    ->label('Teacher')
                    ->placeholder('-'),
                TextEntry::make('name'),
                TextEntry::make('code'),
                TextEntry::make('level')
                    ->placeholder('-'),
                TextEntry::make('room')
                    ->placeholder('-'),
                TextEntry::make('capacity')
                    ->numeric(),
                TextEntry::make('starts_at')
                    ->time()
                    ->placeholder('-'),
                TextEntry::make('ends_at')
                    ->time()
                    ->placeholder('-'),
                TextEntry::make('days')
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
