<?php

namespace App\Filament\Resources\Regions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RegionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('اسم المنطقة')
                ->required()
                ->maxLength(150),

            TextInput::make('code')
                ->label('رمز المنطقة')
                ->required()
                ->maxLength(50)
                ->unique(ignoreRecord: true),

            Toggle::make('is_active')
                ->label('المنطقة مفعّلة')
                ->default(true),
        ]);
    }
}
