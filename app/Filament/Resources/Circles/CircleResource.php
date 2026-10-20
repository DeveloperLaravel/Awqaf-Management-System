<?php

namespace App\Filament\Resources\Circles;

use App\Filament\Resources\Circles\Pages\CreateCircle;
use App\Filament\Resources\Circles\Pages\EditCircle;
use App\Filament\Resources\Circles\Pages\ListCircles;
use App\Filament\Resources\Circles\Pages\ViewCircle;
use App\Filament\Resources\Circles\Schemas\CircleForm;
use App\Filament\Resources\Circles\Schemas\CircleInfolist;
use App\Filament\Resources\Circles\Tables\CirclesTable;
use App\Filament\Resources\Concerns\BelongsToCenter;
use App\Models\Circle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class CircleResource extends Resource
{
    use BelongsToCenter;

    protected static ?string $model = Circle::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * نموذج إنشاء وتعديل الحلقة.
     */
    public static function form(Schema $schema): Schema
    {
        return CircleForm::configure($schema);
    }

    /**
     * تفاصيل الحلقة.
     */
    public static function infolist(Schema $schema): Schema
    {
        return CircleInfolist::configure($schema);
    }

    /**
     * جدول الحلقات.
     */
    public static function table(Table $table): Table
    {
        return CirclesTable::configure($table);
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
     * صفحات الحلقات.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListCircles::route('/'),
            'create' => CreateCircle::route('/create'),
            'view' => ViewCircle::route('/{record}'),
            'edit' => EditCircle::route('/{record}/edit'),
        ];
    }

    /**
     * فلترة الحلقات حسب المركز.
     */
    public static function getEloquentQuery(): Builder
    {
        return self::applyCenterScope(
            parent::getEloquentQuery()
        );
    }

    /**
     * عرض قائمة الحلقات.
     */
    public static function canViewAny(): bool
    {
        return Gate::allows('circles.view');
    }

    /**
     * إنشاء حلقة جديدة.
     */
    public static function canCreate(): bool
    {
        return Gate::allows('circles.create');
    }

    /**
     * عرض حلقة محددة.
     */
    public static function canView(Model $record): bool
    {
        return Gate::allows('circles.view')
            && self::belongsToAllowedCenter($record);
    }

    /**
     * تعديل حلقة.
     */
    public static function canEdit(Model $record): bool
    {
        return Gate::allows('circles.update')
            && self::belongsToAllowedCenter($record);
    }

    /**
     * حذف حلقة.
     */
    public static function canDelete(Model $record): bool
    {
        return Gate::allows('circles.delete')
            && self::belongsToAllowedCenter($record);
    }
}