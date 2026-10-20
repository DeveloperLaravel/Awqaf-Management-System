<?php

namespace App\Filament\Resources\Guardians;

use App\Filament\Resources\Guardians\Pages\CreateGuardian;
use App\Filament\Resources\Guardians\Pages\EditGuardian;
use App\Filament\Resources\Guardians\Pages\ListGuardians;
use App\Filament\Resources\Guardians\Pages\ViewGuardian;
use App\Filament\Resources\Guardians\Schemas\GuardianForm;
use App\Filament\Resources\Guardians\Schemas\GuardianInfolist;
use App\Filament\Resources\Guardians\Tables\GuardiansTable;
use App\Filament\Resources\Concerns\BelongsToCenter;
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

    /**
     * نموذج إضافة وتعديل ولي الأمر.
     */
    public static function form(Schema $schema): Schema
    {
        return GuardianForm::configure($schema);
    }

    /**
     * تفاصيل ولي الأمر.
     */
    public static function infolist(Schema $schema): Schema
    {
        return GuardianInfolist::configure($schema);
    }

    /**
     * جدول أولياء الأمور.
     */
    public static function table(Table $table): Table
    {
        return GuardiansTable::configure($table);
    }

    /**
     * العلاقات.
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * صفحات المورد.
     */
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
     * فلترة أولياء الأمور حسب مركز المستخدم.
     */
    public static function getEloquentQuery(): Builder
    {
        return self::applyCenterScope(
            parent::getEloquentQuery()
        );
    }

    /**
     * عرض قائمة أولياء الأمور.
     */
    public static function canViewAny(): bool
    {
        return Gate::allows('guardians.view');
    }

    /**
     * إضافة ولي أمر.
     */
    public static function canCreate(): bool
    {
        return Gate::allows('guardians.create');
    }

    /**
     * عرض ولي أمر محدد.
     */
    public static function canView(Model $record): bool
    {
        return Gate::allows('guardians.view')
            && self::belongsToAllowedCenter($record);
    }

    /**
     * تعديل ولي أمر.
     */
    public static function canEdit(Model $record): bool
    {
        return Gate::allows('guardians.update')
            && self::belongsToAllowedCenter($record);
    }

    /**
     * حذف ولي أمر.
     */
    public static function canDelete(Model $record): bool
    {
        return Gate::allows('guardians.delete')
            && self::belongsToAllowedCenter($record);
    }
}