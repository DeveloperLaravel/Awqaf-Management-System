<?php

declare(strict_types=1);

namespace App\Filament\Resources\MemorizationRecords;

use App\Filament\Resources\MemorizationRecords\Pages;
use App\Models\Center;
use App\Models\Circle;
use App\Models\MemorizationRecord;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MemorizationRecordResource extends Resource
{
    protected static ?string $model = MemorizationRecord::class;

    protected static ?string $modelLabel = 'سجل تسميع';

    protected static ?string $pluralModelLabel = 'سجلات التسميع';

    protected static ?string $navigationLabel = 'التسميع والمراجعة';

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-book-open';

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return 'إدارة الحلقات';
    }

    // ---------------------------------------------------------
    // الصلاحيات
    // ---------------------------------------------------------

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && Gate::forUser($user)->allows(
                'viewAny',
                MemorizationRecord::class
            );
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof MemorizationRecord
            && Gate::forUser($user)->allows('view', $record);
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && Gate::forUser($user)->allows(
                'create',
                MemorizationRecord::class
            );
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof MemorizationRecord
            && Gate::forUser($user)->allows('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof MemorizationRecord
            && Gate::forUser($user)->allows('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    // ---------------------------------------------------------
    // المراكز المتاحة
    // ---------------------------------------------------------

    protected static function accessibleCenterIds(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        return app(CenterHierarchy::class)
            ->activeAccessibleIdsForUser($user);
    }

    protected static function centerOptions(): array
    {
        return Center::query()
            ->whereIn('id', self::accessibleCenterIds())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    protected static function defaultCenterId(): ?int
    {
        $ids = self::accessibleCenterIds();

        return count($ids) === 1 ? (int) $ids[0] : null;
    }

    // ---------------------------------------------------------
    // النموذج
    // ---------------------------------------------------------

    public static function form(Schema $schema): Schema
    {
        return $schema->components([

            Select::make('center_id')
                ->label('المركز')
                ->options(fn (): array => self::centerOptions())
                ->default(fn (): ?int => self::defaultCenterId())
                ->searchable()
                ->preload()
                ->live()
                ->required()
                ->afterStateUpdated(function (Set $set): void {
                    $set('student_id', null);
                    $set('circle_id', null);
                    $set('teacher_id', null);
                }),

            Select::make('student_id')
                ->label('الطالب')
                ->options(function (Get $get): array {
                    $centerId = $get('center_id');

                    if (! $centerId) {
                        return [];
                    }

                    return Student::query()
                        ->where('center_id', $centerId)
                        ->orderBy('full_name')
                        ->pluck('full_name', 'id')
                        ->all();
                })
                ->searchable()
                ->preload()
                ->required(),

            Select::make('circle_id')
                ->label('الحلقة')
                ->options(function (Get $get): array {
                    $centerId = $get('center_id');

                    if (! $centerId) {
                        return [];
                    }

                    return Circle::query()
                        ->where('center_id', $centerId)
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all();
                })
                ->searchable()
                ->preload()
                ->nullable(),

            Select::make('teacher_id')
                ->label('المعلم')
                ->options(function (Get $get): array {
                    $centerId = $get('center_id');

                    if (! $centerId) {
                        return [];
                    }

                    return Teacher::query()
                        ->whereHas('centers', function (Builder $query) use ($centerId) {
                            $query
                                ->where('centers.id', $centerId)
                                ->where('center_teacher.is_active', true);
                        })
                        ->orderBy('full_name')
                        ->pluck('full_name', 'id')
                        ->all();
                })
                ->searchable()
                ->preload()
                ->nullable(),

            DatePicker::make('record_date')
                ->label('تاريخ التسميع')
                ->default(now()->toDateString())
                ->required()
                ->native(false),

            Select::make('record_type')
                ->label('نوع التسميع')
                ->options([
                    'new' => 'حفظ جديد',
                    'review' => 'مراجعة',
                    'revision' => 'مراجعة سابقة',
                ])
                ->required()
                ->native(false),

            TextInput::make('surah_name')
                ->label('اسم السورة')
                ->maxLength(100)
                ->required(),

            TextInput::make('from_ayah')
                ->label('من الآية')
                ->numeric()
                ->minValue(1)
                ->required(),

            TextInput::make('to_ayah')
                ->label('إلى الآية')
                ->numeric()
                ->minValue(1)
                ->required()
                ->gte('from_ayah'),

            TextInput::make('grade')
                ->label('التقدير')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->step('0.01')
                ->suffix('%')
                ->nullable(),

            Textarea::make('notes')
                ->label('ملاحظات المعلم')
                ->rows(3)
                ->columnSpanFull()
                ->nullable(),
        ]);
    }

    // ---------------------------------------------------------
    // الجدول
    // ---------------------------------------------------------

    public static function table(Table $table): Table
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

                TextColumn::make('record_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),

                TextColumn::make('record_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'new' => 'حفظ جديد',
                        'review' => 'مراجعة',
                        'revision' => 'مراجعة سابقة',
                        default => $state ?? 'غير محدد',
                    }),

                TextColumn::make('surah_name')
                    ->label('السورة')
                    ->searchable(),

                TextColumn::make('from_ayah')
                    ->label('من'),

                TextColumn::make('to_ayah')
                    ->label('إلى'),

                TextColumn::make('grade')
                    ->label('التقدير')
                    ->suffix('%')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('record_type')
                    ->label('نوع التسميع')
                    ->options([
                        'new' => 'حفظ جديد',
                        'review' => 'مراجعة',
                        'revision' => 'مراجعة سابقة',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    // ---------------------------------------------------------
    // نطاق الاستعلام
    // ---------------------------------------------------------

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'center',
                'student',
                'circle',
                'teacher',
            ]);

        $centerIds = self::accessibleCenterIds();

        if (empty($centerIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('center_id', $centerIds);
    }

    // ---------------------------------------------------------
    // الصفحات
    // ---------------------------------------------------------

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMemorizationRecords::route('/'),
            'create' => Pages\CreateMemorizationRecord::route('/create'),
            'edit' => Pages\EditMemorizationRecord::route('/{record}/edit'),
        ];
    }
}