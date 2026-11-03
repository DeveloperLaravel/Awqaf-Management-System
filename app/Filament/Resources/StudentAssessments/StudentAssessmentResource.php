<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentAssessments;

use App\Filament\Resources\StudentAssessments\Pages;
use App\Models\Center;
use App\Models\Circle;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Services\CenterHierarchy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;
class StudentAssessmentResource extends Resource
{
    protected static ?string $model = StudentAssessment::class;

protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'تقييمات الطلاب';

    protected static ?string $modelLabel = 'تقييم أسبوعي';

    protected static ?string $pluralModelLabel = 'تقييمات الطلاب';

   protected static UnitEnum|string|null $navigationGroup = 'التقييم والمتابعة';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        $centerIds = app(CenterHierarchy::class)
            ->accessibleIdsForUser($user);

        return $query->whereIn('center_id', $centerIds);
    }

    public static function availableCenters(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $query = Center::query()
            ->where('is_active', true)
            ->where('approval_status', 'active');

        if (! $user->hasRole('super_admin')) {
            $ids = app(CenterHierarchy::class)
                ->accessibleIdsForUser($user);

            $query->whereIn('id', $ids);
        }

        return $query
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات التقييم')
                ->description('حدد المركز والطالب والحلقة وفترة التقييم.')
                ->columns(2)
                ->schema([
                    Select::make('center_id')
                        ->label('المركز / الفرع')
                        ->options(fn () => self::availableCenters())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),

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
                        ->required()
                        ->live(),

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
                ->description('كل درجة من 100، والمجموع يحسب تلقائياً عند الحفظ.')
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
                        ->disabled()
                        ->dehydrated(false),

                    Textarea::make('notes')
                        ->label('ملاحظات المعلم')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
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
                    ->label('الحلقة'),

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
                    ->color(fn (StudentAssessment $record) => match ($record->level) {
                        StudentAssessment::LEVEL_EXCELLENT => 'success',
                        StudentAssessment::LEVEL_VERY_GOOD => 'success',
                        StudentAssessment::LEVEL_GOOD => 'info',
                        StudentAssessment::LEVEL_ACCEPTABLE => 'warning',
                        StudentAssessment::LEVEL_NEEDS_FOLLOW_UP => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('assessedBy.name')
                    ->label('المعلم / المقيم'),
            ])
            ->filters([
                SelectFilter::make('center_id')
                    ->label('المركز')
                    ->options(fn () => self::availableCenters()),

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentAssessments::route('/'),
            'create' => Pages\CreateStudentAssessment::route('/create'),
            'view' => Pages\ViewStudentAssessment::route('/{record}'),
            'edit' => Pages\EditStudentAssessment::route('/{record}/edit'),
        ];
    }
}
