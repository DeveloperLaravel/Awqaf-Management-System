<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuardianStudents;

use App\Filament\Resources\GuardianStudents\Pages;
use App\Models\GuardianStudent;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Services\CenterHierarchy;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;


class GuardianStudentResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = GuardianStudent::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'ربط الطلاب بأولياء الأمور';

    protected static ?string $modelLabel = 'ربط طالب بولي أمر';

    protected static ?string $pluralModelLabel = 'روابط الطلاب وأولياء الأمور';

    protected static ?string $slug = 'guardian-students';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الطلاب';

    protected static ?int $navigationSort = 5;


    public static function getPermissionPrefixes(): array
{
    return [
        'view',
        'view_any',
        'create',
        'update',
        'delete',
        'delete_any',
    ];
}
    public static function form(Schema $schema): Schema
    {
        return \App\Filament\Resources\GuardianStudents\Schemas\GuardianStudentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return \App\Filament\Resources\GuardianStudents\Tables\GuardianStudentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
{
    $user = auth()->user();

    if (! $user) {
        return parent::getEloquentQuery()->whereRaw('1 = 0');
    }

    $centerIds = app(CenterHierarchy::class)
        ->accessibleIdsForUser($user);

    return parent::getEloquentQuery()
        ->whereIn('center_id', $centerIds);
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuardianStudents::route('/'),
            'create' => Pages\CreateGuardianStudent::route('/create'),
            'edit' => Pages\EditGuardianStudent::route('/{record}/edit'),
        ];
    }
}