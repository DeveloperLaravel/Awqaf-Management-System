<?php

declare(strict_types=1);

namespace App\Filament\Resources\CircleStudents;

use App\Filament\Resources\CircleStudents\Pages;
use App\Filament\Resources\CircleStudents\Schemas\CircleStudentForm;
use App\Filament\Resources\CircleStudents\Tables\CircleStudentsTable;
use App\Models\Center;
use App\Models\CircleStudent;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CircleStudentResource extends Resource
{
    protected static ?string $model = CircleStudent::class;

    protected static ?string $slug = 'circle-students';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الحلقات';

    protected static ?string $navigationLabel = 'تسجيل الطلاب في الحلقات';

    protected static ?string $modelLabel = 'تسجيل طالب';

    protected static ?string $pluralModelLabel = 'تسجيلات الطلاب';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return CircleStudentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CircleStudentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCircleStudents::route('/'),
            'create' => Pages\CreateCircleStudent::route('/create'),
            'edit' => Pages\EditCircleStudent::route('/{record}/edit'),
        ];
    }

    /**
     * المراكز التي يحق للمستخدم الوصول إليها.
     */
    public static function accessibleCenterIds(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        return app(CenterHierarchy::class)
            ->accessibleIdsForUser($user);
    }

    /**
     * المراكز النشطة والمعتمدة فقط.
     */
    public static function getAvailableCenters(): Builder
    {
        return Center::query()
            ->whereIn('id', static::accessibleCenterIds())
            ->where('is_active', true)
            ->where('approval_status', 'active');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'center',
                'circle',
                'student',
            ])
            ->whereIn(
                'center_id',
                static::getAvailableCenters()->select('id')
            );
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('ViewAny:CircleStudent') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('Create:CircleStudent') ?? false;
    }

    public static function canEdit($record): bool
    {
        return (
            auth()->user()?->can('Update:CircleStudent')
            && in_array(
                (int) $record->center_id,
                static::accessibleCenterIds(),
                true
            )
        ) ?? false;
    }

    public static function canDelete($record): bool
    {
        // نحافظ على سجل التسجيلات ولا نحذفها.
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
