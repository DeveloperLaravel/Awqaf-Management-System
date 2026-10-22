<?php

declare(strict_types=1);

namespace App\Filament\Resources\Guardians;

use App\Filament\Resources\Concerns\BelongsToCenter;
use App\Filament\Resources\Guardians\Pages\CreateGuardian;
use App\Filament\Resources\Guardians\Pages\EditGuardian;
use App\Filament\Resources\Guardians\Pages\ListGuardians;
use App\Filament\Resources\Guardians\Pages\ViewGuardian;
use App\Filament\Resources\Guardians\Schemas\GuardianForm;
use App\Filament\Resources\Guardians\Schemas\GuardianInfolist;
use App\Filament\Resources\Guardians\Tables\GuardiansTable;
use App\Models\Guardian;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class GuardianResource extends Resource
{
    use BelongsToCenter;

    protected static ?string $model = Guardian::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'full_name';
  protected static \UnitEnum|string|null $navigationGroup =
        'إدارة  الوسط';
      protected static ?int $navigationSort = 3;
    public static function form(Schema $schema): Schema
    {
        return GuardianForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GuardianInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GuardiansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGuardians::route('/'),
            'create' => CreateGuardian::route('/create'),
            'view' => ViewGuardian::route('/{record}'),
            'edit' => EditGuardian::route('/{record}/edit'),
        ];
    }

    /**
     * عزل السجلات حسب نطاق المراكز المسموح للمستخدم.
     */
    public static function getEloquentQuery(): Builder
    {
        return static::applyCenterScope(
            parent::getEloquentQuery()
        );
    }

    public static function canViewAny(): bool
    {
        return Gate::allows('guardians.view');
    }

    public static function canCreate(): bool
    {
        return Gate::allows('guardians.create');
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof Guardian
            && Gate::allows('guardians.view')
            && static::belongsToAllowedCenter($record);
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Guardian
            && Gate::allows('guardians.update')
            && static::belongsToAllowedCenter($record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Guardian
            && Gate::allows('guardians.delete')
            && static::belongsToAllowedCenter($record);
    }

    public static function canDeleteAny(): bool
    {
        return Gate::allows('guardians.delete');
    }
}
