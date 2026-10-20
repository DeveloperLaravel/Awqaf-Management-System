<?php

namespace App\Filament\Resources\Regions\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RegionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم المنطقة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label('الرمز')
                    ->searchable(),

                TextColumn::make('centers_count')
                    ->label('عدد المراكز')
                    ->counts('centers')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('حالة المنطقة'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل'),

                ViewAction::make()
                    ->label('عرض'),
            ])
            ->defaultSort('name');
    }
}
