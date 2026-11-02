<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers;

use App\Filament\Resources\Teachers\Pages;
use App\Filament\Resources\Teachers\Schemas\TeacherForm;
use App\Filament\Resources\Teachers\Tables\TeachersTable;
use App\Models\Center;
use App\Models\Teacher;
use App\Services\CenterHierarchy;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-academic-cap';

    protected static \UnitEnum|string|null $navigationGroup =
        'إدارة الوسط';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'معلم';

    protected static ?string $pluralModelLabel = 'المعلمون';

    protected static ?string $navigationLabel = 'المعلمون';

    /**
     * المراكز المسموح للمستخدم بالعمل عليها.
     */
    public static function allowedCenterIds(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->hasRole('super_admin')) {
            return Center::query()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        return collect(
             app(CenterHierarchy::class)->accessibleIdsForUser($user)
        )
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * عرض قائمة المعلمين.
     */
    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', Teacher::class);
    }

    /**
     * عرض معلم.
     */
    public static function canView(Model $record): bool
    {
        return $record instanceof Teacher
            && Gate::allows('view', $record);
    }

    /**
     * إنشاء معلم.
     */
    public static function canCreate(): bool
    {
        return Gate::allows('create', Teacher::class);
    }

    /**
     * تعديل معلم.
     */
    public static function canEdit(Model $record): bool
    {
        return $record instanceof Teacher
            && Gate::allows('update', $record);
    }

    /**
     * حذف معلم.
     */
    public static function canDelete(Model $record): bool
    {
        return $record instanceof Teacher
            && Gate::allows('delete', $record);
    }

    /**
     * حذف عدة معلمين.
     */
    public static function canDeleteAny(): bool
    {
        return Gate::allows('deleteAny', Teacher::class);
    }

    /**
     * نموذج المعلم.
     */
    public static function form(Schema $schema): Schema
    {
        return TeacherForm::configure($schema);
    }

    /**
     * جدول المعلمين.
     */
    public static function table(Table $table): Table
    {
        return TeachersTable::configure($table);
    }

    /**
     * عرض المعلمين المرتبطين بمراكز المستخدم فقط.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['centers', 'user']);

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $allowedIds = static::allowedCenterIds();

        if ($allowedIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'activeCenters',
            fn (Builder $centerQuery): Builder =>
                $centerQuery->whereIn('centers.id', $allowedIds)
        );
    }

    /**
     * صفحات المورد.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'edit' => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }
}
