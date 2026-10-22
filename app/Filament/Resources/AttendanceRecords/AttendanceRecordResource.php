<?php

declare(strict_types=1);

namespace App\Filament\Resources\AttendanceRecords;

use App\Filament\Resources\AttendanceRecords\Pages\CreateAttendanceRecord;
use App\Filament\Resources\AttendanceRecords\Pages\EditAttendanceRecord;
use App\Filament\Resources\AttendanceRecords\Pages\ListAttendanceRecords;
use App\Filament\Resources\AttendanceRecords\Schemas\AttendanceRecordForm;
use App\Filament\Resources\AttendanceRecords\Tables\AttendanceRecordsTable;
use App\Models\AttendanceRecord;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CenterHierarchy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class AttendanceRecordResource extends Resource
{
    protected static ?string $model = AttendanceRecord::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedRectangleStack;
          protected static \UnitEnum|string|null $navigationGroup =
        'إدارة  الوسط';
  protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'attendance_date';

    public static function form(Schema $schema): Schema
    {
        return AttendanceRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceRecordsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendanceRecords::route('/'),
            'create' => CreateAttendanceRecord::route('/create'),
            'edit' => EditAttendanceRecord::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = request()->user();

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
            return $query->whereHas(
                'circle',
                fn (Builder $circles) => $circles
                    ->where('teacher_id', $teacher->id)
                    ->whereColumn(
                        'circles.center_id',
                        'attendance_records.center_id'
                    )
            );
        }

        $centerIds = app(CenterHierarchy::class)
            ->accessibleIdsForUser($user);

        return $query->whereIn(
            'center_id',
            collect($centerIds)->map(
                fn ($id) => (int) $id
            )->all()
        );
    }

    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', AttendanceRecord::class);
    }

    public static function canCreate(): bool
    {
        return Gate::allows('create', AttendanceRecord::class);
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof AttendanceRecord
            && Gate::allows('view', $record);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof AttendanceRecord
            && Gate::allows('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof AttendanceRecord
            && Gate::allows('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
        return Gate::allows('deleteAny', AttendanceRecord::class);
    }
}
