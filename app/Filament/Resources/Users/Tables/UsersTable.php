<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),

                TextColumn::make('center.name')
                    ->label('المركز')
                    ->sortable()
                    ->searchable()
                    ->placeholder('بدون مركز'),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make()
                    ->label('تعديل')
                    ->visible(
                        fn (User $record): bool =>
                            User::query()
                                ->whereKey($record->getKey())
                                ->exists()
                            && \App\Filament\Resources\Users\UserResource::canEdit($record)
                    ),

                DeleteAction::make()
                    ->label('حذف')
                    ->visible(
                        fn (User $record): bool =>
                            \App\Filament\Resources\Users\UserResource::canDelete($record)
                    ),

            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف المحدد')
                        ->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }
}
