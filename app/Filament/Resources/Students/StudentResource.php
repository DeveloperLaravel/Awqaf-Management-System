<?php

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Students\Pages;
use App\Models\Center;
use App\Models\Student;
use App\Services\CenterHierarchy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $modelLabel = 'طالب';

    protected static ?string $pluralModelLabel = 'الطلاب';

    protected static ?string $navigationLabel = 'الطلاب';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة التعليم';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Section::make('البيانات الأساسية')
                ->schema([
                    Select::make('center_id')
                        ->label('المركز')
                        ->options(fn () => self::availableCenters())
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('student_code')
                        ->label('رقم الطالب')
                        ->required()
                        ->maxLength(50),

                    TextInput::make('full_name')
                        ->label('اسم الطالب')
                        ->required()
                        ->maxLength(255),

                    Select::make('gender')
                        ->label('الجنس')
                        ->options([
                            'male' => 'ذكر',
                            'female' => 'أنثى',
                        ]),

                    DatePicker::make('birth_date')
                        ->label('تاريخ الميلاد')
                        ->native(false),

                    DatePicker::make('enrollment_date')
                        ->label('تاريخ الالتحاق')
                        ->native(false),

                    TextInput::make('school_grade')
                        ->label('المرحلة الدراسية')
                        ->maxLength(100),

                    TextInput::make('phone')
                        ->label('رقم الهاتف')
                        ->tel()
                        ->maxLength(30),

                    Textarea::make('address')
                        ->label('العنوان')
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->label('الطالب نشط')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_code')
                    ->label('رقم الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('full_name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('center.name')
                    ->label('المركز')
                    ->sortable(),

                TextColumn::make('gender')
                    ->label('الجنس')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                        default => 'غير محدد',
                    }),

                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),

                TextColumn::make('enrollment_date')
                    ->label('تاريخ الالتحاق')
                    ->date(),

                IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('center_id')
                    ->label('المركز')
                    ->options(fn () => self::availableCenters()),

                SelectFilter::make('gender')
                    ->label('الجنس')
                    ->options([
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                    ]),

                SelectFilter::make('is_active')
                    ->label('الحالة')
                    ->options([
                        1 => 'نشط',
                        0 => 'غير نشط',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('center_id', self::accessibleCenterIds());
    }

    protected static function accessibleCenterIds(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->hasRole('super_admin')) {
            return Center::query()->pluck('id')->all();
        }

        return app(CenterHierarchy::class)
            ->accessibleIdsForUser($user);
    }

    protected static function availableCenters(): array
    {
        return Center::query()
            ->whereIn('id', self::accessibleCenterIds())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
