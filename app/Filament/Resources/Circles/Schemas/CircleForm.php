<?php

namespace App\Filament\Resources\Circles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CircleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('center_id')
                    ->relationship('center', 'name')
                    ->required(),
                Select::make('teacher_id')
                    ->relationship('teacher', 'id')
                    ->default(null),
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('level')
                    ->default(null),
                TextInput::make('room')
                    ->default(null),
                TextInput::make('capacity')
                    ->required()
                    ->numeric()
                    ->default(20),
                TimePicker::make('starts_at'),
                TimePicker::make('ends_at'),
                TextInput::make('days')
                    ->default(null),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
