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

    protected static \UnitEnum|string|null $navigationGroup =
        'إدارة الوسط';

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

    return collect(
        app(CenterHierarchy::class)->accessibleIdsForUser($user)
    )->all();
}

    /**
     * عزل الحلقات حسب المستخدم.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // المدير العام يرى جميع الحلقات.
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        // مدير المركز يرى حلقات مراكزه والفروع التابعة له.
        if ($user->hasRole('center_manager')) {
            $centerIds = static::allowedCenterIds();

            if (empty($centerIds)) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereIn('center_id', $centerIds);
        }

        // المعلم يرى الحلقات المسندة إليه فقط.
        if ($user->hasRole('teacher')) {
            return $query->whereHas(
                'teacher',
                fn (Builder $teacherQuery) => $teacherQuery
                    ->where('user_id', $user->getKey())
            );
        }

        // منع بقية الأدوار من رؤية الحلقات.
        return $query->whereRaw('1 = 0');
    }

    /**
     * صلاحية عرض القائمة.
     */
    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', Circle::class);
    }

    /**
     * صلاحية عرض سجل.
     */
    public static function canView(Model $record): bool
    {
        return $record instanceof Circle
            && Gate::allows('view', $record);
    }

    /**
     * صلاحية إنشاء حلقة.
     */
    public static function canCreate(): bool
    {
        return Gate::allows('create', Circle::class);
    }

    /**
     * صلاحية تعديل حلقة.
     */
    public static function canEdit(Model $record): bool
    {
        return $record instanceof Circle
            && Gate::allows('update', $record);
    }

    /**
     * صلاحية حذف حلقة.
     */
    public static function canDelete(Model $record): bool
    {
        return $record instanceof Circle
            && Gate::allows('delete', $record);
    }

    /**
     * صلاحية الحذف الجماعي.
     */
    public static function canDeleteAny(): bool
    {
        return Gate::allows('deleteAny', Circle::class);
    }
}
