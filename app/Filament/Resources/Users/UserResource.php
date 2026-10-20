<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $pluralModelLabel = 'المستخدمون';

    protected static ?string $navigationLabel = 'المستخدمون';

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup =
       'إدارة المراكز';
    
    protected static ?int $navigationSort = 3;

    /**
     * إظهار المورد في القائمة عند امتلاك صلاحية العرض.
     * المدير العام يتجاوز الصلاحيات عبر Gate::before.
     */


    /**
     * تحديد المستخدمين الذين يمكن عرضهم.
     * المدير العام يرى جميع المراكز.
     * المستخدم العادي يرى مستخدمي مركزه فقط.
     */
 
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();

    $user = request()->user();

    if (! $user instanceof \App\Models\User) {
        return $query->whereRaw('1 = 0');
    }

    // المدير العام يستطيع مشاهدة جميع المستخدمين.
    if ($user->hasRole('super_admin')) {
        return $query;
    }

    // التحقق من صلاحية عرض قائمة المستخدمين.
    if (! $user->hasPermissionTo('ViewAny:User', 'web')) {
        return $query->whereRaw('1 = 0');
    }

    // جلب المراكز التي يستطيع المستخدم الوصول إليها.
    $centerIds = app(
        \App\Services\CenterHierarchy::class
    )->accessibleIdsForUser($user);

    if ($centerIds === []) {
        return $query->whereRaw('1 = 0');
    }

    // عرض المستخدمين المرتبطين بالمراكز المسموح بها فقط.
    return $query->whereIn(
        $query->getModel()->qualifyColumn('center_id'),
        $centerIds,
    );
}

    /**
     * نموذج إنشاء المستخدم وتعديله.
     */
    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    /**
     * جدول المستخدمين.
     */
    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    /**
     * هل يستطيع المستخدم مشاهدة قائمة المستخدمين؟
     */
        public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
 public static function canViewAny(): bool
{
    $user = request()->user();

    if (! $user instanceof \App\Models\User) {
        return false;
    }

    return $user->hasRole('super_admin')
        || $user->hasPermissionTo('ViewAny:User');
}

    /**
     * هل يستطيع إنشاء مستخدم؟
     */
public static function canCreate(): bool
{
    $user = request()->user();

    if (! $user instanceof User) {
        return false;
    }

    return $user->hasRole('super_admin')
        || $user->hasPermissionTo('Create:User', 'web');
}

    /**
     * هل يستطيع مشاهدة سجل مستخدم محدد؟
     */
    public static function canView(Model $record): bool
    {
        if (! $record instanceof User) {
            return false;
        }

        $user = request()->user();

        if (! $user instanceof User) {
            return false;
        }

        if (! Gate::allows('users.view')) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->center_id !== null
            && (int) $record->center_id === (int) $user->center_id;
    }

    /**
     * هل يستطيع تعديل مستخدم؟
     */
   
public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
{
    if (! $record instanceof User) {
        return false;
    }

    $user = request()->user();

    if (! $user instanceof User) {
        return false;
    }

    // منع تعديل الحساب الشخصي من خلال هذا المورد.
    if ($record->is($user)) {
        return false;
    }

    // منع تعديل حسابات المدير العام من غير المدير العام.
    if (
        $record->hasRole('super_admin')
        && ! $user->hasRole('super_admin')
    ) {
        return false;
    }

    // المدير العام يستطيع تعديل حسابات المستخدمين الآخرين.
    if ($user->hasRole('super_admin')) {
        return true;
    }

    // التحقق من صلاحية التعديل لدى بقية المستخدمين.
    if (! $user->hasPermissionTo('Update:User', 'web')) {
        return false;
    }

    // السماح بالتعديل فقط داخل المراكز التي يمكن الوصول إليها.
    $centerIds = app(
        \App\Services\CenterHierarchy::class
    )->accessibleIdsForUser($user);

    return $record->center_id !== null
        && in_array(
            (int) $record->center_id,
            array_map('intval', $centerIds),
            true,
        );
}

    /**
     * هل يستطيع حذف مستخدم؟
     */
    public static function canDelete(Model $record): bool
    {
        if (! $record instanceof User) {
            return false;
        }

        $user = request()->user();

        if (! $user instanceof User) {
            return false;
        }

        // منع حذف الحساب الحالي.
        if ($record->is($user)) {
            return false;
        }

        // منع حذف حساب المدير العام.
        if ($record->hasRole('super_admin')) {
            return false;
        }

        if (! Gate::allows('users.delete')) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->center_id !== null
            && (int) $record->center_id === (int) $user->center_id;
    }

    /**
     * صلاحية الحذف الجماعي.
     * يُعاد التحقق من كل سجل على حدة أيضًا.
     */
 public static function canDeleteAny(): bool
{
    return Auth::check()
        && Gate::allows('users.delete');
}
    /**
     * صفحات المورد.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
