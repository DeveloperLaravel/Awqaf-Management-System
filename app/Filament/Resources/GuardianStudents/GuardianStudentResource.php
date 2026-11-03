<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuardianStudents;

use App\Filament\Resources\GuardianStudents\Pages;
use App\Models\GuardianStudent;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class GuardianStudentResource extends Resource
{
    protected static ?string $model = GuardianStudent::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'ربط الطلاب بأولياء الأمور';

    protected static ?string $modelLabel = 'ربط طالب بولي أمر';

    protected static ?string $pluralModelLabel = 'روابط الطلاب وأولياء الأمور';

    protected static ?string $slug = 'guardian-students';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة الطلاب';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return \App\Filament\Resources\GuardianStudents\Schemas\GuardianStudentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return \App\Filament\Resources\GuardianStudents\Tables\GuardianStudentsTable::configure($table);
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