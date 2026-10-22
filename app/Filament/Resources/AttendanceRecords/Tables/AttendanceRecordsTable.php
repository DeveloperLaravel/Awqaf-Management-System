<?php

declare(strict_types=1);

namespace App\Filament\Resources\AttendanceRecords\Tables;

use App\Models\AttendanceRecord;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AttendanceRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('center.name')
                    ->label('المركز')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('student.full_name')
                    ->label('الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('circle.name')
                    ->label('الحلقة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('teacher.user.name')
                    ->label('المعلم')
                    ->placeholder('غير محدد')
                    ->searchable(),

                TextColumn::make('attendance_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        AttendanceRecord::STATUS_PRESENT => 'حاضر',
                        AttendanceRecord::STATUS_ABSENT => 'غائب',
                        AttendanceRecord::STATUS_LATE => 'متأخر',
                        AttendanceRecord::STATUS_EXCUSED => 'غائب بعذر',
                        default => 'غير محدد',
                    })
                    ->badge()
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(40)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('حالة الحضور')
                    ->options([
                        AttendanceRecord::STATUS_PRESENT => 'حاضر',
                        AttendanceRecord::STATUS_ABSENT => 'غائب',
                        AttendanceRecord::STATUS_LATE => 'متأخر',
                        AttendanceRecord::STATUS_EXCUSED => 'غائب بعذر',
                    ]),

                SelectFilter::make('center_id')
                    ->label('المركز')
                    ->relationship('center', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('circle_id')
                    ->label('الحلقة')
                    ->relationship('circle', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل'),
            ]);
    }
}
