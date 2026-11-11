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

    // -----------------------------------------------------------------
    // الصلاحيات
    // -----------------------------------------------------------------

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && Gate::forUser($user)->allows('viewAny', Center::class);
    }

    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof Center
            && Gate::forUser($user)->allows('view', $record);
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && Gate::forUser($user)->allows('create', Center::class);
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof Center
            && Gate::forUser($user)->allows('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $record instanceof Center
            && Gate::forUser($user)->allows('delete', $record);
    }

    /**
     * تعطيل الحذف الجماعي لتجنب حذف مراكز متعددة بالخطأ.
     */
    public static function canDeleteAny(): bool
    {
        return false;
    }

    // -----------------------------------------------------------------
    // نطاق البيانات
    // -----------------------------------------------------------------

    /**
     * المراكز التي يستطيع المستخدم مشاهدتها.
     *
     * نسمح بظهور المراكز المعلقة هنا حتى يستطيع المدير
     * مراجعة طلبات الفروع واعتمادها أو رفضها.
     *
     * لا تستخدم هذا النطاق للعمليات اليومية؛ استخدم
     * activeAccessibleIdsForUser في موارد الطلاب والحلقات والحضور.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'region',
                'parentCenter',
                'parentApprover',
                'finalApprover',
            ]);

        $user = Auth::user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        // المدير العام يرى جميع المراكز.
        if ($user->hasRole('super_admin')) {
            return self::applyHierarchyOrder($query);
        }

        // من لديه صلاحية العرض الشامل يرى جميع المراكز.
     if ($user->hasPermissionTo('ViewAll:Center', 'web')) {
    return self::applyHierarchyOrder($query);
}

        // التحقق من صلاحية عرض قائمة المراكز.
        if (! Gate::forUser($user)->allows('viewAny', Center::class)) {
            return $query->whereRaw('1 = 0');
        }

        // المراكز التي يستطيع المستخدم الوصول إليها.
        $centerIds = app(CenterHierarchy::class)
            ->accessibleIdsForUser($user);

        if (empty($centerIds)) {
            return $query->whereRaw('1 = 0');
        }

        return self::applyHierarchyOrder(
            $query->whereIn('centers.id', $centerIds)
        );
    }

    // -----------------------------------------------------------------
    // ترتيب الشجرة الهرمية
    // -----------------------------------------------------------------

    /**
     * عرض المركز الرئيسي أولاً، ثم فروعه حسب مستوياتها.
     *
     * يعتمد على PostgreSQL وWITH RECURSIVE.
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

    // -----------------------------------------------------------------
    // النموذج والجدول والصفحات
    // -----------------------------------------------------------------

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