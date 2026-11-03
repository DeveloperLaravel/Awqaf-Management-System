<?php

namespace App\Filament\Resources\Teachers;

use App\Filament\Resources\Teachers\Pages;
use App\Models\Center;
use App\Models\Teacher;
use App\Services\CenterHierarchy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static ?string $modelLabel = 'معلم';

    protected static ?string $pluralModelLabel = 'المعلمين';

    protected static ?string $navigationLabel = 'المعلمين';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة التعليم';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Section::make('البيانات الشخصية')
                ->schema([
                    TextInput::make('full_name')
                        ->label('اسم المعلم')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('phone')
                        ->label('رقم الهاتف')
                        ->tel()
                        ->maxLength(30),

                    TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->maxLength(255),

                    TextInput::make('qualification')
                        ->label('المؤهل العلمي')
                        ->maxLength(255),

                    DatePicker::make('hire_date')
                        ->label('تاريخ التعيين')
                        ->native(false),

                    Toggle::make('is_active')
                        ->label('المعلم نشط')
                        ->default(true),
                ])
                ->columns(2),

            \Filament\Schemas\Components\Section::make('المراكز')
                ->description('حدد المركز الأساسي والمراكز الأخرى التي يعمل بها المعلم.')
                ->schema([
                    Select::make('center_id')
                        ->label('المركز الأساسي')
                        ->options(fn () => self::availableCenters())
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('centers')
                        ->label('المراكز الأخرى')
                        ->relationship(
                            name: 'centers',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query) =>
                                $query->whereIn(
                                    'centers.id',
                                    self::accessibleCenterIds()
                                )
                        )
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->saveRelationshipsUsing(
                            fn (Teacher $record, $state) =>
                                $record->centers()->syncWithPivotValues(
                                    $state ?? [],
                                    ['is_active' => true]
                                )
                        ),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('اسم المعلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('center.name')
                    ->label('المركز الأساسي'),

                TextColumn::make('centers.name')
                    ->label('المراكز المرتبط بها')
                    ->listWithLineBreaks(),

                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),

                TextColumn::make('qualification')
                    ->label('المؤهل'),

                IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('center_id')
                    ->label('المركز الأساسي')
                    ->options(fn () => self::availableCenters()),

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
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        $centerIds = self::accessibleCenterIds();

        return $query->where(function (Builder $query) use ($centerIds) {
            $query->whereIn('center_id', $centerIds)
                ->orWhereHas('centers', function (Builder $query) use ($centerIds) {
                    $query->whereIn('centers.id', $centerIds);
                });
        });
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
            'index' => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'view' => Pages\ViewTeacher::route('/{record}'),
            'edit' => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }
}
