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

    /**
     * مجموعة عناصر القائمة الجانبية.
     */
    public static function getNavigationGroup(): ?string
    {
        return 'إدارة المراكز';
    }

    /**
     * هل يستطيع المستخدم مشاهدة قائمة المراكز؟
     */
public static function canViewAny(): bool
{
    $user = request()->user();

    return $user instanceof User
        && (
            $user->hasRole('super_admin')
            || $user->hasPermissionTo('ViewAny:Center', 'web')
        );
}
    /**
     * هل يستطيع المستخدم مشاهدة مركز معين؟
     */
    public static function canView(Model $record): bool
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $record instanceof Center) {
            return false;
        }

        return Gate::forUser($user)->allows('view', $record);
    }
    

    /**
     * هل يستطيع إنشاء مركز؟
     */
public static function canCreate(): bool
{
    $user = request()->user();

    if (! $user instanceof User) {
        return false;
    }

    // المدير العام يحتاج صلاحية إنشاء المراكز.
    if ($user->hasRole('super_admin')) {
        return $user->hasPermissionTo('Create:Center');
    }

    // بقية المديرين يمكنهم بدء إنشاء مركز فرعي فقط.
    return $user->hasPermissionTo('Create:Center')
        && (
            $user->center_id !== null
            || $user->managedCenters()->exists()
        );
}
    /**
     * هل يستطيع تعديل المركز المحدد؟
     */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $record instanceof Center) {
            return false;
        }

        return Gate::forUser($user)->allows('update', $record);
    }

    /**
     * هل يستطيع حذف المركز المحدد؟
     */
    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $record instanceof Center) {
            return false;
        }

        return Gate::forUser($user)->allows('delete', $record);
    }

    /**
     * تحديد المراكز التي يمكن للمستخدم عرضها.
     */
    
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery()
        ->with(['region', 'parentCenter']);

    $user = request()->user();

    if (! $user instanceof User) {
        return $query->whereRaw('1 = 0');
    }

    if ($user->hasRole('super_admin')) {
        return $query;
    }

    if (! $user->hasPermissionTo('ViewAny:Center')) {
        return $query->whereRaw('1 = 0');
    }

    $centerIds = app(CenterHierarchy::class)
        ->accessibleIdsForUser($user);

    if ($centerIds === []) {
        return $query->whereRaw('1 = 0');
    }

    return $query->whereIn('centers.id', $centerIds);
}


    /**
     * التحقق من إمكانية الوصول إلى مركز محدد.
     */
    protected static function userCanAccessCenter(
        User $user,
        Center $center,
    ): bool {
        if ($user->hasRole('super_admin')) {
            return $user->can('centers.view_all');
        }

        return Gate::forUser($user)->allows('centers.access', $center);
    }

    /**
     * إرجاع معرفات المراكز التي يستطيع المستخدم الوصول إليها.
     */
    protected static function accessibleCenterIds(User $user): array
    {
        $assignedIds = $user->managedCenters()
            ->pluck('centers.id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        // دعم المستخدمين المرتبطين بمركز عبر center_id.
        if ($user->center_id !== null) {
            $assignedIds[] = (int) $user->center_id;
        }

        $assignedIds = array_values(array_unique($assignedIds));

        if ($assignedIds === []) {
            return [];
        }

        $hierarchy = app(CenterHierarchy::class);
        $accessibleIds = [];

        foreach ($assignedIds as $centerId) {
            $accessibleIds = array_merge(
                $accessibleIds,
                $hierarchy->accessibleIds($centerId),
            );
        }

        return array_values(array_unique(array_map(
            'intval',
            $accessibleIds,
        )));
    }

    /**
     * نموذج إنشاء وتعديل المركز.
     */
    public static function form(Schema $schema): Schema
    {
        return CenterForm::configure($schema);
    }

    /**
     * جدول المراكز.
     */
    public static function table(Table $table): Table
    {
        return CentersTable::configure($table);
    }

    /**
     * صفحات المورد.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCenters::route('/'),
            'create' => Pages\CreateCenter::route('/create'),
            'edit' => Pages\EditCenter::route('/{record}/edit'),
        ];
    }
}