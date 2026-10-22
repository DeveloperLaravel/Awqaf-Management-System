<?php

declare(strict_types=1);

namespace App\Filament\Resources\Circles;

use App\Filament\Resources\Circles\Pages;
use App\Filament\Resources\Circles\Schemas\CircleForm;
use App\Filament\Resources\Circles\Tables\CirclesTable;
use App\Models\Circle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CircleResource extends Resource
{
    protected static ?string $model = Circle::class;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'الحلقات';
  protected static \UnitEnum|string|null $navigationGroup =
        'إدارة  الوسط';    
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
     * فحص الصلاحيات المتوافقة مع أسماء Spatie وShield
     * المستخدمة في المشروع.
     */
    protected static function hasAnyCirclePermission(
        array $permissions
    ): bool {
        $user = request()->user();

        return $user !== null
            && $user->hasAnyPermission($permissions);
    }

    /**
     * عزل الحلقات حسب الدور.
     */
   public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();

    $user = request()->user();

    // لا يوجد مستخدم مسجل الدخول
    if (! $user) {
        return $query->whereRaw('1 = 0');
    }

    // المدير العام يرى جميع الحلقات
    if ($user->hasRole('super_admin')) {
        return $query;
    }

    // مدير المركز يرى حلقات مركزه
    if ($user->hasRole('center_manager')) {
        if (blank($user->center_id)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(
            $query->getModel()->qualifyColumn('center_id'),
            $user->center_id
        );
    }

    // المعلم يرى الحلقات المرتبطة بحساب المعلم الخاص به
    if ($user->hasRole('teacher')) {
        return $query->whereHas(
            'teacher',
            fn (Builder $teacherQuery) => $teacherQuery
                ->where('user_id', $user->getKey())
        );
    }

    // منع الوصول لبقية الأدوار
    return $query->whereRaw('1 = 0');
}
    /**
     * صلاحية عرض قائمة الحلقات.
     */
    public static function canViewAny(): bool
    {
        $user = request()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('center_manager')) {
            return filled($user->center_id)
                && static::hasAnyCirclePermission([
                    'circles.view',
                    'ViewAny:Circle',
                ]);
        }

        if ($user->hasRole('teacher')) {
            return static::hasAnyCirclePermission([
                'circles.view',
                'circles.view_own',
                'ViewAny:Circle',
            ]);
        }

        return false;
    }

    /**
     * صلاحية عرض سجل حلقة معينة.
     */
    public static function canView(Model $record): bool
    {
        $user = request()->user();

        if (! $user || ! $record instanceof Circle) {
            return false;
        }

        if (! static::recordIsAccessible($record)) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('center_manager')) {
            return static::hasAnyCirclePermission([
                'circles.view',
                'View:Circle',
            ]);
        }

        if ($user->hasRole('teacher')) {
            return static::hasAnyCirclePermission([
                'circles.view',
                'circles.view_own',
                'View:Circle',
                'ViewAny:Circle',
            ]);
        }

        return false;
    }

    /**
     * صلاحية إنشاء حلقة.
     */
    public static function canCreate(): bool
    {
        $user = request()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('center_manager')
            && filled($user->center_id)
            && static::hasAnyCirclePermission([
                'circles.create',
                'Create:Circle',
            ]);
    }

    /**
     * صلاحية تعديل حلقة.
     */
    public static function canEdit(Model $record): bool
    {
        $user = request()->user();


        if (! $user || ! $record instanceof Circle) {
            return false;
        }

        if (! static::recordIsAccessible($record)) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('center_manager')
            && static::hasAnyCirclePermission([
                'circles.update',
                'Update:Circle',
            ]);
    }

    /**
     * صلاحية حذف حلقة.
     */
    public static function canDelete(Model $record): bool
    {
        $user = request()->user();


        if (! $user || ! $record instanceof Circle) {
            return false;
        }

        if (! static::recordIsAccessible($record)) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('center_manager')
            && static::hasAnyCirclePermission([
                'circles.delete',
                'Delete:Circle',
            ]);
    }

    /**
     * التأكد من أن السجل داخل نطاق المستخدم.
     */
    protected static function recordIsAccessible(
        Circle $record
    ): bool {
        $user = request()->user();


        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('center_manager')) {
            return filled($user->center_id)
                && (int) $record->center_id === (int) $user->center_id;
        }

        if ($user->hasRole('teacher')) {
            return $record->teacher()
                ->where('user_id', $user->getKey())
                ->exists();
        }

        return false;
    }
}
