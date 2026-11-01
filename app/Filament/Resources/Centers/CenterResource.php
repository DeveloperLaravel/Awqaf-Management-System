<?php

declare(strict_types=1);

namespace App\Filament\Resources\Centers;

use App\Filament\Resources\Centers\Pages;
use App\Filament\Resources\Centers\Schemas\CenterForm;
use App\Filament\Resources\Centers\Tables\CentersTable;
use App\Models\Center;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CenterResource extends Resource
{
    protected static ?string $model = Center::class;

    protected static ?string $modelLabel = 'مركز';

    protected static ?string $pluralModelLabel = 'مراكز التحفيظ';

    protected static ?string $navigationLabel = 'مراكز التحفيظ';

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-building-library';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'إدارة المراكز';
    }

    /**
     * عرض قائمة المراكز.
     */
    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && Gate::forUser($user)->allows('viewAny', Center::class);
    }

    /**
     * عرض مركز معين.
     */
    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof Center
            && Gate::forUser($user)->allows('view', $record);
    }

    /**
     * إنشاء مركز أو فرع وفق سياسة المستخدم.
     */
    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && Gate::forUser($user)->allows('create', Center::class);
    }

    /**
     * تعديل مركز.
     */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof Center
            && Gate::forUser($user)->allows('update', $record);
    }

    /**
     * حذف مركز.
     */
    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof Center
            && Gate::forUser($user)->allows('delete', $record);
    }

    /**
     * منع الحذف الجماعي.
     */
    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * نطاق المراكز التي تظهر للمستخدم،
     * مع ترتيب المراكز والفروع بصورة هرمية.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'region',
                'parentCenter',
            ]);

        $user = Auth::user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        /*
         * المدير العام يرى جميع المراكز.
         */
        if ($user->hasRole('super_admin')) {
            return self::applyHierarchyOrder($query);
        }

        /*
         * التحقق من صلاحية عرض قائمة المراكز.
         */
        if (! Gate::forUser($user)->allows('viewAny', Center::class)) {
            return $query->whereRaw('1 = 0');
        }

        /*
         * صلاحية العرض الشامل.
         */
        if ($user->hasPermissionTo('centers.view_all', 'web')) {
            return self::applyHierarchyOrder($query);
        }

        /*
         * جلب المركز والمراكز التابعة للمستخدم
         * وفق خدمة CenterHierarchy.
         */
        $centerIds = collect(
            app(CenterHierarchy::class)->accessibleIdsForUser($user)
        )->all();

        if (empty($centerIds)) {
            return $query->whereRaw('1 = 0');
        }

        return self::applyHierarchyOrder(
            $query->whereIn('centers.id', $centerIds)
        );
    }

    /**
     * ترتيب المراكز حسب الشجرة الهرمية.
     *
     * المركز الرئيسي أولاً، ثم فروعه،
     * ثم فروع تلك الفروع، وهكذا.
     *
     * يستخدم PostgreSQL Recursive CTE.
     */
    protected static function applyHierarchyOrder(Builder $query): Builder
    {
        return $query->orderByRaw("
            (
                WITH RECURSIVE center_tree AS (
                    SELECT
                        id,
                        parent_center_id,
                        ARRAY[id] AS hierarchy_path
                    FROM centers
                    WHERE parent_center_id IS NULL

                    UNION ALL

                    SELECT
                        child.id,
                        child.parent_center_id,
                        parent.hierarchy_path || child.id
                    FROM centers AS child
                    INNER JOIN center_tree AS parent
                        ON child.parent_center_id = parent.id
                    WHERE NOT child.id = ANY(parent.hierarchy_path)
                )
                SELECT hierarchy_path
                FROM center_tree
                WHERE center_tree.id = centers.id
            ) ASC NULLS LAST,
            centers.id ASC
        ");
    }

    public static function form(Schema $schema): Schema
    {
        return CenterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CentersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCenters::route('/'),
            'create' => Pages\CreateCenter::route('/create'),
            'edit' => Pages\EditCenter::route('/{record}/edit'),
        ];
    }
}
