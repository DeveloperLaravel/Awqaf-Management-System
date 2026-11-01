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

    /*
    |--------------------------------------------------------------------------
    | Query Scope
    |--------------------------------------------------------------------------
    */

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        // المدير العام يرى جميع المستخدمين.
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        // لا يمكن عرض القائمة دون صلاحية ViewAny:User.
        if (! Gate::allows('viewAny', User::class)) {
            return $query->whereRaw('1 = 0');
        }

        $centerIds = app(CenterHierarchy::class)
            ->accessibleIdsForUser($user);

        if ($centerIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereIn('center_id', $centerIds)

            // إخفاء حسابات المدير العام عن بقية المستخدمين.
            ->whereDoesntHave('roles', function (Builder $rolesQuery) {
                $rolesQuery
                    ->where('name', 'super_admin')
                    ->where('guard_name', 'web');
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Form
    |--------------------------------------------------------------------------
    */

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', User::class);
    }

    public static function canCreate(): bool
    {
        return Gate::allows('create', User::class);
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof User
            && Gate::allows('view', $record);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof User
            && Gate::allows('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof User
            && Gate::allows('delete', $record);
    }

    /*
    |--------------------------------------------------------------------------
    | Bulk Actions
    |--------------------------------------------------------------------------
    */

    public static function canDeleteAny(): bool
    {
        // الحذف الجماعي معطل لحماية الحسابات الحساسة.
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
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
