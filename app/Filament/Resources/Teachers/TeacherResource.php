<?php

namespace App\Filament\Resources\Teachers;

use App\Filament\Resources\Teachers\Pages\CreateTeacher;
use App\Filament\Resources\Teachers\Pages\EditTeacher;
use App\Filament\Resources\Teachers\Pages\ListTeachers;
use App\Models\Teacher;
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
use Filament\Support\Icons\Heroicon;
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
        Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'المعلمين';

    protected static ?string $modelLabel = 'معلم';

    protected static ?string $pluralModelLabel = 'المعلمين';

    // protected static ?string $navigationGroup = 'إدارة مراكز التحفيظ';

    protected static ?int $navigationSort = 3;

    /**
     * التحقق من صلاحية عرض قائمة المعلمين.
     */
    public static function canViewAny(): bool
    {
        return Gate::allows('teachers.view');
    }

    /**
     * التحقق من صلاحية عرض معلم محدد.
     */
    public static function canView(Model $record): bool
    {
        return Gate::allows('teachers.view')
            && static::belongsToAllowedCenter($record);
    }

    /**
     * التحقق من صلاحية إضافة معلم.
     */
    public static function canCreate(): bool
    {
        return Gate::allows('teachers.create');
    }

    /**
     * التحقق من صلاحية تعديل معلم.
     */
    public static function canEdit(Model $record): bool
    {
        return Gate::allows('teachers.update')
            && static::belongsToAllowedCenter($record);
    }

    /**
     * التحقق من صلاحية حذف معلم.
     */
    public static function canDelete(Model $record): bool
    {
        return Gate::allows('teachers.delete')
            && static::belongsToAllowedCenter($record);
    }

    /**
     * تحديد هل المعلم تابع لمركز مسموح للمستخدم بإدارته.
     */
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

    /**
     * نموذج الإضافة والتعديل.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('full_name')
                ->label('اسم المعلم')
                ->required()
                ->maxLength(255)
                ->autocomplete('name'),

            TextInput::make('phone')
                ->label('رقم الهاتف')
                ->tel()
                ->maxLength(30),

            TextInput::make('email')
                ->label('البريد الإلكتروني')
                ->email()
                ->maxLength(255),

            TextInput::make('qualification')
                ->label('المؤهل العلمي')
                ->maxLength(255),

            DatePicker::make('hire_date')
                ->label('تاريخ التعيين')
                ->native(false)
                ->displayFormat('Y-m-d'),

            Select::make('center_id')
                ->label('مركز التحفيظ')
                ->relationship(
                    name: 'center',
                    titleAttribute: 'name',
                )
                ->searchable()
                ->preload()
                ->required()
                ->visible(
                    fn (): bool => Gate::allows('centers.view_all')
                ),

            Select::make('user_id')
                ->label('حساب المستخدم المرتبط')
                ->relationship(
                    name: 'user',
                    titleAttribute: 'name',
                    modifyQueryUsing: function (Builder $query) {
                        if (! Gate::allows('centers.view_all')) {
                            $centerId = Auth::user()?->center_id;

                            if ($centerId === null) {
                                return $query->whereRaw('1 = 0');
                            }

                            $query->where('center_id', $centerId);
                        }

                        return $query->orderBy('name');
                    },
                )
                ->searchable()
                ->preload()
                ->nullable()
                ->helperText(
                    'اختياري: ربط المعلم بحساب دخول إلى النظام.'
                ),

            Toggle::make('is_active')
                ->label('حساب المعلم نشط')
                ->default(true)
                ->required(),
        ]);
    }

    /**
     * جدول المعلمين.
     */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('اسم المعلم')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->placeholder('غير محدد'),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->placeholder('غير محدد')
                    ->toggleable(),

                TextColumn::make('center.name')
                    ->label('المركز')
                    ->sortable()
                    ->placeholder('غير محدد'),

                TextColumn::make('user.name')
                    ->label('حساب المستخدم')
                    ->placeholder('غير مرتبط')
                    ->toggleable(),

                TextColumn::make('qualification')
                    ->label('المؤهل')
                    ->placeholder('غير محدد')
                    ->toggleable(),

                TextColumn::make('hire_date')
                    ->label('تاريخ التعيين')
                    ->date('Y-m-d')
                    ->sortable()
                    ->placeholder('غير محدد'),

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
                        fn (Teacher $record): bool =>
                            static::canEdit($record)
                    ),

                DeleteAction::make()
                    ->label('حذف')
                    ->visible(
                        fn (Teacher $record): bool =>
                            static::canDelete($record)
                    )
                    ->requiresConfirmation()
                    ->modalHeading('حذف المعلم')
                    ->modalDescription(
                        'هل أنت متأكد من حذف هذا المعلم؟'
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

    /**
     * عزل السجلات حسب مركز المستخدم.
     */
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
            'index' => ListTeachers::route('/'),
            'create' => CreateTeacher::route('/create'),
            'edit' => EditTeacher::route('/{record}/edit'),
        ];
    }
}
