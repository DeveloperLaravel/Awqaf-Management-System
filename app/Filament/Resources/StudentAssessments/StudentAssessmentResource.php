<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudentAssessments;

use App\Filament\Resources\StudentAssessments\Pages;
use App\Models\Center;
use App\Models\Circle;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Services\CenterHierarchy;
use BackedEnum;
use UnitEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\StudentAssessments\Schemas\StudentAssessmentForm;
use App\Filament\Resources\StudentAssessments\Tables\StudentAssessmentsTable;
class StudentAssessmentResource extends Resource
{
    protected static ?string $model = StudentAssessment::class;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'تقييمات الطلاب';

    protected static ?string $modelLabel = 'تقييم أسبوعي';

    protected static ?string $pluralModelLabel = 'تقييمات الطلاب';

    protected static UnitEnum|string|null $navigationGroup = 'التقييم والمتابعة';

    protected static ?int $navigationSort = 5;

    /*
    |--------------------------------------------------------------------------
    | Access Control
    |--------------------------------------------------------------------------
    */

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        $centerIds = app(CenterHierarchy::class)
            ->accessibleIdsForUser($user);

        return $query->whereIn('center_id', $centerIds);
    }

    /*
    |--------------------------------------------------------------------------
    | Available Centers
    |--------------------------------------------------------------------------
    */

    public static function availableCenters(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $query = Center::query()
            ->where('is_active', true)
            ->where('approval_status', 'active');

        if (! $user->hasRole('super_admin')) {
            $centerIds = app(CenterHierarchy::class)
                ->accessibleIdsForUser($user);

            $query->whereIn('id', $centerIds);
        }

        return $query
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Form
    |--------------------------------------------------------------------------
    */

public static function form(Schema $schema): Schema
{
    return StudentAssessmentForm::configure($schema);
}

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    */
    public static function table(Table $table): Table
{
    return StudentAssessmentsTable::configure($table);
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentAssessments::route('/'),
            'create' => Pages\CreateStudentAssessment::route('/create'),
            'view' => Pages\ViewStudentAssessment::route('/{record}'),
            'edit' => Pages\EditStudentAssessment::route('/{record}/edit'),
        ];
    }
}
