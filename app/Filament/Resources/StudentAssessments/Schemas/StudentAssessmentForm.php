<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentAssessments\Schemas;

use App\Filament\Resources\StudentAssessments\StudentAssessmentResource;
use App\Models\Circle;
use App\Models\Student;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use App\Models\StudentAssessment;

class StudentAssessmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('بيانات التقييم')
                ->description('حدد المركز والطالب والحلقة وفترة التقييم.')
                ->columns(2)
                ->schema([

                    Select::make('center_id')
                        ->label('المركز / الفرع')
                        ->options(fn () => StudentAssessmentResource::availableCenters())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (callable $set): void {
                            $set('student_id', null);
                            $set('circle_id', null);
                        }),

                    Select::make('student_id')
                        ->label('الطالب')
                        ->options(fn (Get $get) => $get('center_id')
                            ? Student::query()
                                ->where('center_id', $get('center_id'))
                                ->where('is_active', true)
                                ->orderBy('full_name')
                                ->pluck('full_name', 'id')
                                ->all()
                            : [])
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('circle_id')
                        ->label('الحلقة')
                        ->options(fn (Get $get) => $get('center_id')
                            ? Circle::query()
                                ->where('center_id', $get('center_id'))
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()
                            : [])
                        ->searchable()
                        ->preload()
                        ->required(),

                    DatePicker::make('week_start')
                        ->label('بداية الأسبوع')
                        ->required()
                        ->native(false)
                        ->live(),

                    DatePicker::make('week_end')
                        ->label('نهاية الأسبوع')
                        ->required()
                        ->native(false)
                        ->afterOrEqual('week_start'),

                ]),

            Section::make('درجات التقييم')
                ->description('كل درجة من 100.')
                ->columns(2)
                ->schema([

                    TextInput::make('memorization_score')
                        ->label('الحفظ الجديد (25%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->required(),

                    TextInput::make('review_score')
                        ->label('المراجعة (25%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->required(),

                    TextInput::make('tajweed_score')
                        ->label('التجويد (20%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->required(),

                    TextInput::make('attendance_score')
                        ->label('الحضور (15%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->required(),

                    TextInput::make('discipline_score')
                        ->label('الانضباط والتفاعل (15%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->required(),

                ]),

            Section::make('النتيجة والملاحظات')
                ->columns(2)
                ->schema([

                    TextInput::make('total_score')
                        ->label('النتيجة النهائية')
                        ->disabled()
                        ->dehydrated(false),

                 TextInput::make('level')
    ->label('التقدير')
    ->formatStateUsing(
        fn ($state) => StudentAssessment::LEVELS[$state] ?? $state
    )
    ->disabled()
    ->dehydrated(false),

                    Textarea::make('notes')
                        ->label('ملاحظات المعلم')
                        ->rows(4)
                        ->columnSpanFull(),

                ]),

        ]);
    }
}
