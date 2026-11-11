<?php

declare(strict_types=1);

namespace App\Filament\Resources\Circles;

use App\Filament\Resources\Circles\Pages;
use App\Filament\Resources\Circles\Schemas\CircleForm;
use App\Filament\Resources\Circles\Tables\CirclesTable;
use App\Models\Circle;
use App\Services\CenterHierarchy;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class CircleResource extends Resource
{
    protected static ?string $model = Circle::class;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'الحلقات';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الحلقات';
 

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'حلقة';

    protected static ?string $pluralModelLabel = 'الحلقات';

    protected static ?string $slug = 'circles';

    /**
     * نموذج الحلقة.
     */
    public static function form(Schema $schema): Schema
    {
        return CircleForm::configure($schema);
    }

    /**
     * جدول الحلقات.
     */
    public static function table(Table $table): Table
    {
        return CirclesTable::configure($table);
    }

    /**
     * صفحات المورد.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCircles::route('/'),
            'create' => Pages\CreateCircle::route('/create'),
            'edit' => Pages\EditCircle::route('/{record}/edit'),
        ];
    }

    /**
     * المراكز المسموح للمستخدم بالوصول إليها.
     */
public static function allowedCenterIds(): array
{
    $user = auth()->user();

    if (! $user) {
        return [];
    }

    if ($user->hasRole('super_admin')) {
        return \App\Models\Center::query()
            ->pluck('id')
            ->all();
    }

    if (
        $user->hasAnyRole([
            'center_admin',
            'center_manager',
        ])
    ) {
        return app(CenterHierarchy::class)
            ->accessibleIdsForUser($user);
    }

    return [];
}
    /**
     * عزل الحلقات حسب المستخدم.
     */
/**
 * عزل الحلقات حسب دور المستخدم.
 */
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();

    $user = auth()->user();

    if (! $user) {
        return $query->whereRaw('1 = 0');
    }

    // 1. المدير العام: جميع الحلقات.
    if ($user->hasRole('super_admin')) {
        return $query;
    }

    // 2. مدير المركز ومدير الفرع.
    if (
        $user->hasAnyRole([
            'center_admin',
            'center_manager',
        ])
    ) {
        $centerIds = static::allowedCenterIds();

        if (empty($centerIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('center_id', $centerIds);
    }

    // 3. المعلم: الحلقات المسندة إليه فقط.
// المعلم يرى حلقاته المسندة إليه فقط.
if ($user->hasRole('teacher')) {

    return $query->whereHas(
        'teacher',
        fn (Builder $teacherQuery) => $teacherQuery
            ->where('user_id', $user->getKey())
            ->where('teachers.is_active', true)
            ->whereHas(
                'centers',
                fn (Builder $centerQuery) => $centerQuery
                    ->whereColumn(
                        'centers.id',
                        'circles.center_id'
                    )
                    ->where('center_teacher.is_active', true)
            )
    );
}
// 4. الطالب: الحلقات المسجل فيها حالياً فقط.
if ($user->hasRole('student')) {

    return $query->whereHas(
        'students',
        fn (Builder $studentQuery) => $studentQuery
            ->where('students.user_id', $user->getKey())
            ->where('students.is_active', true)
            ->whereNull('circle_student.left_at')
            ->whereColumn(
                'circle_student.center_id',
                'circles.center_id'
            )
    );
}


    // 4. الطالب وبقية الأدوار:
    // لا يسمح لهم بدخول إدارة الحلقات في Filament.
    return $query->whereRaw('1 = 0');
}

    /**
     * صلاحية عرض القائمة.
     */
  public static function canViewAny(): bool
{
    return Gate::allows('viewAny', Circle::class);
}

public static function canView(Model $record): bool
{
    return $record instanceof Circle
        && Gate::allows('view', $record);
}

public static function canCreate(): bool
{
    return Gate::allows('create', Circle::class);
}

public static function canEdit(Model $record): bool
{
    return $record instanceof Circle
        && Gate::allows('update', $record);
}

public static function canDelete(Model $record): bool
{
    return $record instanceof Circle
        && Gate::allows('delete', $record);
}

public static function canDeleteAny(): bool
{
    return Gate::allows('deleteAny', Circle::class);
}
}