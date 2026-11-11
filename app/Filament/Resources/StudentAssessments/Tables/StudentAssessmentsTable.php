<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentAssessments\Tables;

use App\Models\StudentAssessment;
use App\Filament\Resources\StudentAssessments\StudentAssessmentResource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class StudentAssessmentsTable
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
                    ->label('المركز / الفرع')
                    ->sortable(),

                TextColumn::make('circle.name')
                    ->label('الحلقة')
                    ->searchable(),

                TextColumn::make('week_start')
                    ->label('بداية الأسبوع')
                    ->date()
                    ->sortable(),

                TextColumn::make('week_end')
                    ->label('نهاية الأسبوع')
                    ->date(),

                TextColumn::make('total_score')
                    ->label('النتيجة')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('level_label')
                    ->label('التقدير')
                    ->badge()
                    ->color(fn (StudentAssessment $record): string => match ($record->level) {
                        StudentAssessment::LEVEL_EXCELLENT,
                        StudentAssessment::LEVEL_VERY_GOOD => 'success',

                        StudentAssessment::LEVEL_GOOD => 'info',

                        StudentAssessment::LEVEL_ACCEPTABLE => 'warning',

                        StudentAssessment::LEVEL_NEEDS_FOLLOW_UP => 'danger',

                        default => 'gray',
                    }),

                TextColumn::make('assessedBy.name')
                    ->label('المعلم / المقيم')
                    ->searchable(),

            ])
            ->filters([

                SelectFilter::make('center_id')
                    ->label('المركز / الفرع')
                    ->options(fn () => StudentAssessmentResource::availableCenters()),

                SelectFilter::make('level')
                    ->label('التقدير')
                    ->options(StudentAssessment::LEVELS),

            ])
            ->recordActions([

                ViewAction::make(),

                EditAction::make(),

                DeleteAction::make(),

            ])
            ->defaultSort('week_start', 'desc');
    }
}
