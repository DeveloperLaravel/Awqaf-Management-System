<?php

namespace App\Filament\Resources\Regions;

use App\Filament\Resources\Regions\Pages;
use App\Filament\Resources\Regions\Schemas\RegionForm;
use App\Filament\Resources\Regions\Tables\RegionsTable;
use App\Models\Region;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return 'المناطق';
    }

    public static function getModelLabel(): string
    {
        return 'منطقة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'المناطق';
    }

 
 protected static \UnitEnum|string|null $navigationGroup =
        //  'إدارة النظام';
           'إدارة المراكز';
    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-map';
    }


  







    public static function form(Schema $schema): Schema
    {
        return RegionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RegionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        if (Gate::forUser($user)->allows('ViewAny:Region')) {
            return parent::getEloquentQuery()
                ->withCount('centers');
        }

        // المستخدم العادي يشاهد منطقة مركزه فقط.
        return parent::getEloquentQuery()
            ->whereHas('centers', function (Builder $query) use ($user) {
                $query->where('id', $user->center_id);
            })
            ->withCount('centers');
    }

    public static function canCreate(): bool
    {
        return Auth::check()
            && Gate::forUser(Auth::user())->allows('Create:Region');
    }

    public static function canEdit($record): bool
    {
        return Auth::check()
            && Gate::forUser(Auth::user())->allows('Update:Region');
    }

    public static function canDelete($record): bool
    {
        return Auth::check()
            && Gate::forUser(Auth::user())->allows('Delete:Region');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegions::route('/'),
            'create' => Pages\CreateRegion::route('/create'),
            'edit' => Pages\EditRegion::route('/{record}/edit'),
        ];
    }
}
