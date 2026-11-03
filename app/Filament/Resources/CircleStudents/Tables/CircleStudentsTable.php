<?php

declare(strict_types=1);

namespace App\Filament\Resources\CircleStudents\Tables;

use App\Models\CircleStudent;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CircleStudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('center.name')
                    ->label('المركز')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('circle.name')
                    ->label('الحلقة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('joined_at')
                    ->label('تاريخ الانضمام')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('left_at')
                    ->label('تاريخ المغادرة')
                    ->date('Y-m-d')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('حالة التسجيل')
                    ->state(fn (CircleStudent $record): string =>
                        $record->left_at === null ? 'نشط' : 'منتهي'
                    )
                    ->badge()
                    ->color(fn (string $state): string =>
                        $state === 'نشط' ? 'success' : 'gray'
                    ),
            ])
            ->filters([
                SelectFilter::make('center_id')
                    ->label('المركز')
                    ->options(fn () => \App\Filament\Resources\CircleStudents\CircleStudentResource::getAvailableCenters()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),

                SelectFilter::make('status')
                    ->label('حالة التسجيل')
                    ->options([
                        'active' => 'نشط',
                        'ended' => 'منتهي',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'active' => $query->whereNull('left_at'),
                            'ended' => $query->whereNotNull('left_at'),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل'),

                Action::make('endEnrollment')
                    ->label('إنهاء التسجيل')
                    ->icon('heroicon-o-check-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('إنهاء تسجيل الطالب')
                    ->modalDescription('هل تريد إنهاء تسجيل هذا الطالب في الحلقة؟')
                    ->visible(fn (CircleStudent $record): bool =>
                        $record->left_at === null
                    )
                    ->disabled(fn (CircleStudent $record): bool =>
                        $record->joined_at !== null
                        && $record->joined_at->isFuture()
                    )
                    ->action(function (CircleStudent $record): void {
                        $record->update([
                            'left_at' => now()->toDateString(),
                        ]);
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('لا توجد تسجيلات')
            ->emptyStateDescription('ابدأ بإضافة الطلاب إلى الحلقات.');
    }
}
