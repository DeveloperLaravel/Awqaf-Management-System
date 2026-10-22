<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Students\Pages;
use App\Filament\Resources\Students\Schemas\StudentForm;
use App\Filament\Resources\Students\Tables\StudentsTable;
use App\Models\Student;
use App\Support\StudentCenterAccess;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $recordTitleAttribute = 'full_name';
  protected static \UnitEnum|string|null $navigationGroup =
        'إدارة  الوسط';

      protected static ?int $navigationSort = 2;
    public static function form(Schema $schema): Schema
    {
        return StudentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentsTable::configure($table);
    }

    /**
     * المشرف العام يرى طلاب الفروع الفعالة والمعتمدة.
     * مدير المركز يرى طلاب الفروع المصرح بها لحسابه فقط.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $branchIds = StudentCenterAccess::branchIdsFor($user);

        if ($branchIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('center_id', $branchIds);
    }

    public static function canViewAny(): bool
    {
        $user = request()->user();

        return $user !== null
            && $user->can('viewAny', Student::class);
    }

    public static function canCreate(): bool
    {
        $user = request()->user();

        return $user !== null
            && $user->can('create', Student::class);
    }

    public static function canEdit($record): bool
    {
        $user = request()->user();

        return $user !== null
            && $record instanceof Student
            && $user->can('update', $record);
    }

    public static function canDelete($record): bool
    {
        $user = request()->user();

        return $user !== null
            && $record instanceof Student
            && $user->can('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
        $user = request()->user();

        return $user !== null
            && $user->can('deleteAny', Student::class);
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
