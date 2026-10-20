<?php

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Models\Student;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Unique;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static string|\BackedEnum|null $navigationIcon =
        Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'الطلاب';

    protected static ?string $modelLabel = 'طالب';

    protected static ?string $pluralModelLabel = 'الطلاب';

    // protected static ?string $navigationGroup = 'إدارة مراكز التحفيظ';

    protected static ?int $navigationSort = 2;

    // =========================
    // Gate Authorization
    // =========================

    public static function canViewAny(): bool
    {
        return Gate::allows('students.view');
    }

    public static function canView(Model $record): bool
    {
        return Gate::allows('students.view')
            && static::belongsToAllowedCenter($record);
    }

    public static function canCreate(): bool
    {
        return Gate::allows('students.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Gate::allows('students.update')
            && static::belongsToAllowedCenter($record);
    }

    public static function canDelete(Model $record): bool
    {
        return Gate::allows('students.delete')
            && static::belongsToAllowedCenter($record);
    }

    protected static function belongsToAllowedCenter(
        Model $record
    ): bool {
        if (Gate::allows('centers.view_all')) {
            return true;
        }

        $centerId = Auth::user()?->center_id;

        return $centerId !== null
            && (string) $record->center_id === (string) $centerId;
    }

    // =========================
    // Form
    // =========================

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
           TextInput::make('student_code')
    ->label('رقم الطالب')
    ->required()
    ->maxLength(50)
    ->unique(
        table: 'students',
        column: 'student_code',
        ignoreRecord: true,
        modifyRuleUsing: function (Unique $rule) {
            $user = Auth::user();

            if (
                $user?->center_id !== null
                && ! Gate::allows('centers.view_all')
            ) {
                $rule->where('center_id', $user->center_id);
            }

            return $rule;
        },
    ),

            TextInput::make('full_name')
                ->label('اسم الطالب بالكامل')
                ->required()
                ->maxLength(255)
                ->autocomplete('name'),

            Select::make('center_id')
                ->label('مركز التحفيظ')
                ->relationship(
                    name: 'center',
                    titleAttribute: 'name',
                    modifyQueryUsing: function (Builder $query) {
                        if (! Gate::allows('centers.view_all')) {
                            $centerId = Auth::user()?->center_id;

                            if ($centerId === null) {
                                return $query->whereRaw('1 = 0');
                            }

                            $query->whereKey($centerId);
                        }

                        return $query->orderBy('name');
                    },
                )
                ->searchable()
                ->preload()
                ->required()
                ->visible(
                    fn (): bool => Gate::allows('centers.view_all')
                ),

            Select::make('guardian_id')
                ->label('ولي الأمر')
                ->relationship(
                    name: 'guardian',
                    titleAttribute: 'full_name',
                    modifyQueryUsing: function (Builder $query) {
                        if (! Gate::allows('centers.view_all')) {
                            $centerId = Auth::user()?->center_id;

                            if ($centerId === null) {
                                return $query->whereRaw('1 = 0');
                            }

                            $query->where('center_id', $centerId);
                        }

                        return $query->orderBy('full_name');
                    },
                )
                ->searchable()
                ->preload()
                ->nullable(),

            DatePicker::make('birth_date')
                ->label('تاريخ الميلاد')
                ->native(false)
                ->displayFormat('Y-m-d')
                ->maxDate(now()),

            DatePicker::make('enrollment_date')
                ->label('تاريخ التسجيل')
                ->native(false)
                ->displayFormat('Y-m-d')
                ->required()
                ->default(now()),

            Select::make('gender')
                ->label('الجنس')
                ->options([
                    'male' => 'ذكر',
                    'female' => 'أنثى',
                ])
                ->required(),

            TextInput::make('school_grade')
                ->label('الصف الدراسي')
                ->maxLength(100),

            Textarea::make('notes')
                ->label('ملاحظات')
                ->rows(3)
                ->columnSpanFull(),

            Toggle::make('is_active')
                ->label('الطالب نشط')
                ->default(true)
                ->required(),
        ]);
    }

    // =========================
    // Table
    // =========================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student_code')
                    ->label('رقم الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('full_name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('center.name')
                    ->label('المركز')
                    ->sortable(),

                TextColumn::make('guardian.full_name')
                    ->label('ولي الأمر')
                    ->placeholder('غير محدد')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('gender')
                    ->label('الجنس')
                    ->formatStateUsing(
                        fn ($state): string => match ($state) {
                            'male' => 'ذكر',
                            'female' => 'أنثى',
                            default => (string) $state,
                        }
                    )
                    ->badge(),

                TextColumn::make('school_grade')
                    ->label('الصف الدراسي')
                    ->placeholder('غير محدد'),

                TextColumn::make('enrollment_date')
                    ->label('تاريخ التسجيل')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('is_active')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state): string =>
                            $state ? 'نشط' : 'غير نشط'
                    )
                    ->color(
                        fn ($state): string =>
                            $state ? 'success' : 'danger'
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->visible(
                        fn (Student $record): bool =>
                            static::canEdit($record)
                    ),

                DeleteAction::make()
                    ->label('حذف')
                    ->visible(
                        fn (Student $record): bool =>
                            static::canDelete($record)
                    )
                    ->requiresConfirmation()
                    ->modalHeading('حذف الطالب')
                    ->modalDescription(
                        'هل أنت متأكد من حذف هذا الطالب؟'
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف المحدد')
                        ->visible(false),
                ]),
            ]);
    }

    // =========================
    // Center Isolation
    // =========================

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Gate::allows('centers.view_all')) {
            return $query;
        }

        $centerId = Auth::user()?->center_id;

        if ($centerId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('center_id', $centerId);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'edit' => EditStudent::route('/{record}/edit'),
        ];
    }
}
