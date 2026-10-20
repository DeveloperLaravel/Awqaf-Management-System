<?php

namespace App\Filament\Resources\MemorizationRecords\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class MemorizationRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('center_id')
                    ->relationship('center', 'name')
                    ->required(),
                Select::make('student_id')
                    ->relationship('student', 'id')
                    ->required(),
                Select::make('circle_id')
                    ->relationship('circle', 'name')
                    ->required(),
                Select::make('teacher_id')
                    ->relationship('teacher', 'id')
                    ->default(null),
                DatePicker::make('record_date')
                    ->required(),
                TextInput::make('record_type')
                    ->required(),
                TextInput::make('surah_name')
                    ->required(),
                TextInput::make('from_ayah')
                    ->required()
                    ->numeric(),
                TextInput::make('to_ayah')
                    ->required()
                    ->numeric(),
                TextInput::make('grade')
                    ->numeric()
                    ->default(null),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
