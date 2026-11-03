<?php

declare(strict_types=1);

namespace App\Filament\Resources\AttendanceRecords;

use App\Filament\Resources\AttendanceRecords\Pages;
use App\Models\AttendanceRecord;
use App\Models\Center;
use App\Models\Circle;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class AttendanceRecordResource extends Resource
{
    protected static ?string $model = AttendanceRecord::class;

    protected static ?string $modelLabel = 'سجل حضور';

    protected static ?string $pluralModelLabel = 'سجلات الحضور';

    protected static ?string $navigationLabel = 'الحضور والغياب';

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 5;

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
                AttendanceRecord::class
            );
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof AttendanceRecord
            && Gate::forUser($user)->allows('view', $record);
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && Gate::forUser($user)->allows(
                'create',
                AttendanceRecord::class
            );
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof AttendanceRecord
            && Gate::forUser($user)->allows('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof AttendanceRecord
            && Gate::forUser($user)->allows('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    // ---------------------------------------------------------
    // المراكز المتاحة
    // ---------------------------------------------------------
protected static function allowedCirclesQuery(): Builder
{
    $user = Auth::user();

    $query = Circle::query()
        ->where('circles.is_active', true)
        ->whereHas('center', function (Builder $query) {
            $query
                ->where('is_active', true)
                ->where('approval_status', 'active');
        });

    if (! $user instanceof User) {
        return $query->whereRaw('1 = 0');
    }

    if ($user->hasRole('super_admin')) {
        return $query;
    }

    $teacher = Teacher::query()
        ->where('user_id', $user->id)
        ->first();

    if ($teacher) {
        return $query->where('teacher_id', $teacher->id);
    }

    return $query->whereIn(
        'center_id',
        self::accessibleCenterIds()
    );
}
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
            }),

        Select::make('circle_id')
            ->label('الحلقة')
            ->options(function (Get $get): array {
                $centerId = $get('center_id');

                if (! $centerId) {
                    return [];
                }

                return self::allowedCirclesQuery()
                    ->where('center_id', $centerId)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all();
            })
            ->searchable()
            ->preload()
            ->live()
            ->required()
            ->disabled(fn (Get $get): bool => ! $get('center_id'))
            ->afterStateUpdated(function (Set $set): void {
                $set('student_id', null);
            }),

        Select::make('student_id')
            ->label('الطالب')
            ->options(function (Get $get): array {
                $circleId = $get('circle_id');

                if (! $circleId) {
                    return [];
                }

                $circle = self::allowedCirclesQuery()
                    ->whereKey($circleId)
                    ->first();

                if (! $circle) {
                    return [];
                }

                return $circle->students()
                    ->wherePivotNull('left_at')
                    ->orderBy('full_name')
                    ->pluck('students.full_name', 'students.id')
                    ->all();
            })
            ->searchable()
            ->preload()
            ->required()
            ->disabled(fn (Get $get): bool => ! $get('circle_id')),

        DatePicker::make('attendance_date')
            ->label('تاريخ الحضور')
            ->default(now()->toDateString())
            ->required()
            ->native(false),

        Select::make('status')
            ->label('حالة الحضور')
            ->options([
                AttendanceRecord::STATUS_PRESENT => 'حاضر',
                AttendanceRecord::STATUS_ABSENT => 'غائب',
                AttendanceRecord::STATUS_LATE => 'متأخر',
                AttendanceRecord::STATUS_EXCUSED => 'غياب بعذر',
            ])
            ->default(AttendanceRecord::STATUS_PRESENT)
            ->required()
            ->native(false),

        Textarea::make('notes')
            ->label('ملاحظات')
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
            'index' => Pages\ListAttendanceRecords::route('/'),
            'create' => Pages\CreateAttendanceRecord::route('/create'),
            'edit' => Pages\EditAttendanceRecord::route('/{record}/edit'),
        ];
    }
}