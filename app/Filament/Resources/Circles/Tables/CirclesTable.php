<?php

declare(strict_types=1);

namespace App\Filament\Resources\Circles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CirclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الحلقة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('code')
                    ->label('رمز الحلقة')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('center.name')
                    ->label('المركز')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('teacher.full_name')
                    ->label('المعلم')
                    ->searchable()
                    ->sortable()
                    ->placeholder('غير محدد'),

                TextColumn::make('level')
                    ->label('المستوى')
                    ->searchable()
                    ->sortable()
                    ->placeholder('غير محدد'),

                TextColumn::make('room')
                    ->label('القاعة')
                    ->searchable()
                    ->placeholder('غير محددة')
                    ->toggleable(),

                TextColumn::make('capacity')
                    ->label('السعة')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label('وقت البداية')
                    ->time('H:i')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('ends_at')
                    ->label('وقت النهاية')
                    ->time('H:i')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('days')
                    ->label('أيام الحلقة')
                    ->formatStateUsing(
                        fn ($state): string => is_array($state)
                            ? implode('، ', $state)
                            : (string) ($state ?? '—')
                    )
                    ->placeholder('—')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('حالة الحلقة')
                    ->options([
                        '1' => 'نشطة',
                        '0' => 'غير نشطة',
                    ]),

                SelectFilter::make('level')
                    ->label('المستوى')
                    ->options(fn (): array =>
                        \App\Models\Circle::query()
                            ->whereNotNull('level')
                            ->distinct()
                            ->orderBy('level')
                            ->pluck('level', 'level')
                            ->all()
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->icon('heroicon-o-pencil-square'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('name')
            ->striped();
    }
}
