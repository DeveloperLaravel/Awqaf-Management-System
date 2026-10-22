<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers;

use App\Filament\Resources\Teachers\Pages;
use App\Filament\Resources\Teachers\Schemas\TeacherForm;
use App\Filament\Resources\Teachers\Tables\TeachersTable;
use App\Models\Center;
use App\Models\Teacher;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-academic-cap';

    protected static \UnitEnum|string|null $navigationGroup =
        'إدارة  الوسط';
      protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'معلم';

    protected static ?string $pluralModelLabel = 'المعلمون';

    protected static ?string $navigationLabel = 'المعلمون';

    /**
     * المراكز التي يسمح للمستخدم بإدارتها.
     *
     * super_admin الذي يملك centers.view_all:
     * جميع المراكز.
     *
     * مدير المركز الرئيسي:
     * مركزه والفروع التابعة له.
     *
     * مدير المركز الفرعي:
     * مركزه فقط.
     */
    public static function allowedCenterIds(): array
    {
        $user = request()->user();

        if (! $user) {
            return [];
        }

     
        
if (
    $user->hasRole('super_admin')
    && Gate::allows('teachers.manage_all')
) {
    return Center::query()
        ->pluck('id')
        ->map(fn ($id): int => (int) $id)
        ->all();
}





        if (! $user->center_id) {
            return [];
        }

        $allowedIds = [(int) $user->center_id];
        $pendingIds = $allowedIds;

        /*
         * جلب جميع الفروع التابعة للمركز،
         * بما فيها الفروع المتداخلة.
         *
         * يفترض وجود parent_center_id في جدول centers.
         */
        while ($pendingIds !== []) {
            $children = Center::query()
                ->whereIn('parent_center_id', $pendingIds)
                ->whereNotIn('id', $allowedIds)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            if ($children === []) {
                break;
            }

            $allowedIds = array_values(
                array_unique(array_merge($allowedIds, $children))
            );

            $pendingIds = $children;
        }

        return $allowedIds;
    }

    /**
     * هل يملك المستخدم صلاحية إدارة المعلم
     * بناءً على المراكز المرتبط بها المعلم؟
     */
    protected static function belongsToAllowedCenter(
        Teacher $record
    ): bool {
        $allowedIds = static::allowedCenterIds();

        if ($allowedIds === []) {
            return false;
        }

        return $record->centers()
            ->whereIn('centers.id', $allowedIds)
            ->exists();
    }

    /**
     * عرض قائمة المعلمين.
     */
    public static function canViewAny(): bool
    {
        return Gate::allows('teachers.view');
    }

    /**
     * عرض سجل معلم معين.
     */
    // public static function canView(Model $record): bool
    // {
    //     return $record instanceof Teacher
    //         && Gate::allows('teachers.view')
    //         && static::belongsToAllowedCenter($record);
    // }
public static function canView(Model $record): bool
{
    return $record instanceof Teacher
        && Gate::allows('view', $record);
}

    /**
     * إنشاء معلم.
     */
    public static function canCreate(): bool
    {
        return Gate::allows('teachers.create')
            && static::allowedCenterIds() !== [];
    }

    /**
     * تعديل معلم.
     */
    // public static function canEdit(Model $record): bool
    // {
    //     return $record instanceof Teacher
    //         && Gate::allows('teachers.update')
    //         && static::belongsToAllowedCenter($record);
    // }
    public static function canEdit(Model $record): bool
{
    return $record instanceof Teacher
        && Gate::allows('update', $record);
}

    /**
     * حذف معلم.
     */
  
public static function canDelete(Model $record): bool
{
    return $record instanceof Teacher
        && Gate::allows('delete', $record);
}
    /**
     * نموذج المعلم.
     */
public static function form(Schema $schema): Schema
{
    return TeacherForm::configure($schema);
}

    /**
     * جدول المعلمين.
     */
   public static function table(Table $table): Table
{
    return TeachersTable::configure($table);
}

    /**
     * نطاق السجلات المعروضة للمستخدم.
     */

   
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery()
        ->with(['centers', 'user']);

    $user = request()->user();

    if (! $user) {
        return $query->whereRaw('1 = 0');
    }

    // السماح بالوصول العام فقط للمخول من super_admin.
    if (
        $user->hasRole('super_admin')
        && $user->can('teachers.manage_all')
    ) {
        return $query;
    }

    $allowedIds = static::allowedCenterIds();

    if ($allowedIds === []) {
        return $query->whereRaw('1 = 0');
    }

    return $query->whereHas(
        'centers',
        fn (Builder $centerQuery): Builder =>
            $centerQuery
                ->whereIn('centers.id', $allowedIds)
                ->where('center_teacher.is_active', true)
    );
}


    /**
     * صفحات المورد.
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'edit' => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }
}
