<?php

declare(strict_types=1);

namespace App\Filament\Resources\Regions;

use App\Filament\Resources\Regions\Pages;
use App\Filament\Resources\Regions\Schemas\RegionForm;
use App\Filament\Resources\Regions\Tables\RegionsTable;
use App\Models\Center;
use App\Models\Region;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'المناطق';

    protected static ?string $modelLabel = 'منطقة';

    protected static ?string $pluralModelLabel = 'المناطق';

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-map';

    protected static string|\UnitEnum|null $navigationGroup =
        'إدارة المراكز';

    protected static ?int $navigationSort = 2;

    /*
    |--------------------------------------------------------------------------
    | Form
    |--------------------------------------------------------------------------
    */

    public static function form(Schema $schema): Schema
    {
        return RegionForm::configure($schema);
    }

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    public static function table(Table $table): Table
    {
        return RegionsTable::configure($table);
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scope
    |--------------------------------------------------------------------------
    */

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withCount('centers');

        $user = Auth::user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        // المدير العام يستطيع مشاهدة كل المناطق.
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        // التحقق من صلاحية مشاهدة المناطق.
        if (! Gate::allows('viewAny', Region::class)) {
            return $query->whereRaw('1 = 0');
        }

        /*
         * المركز هو المصدر الأساسي لتحديد المنطقة.
         */
        $regionId = null;

        if ($user->center_id !== null) {
            $regionId = Center::query()
                ->whereKey($user->center_id)
                ->value('region_id');
        } else {
            $regionId = $user->region_id;
        }

        if ($regionId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereKey($regionId);
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
        return Gate::allows('viewAny', Region::class);
    }

    public static function canCreate(): bool
    {
        return Gate::allows('create', Region::class);
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof Region
            && Gate::allows('view', $record);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Region
            && Gate::allows('update', $record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Region
            && Gate::allows('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
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
            'index' => Pages\ListRegions::route('/'),
            'create' => Pages\CreateRegion::route('/create'),
            'edit' => Pages\EditRegion::route('/{record}/edit'),
        ];
    }
}
