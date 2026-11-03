<?php

namespace App\Filament\Resources\Guardians;

use App\Filament\Resources\Guardians\Pages;
use App\Models\Center;
use App\Models\Guardian;
use App\Services\CenterHierarchy;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class GuardianResource extends Resource
{
    protected static ?string $model = Guardian::class;

    protected static ?string $modelLabel = 'ولي أمر';

    protected static ?string $pluralModelLabel = 'أولياء الأمور';

    protected static ?string $navigationLabel = 'أولياء الأمور';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة التعليم';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Section::make('بيانات ولي الأمر')
                ->schema([
                Select::make('center_id')
    ->label('المركز / الفرع')
    ->options(fn () => self::availableCenters())
    ->default(fn () => auth()->user()?->center_id)
    ->searchable()
    ->preload()
    ->required()
    ->validationMessages([
        'required' => 'يرجى اختيار المركز أو الفرع.',
    ]),

                    TextInput::make('full_name')
                        ->label('الاسم الكامل')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('phone')
                        ->label('رقم الهاتف')
                        ->tel()
                        ->maxLength(30),

                    TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->maxLength(255),

                    TextInput::make('national_id')
                        ->label('الرقم الوطني')
                        ->maxLength(30),

                    TextInput::make('occupation')
                        ->label('المهنة')
                        ->maxLength(255),

                    Textarea::make('address')
                        ->label('العنوان')
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->label('ولي الأمر نشط')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('اسم ولي الأمر')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('center.name')
                    ->label('المركز')
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),

                TextColumn::make('national_id')
                    ->label('الرقم الوطني')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('center_id')
                    ->label('المركز')
                    ->options(fn () => self::availableCenters()),

                SelectFilter::make('is_active')
                    ->label('الحالة')
                    ->options([
                        1 => 'نشط',
                        0 => 'غير نشط',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('center_id', self::accessibleCenterIds());
    }

    protected static function accessibleCenterIds(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->hasRole('super_admin')) {
            return Center::query()->pluck('id')->all();
        }

        return app(CenterHierarchy::class)
            ->accessibleIdsForUser($user);
    }

 protected static function availableCenters(): array
{
    return Center::query()
        ->whereIn('id', self::accessibleCenterIds())
        ->where('is_active', true)
        ->where('approval_status', 'active')
        ->orderBy('name')
        ->pluck('name', 'id')
        ->all();
}
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuardians::route('/'),
            'create' => Pages\CreateGuardian::route('/create'),
            'view' => Pages\ViewGuardian::route('/{record}'),
            'edit' => Pages\EditGuardian::route('/{record}/edit'),
        ];
    }
}
