<?php

declare(strict_types=1);

namespace App\Filament\Resources\AttendanceRecords\Tables;

use App\Models\AttendanceRecord;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
class AttendanceRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
             ->columns([

                TextColumn::make('student.full_name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('center.name')
                    ->label('المركز')
                    ->sortable(),

                TextColumn::make('circle.name')
                    ->label('الحلقة')
                    ->placeholder('غير محددة'),

                TextColumn::make('teacher.full_name')
                    ->label('المعلم')
                    ->placeholder('غير محدد'),

                TextColumn::make('attendance_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'present' => 'حاضر',
                        'absent' => 'غائب',
                        'late' => 'متأخر',
                        'excused' => 'غياب بعذر',
                        default => $state ?? 'غير محدد',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'present' => 'success',
                        'absent' => 'danger',
                        'late' => 'warning',
                        'excused' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('حالة الحضور')
                    ->options([
                        'present' => 'حاضر',
                        'absent' => 'غائب',
                        'late' => 'متأخر',
                        'excused' => 'غياب بعذر',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
