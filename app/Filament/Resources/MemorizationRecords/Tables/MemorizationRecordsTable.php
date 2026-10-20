<?php

namespace App\Filament\Resources\MemorizationRecords\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MemorizationRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('center.name')
                    ->searchable(),
                TextColumn::make('student.id')
                    ->searchable(),
                TextColumn::make('circle.name')
                    ->searchable(),
                TextColumn::make('teacher.id')
                    ->searchable(),
                TextColumn::make('record_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('record_type')
                    ->searchable(),
                TextColumn::make('surah_name')
                    ->searchable(),
                TextColumn::make('from_ayah')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('to_ayah')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('grade')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
