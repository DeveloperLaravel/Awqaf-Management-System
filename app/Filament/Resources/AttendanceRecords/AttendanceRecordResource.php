<?php

declare(strict_types=1);

namespace App\Filament\Resources\AttendanceRecords;

use App\Filament\Resources\AttendanceRecords\Pages;
use App\Models\AttendanceRecord;
use App\Models\Center;
use App\Models\Circle;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Filament\Resources\AttendanceRecords\Schemas\AttendanceRecordForm;
use App\Filament\Resources\AttendanceRecords\Tables\AttendanceRecordsTable;


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


    // أكمل بقية شروط المستخدم الحالية كما هي...





public  static function allowedCirclesQuery(): Builder
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
    public  static function accessibleCenterIds(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        return app(CenterHierarchy::class)
            ->activeAccessibleIdsForUser($user);
    }

    // protected static function centerOptions(): array
    // {
    //     return Center::query()
    //         ->whereIn('id', self::accessibleCenterIds())
    //         ->orderBy('name')
    //         ->pluck('name', 'id')
    //         ->all();
    // }

    public  static function centerOptions(): array
{
    return Center::query()
        ->whereIn('id', self::accessibleCenterIds())
        ->where('is_active', true)
        ->where('approval_status', 'active')
        ->orderBy('name')
        ->pluck('name', 'id')
        ->all();
}

    public  static function defaultCenterId(): ?int
    {
        $ids = self::accessibleCenterIds();

        return count($ids) === 1 ? (int) $ids[0] : null;
    }

    // ---------------------------------------------------------
    // النموذج
    // ---------------------------------------------------------

public static function form(Schema $schema): Schema
{
    return AttendanceRecordForm::configure($schema);
}

    // ---------------------------------------------------------
    // الجدول
    // ---------------------------------------------------------
public static function table(Table $table): Table
{
    return AttendanceRecordsTable::configure($table);
}
 
    public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with([
            'center',
            'student',
            'circle',
            'teacher',
        ])
        ->whereIn(
            'circle_id',
            self::allowedCirclesQuery()->select('id')
        );
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