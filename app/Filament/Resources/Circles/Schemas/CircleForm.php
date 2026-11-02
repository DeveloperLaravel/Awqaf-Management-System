<?php

declare(strict_types=1);

namespace App\Filament\Resources\Circles\Schemas;

use App\Models\Center;
use App\Models\Circle;
use App\Models\Teacher;
use App\Services\CenterHierarchy;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CircleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('بيانات الحلقة')
                ->description('بيانات المركز والحلقة والمعلم المسؤول.')
                ->schema([

                    Select::make('center_id')
                        ->label('مركز التحفيظ')
                        ->relationship(
                            name: 'center',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query) =>
                                self::centersQuery($query),
                        )
                        ->default(fn () => Auth::user()?->center_id)
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->visible(fn (): bool =>
                            self::canSelectCenter()
                        )
                        ->afterStateUpdated(
                            fn (Set $set) => $set('teacher_id', null)
                        )
                        ->rules([
                            'required',
                            'integer',
                            Rule::exists('centers', 'id'),
                        ]),

                    Select::make('teacher_id')
                        ->label('المعلم المسؤول')
                        ->options(
                            fn (Get $get): array =>
                                self::teachersOptions($get('center_id'))
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabled(fn (Get $get): bool =>
                            blank($get('center_id'))
                        )
                        ->rules([
                            'required',
                            'integer',
                            Rule::exists('teachers', 'id'),
                        ]),

                    TextInput::make('name')
                        ->label('اسم الحلقة')
                        ->required()
                        ->maxLength(255)
                        ->rules(
                            fn (Get $get): array => [
                                Rule::unique('circles', 'name')
                                    ->where(
                                        'center_id',
                                        $get('center_id')
                                    )
                                    ->ignore(self::currentRecordId()),
                            ]
                        ),

                    TextInput::make('code')
                        ->label('رمز الحلقة')
                        ->required()
                        ->maxLength(50)
                        ->rules(
                            fn (Get $get): array => [
                                Rule::unique('circles', 'code')
                                    ->where(
                                        'center_id',
                                        $get('center_id')
                                    )
                                    ->ignore(self::currentRecordId()),
                            ]
                        ),

                    Select::make('level')
                        ->label('مستوى الحلقة')
                        ->options([
                            'beginner' => 'مبتدئ',
                            'intermediate' => 'متوسط',
                            'advanced' => 'متقدم',
                        ])
                        ->required(),

                    TextInput::make('room')
                        ->label('القاعة')
                        ->maxLength(100),

                    TextInput::make('capacity')
                        ->label('السعة الاستيعابية')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->default(20)
                        ->required(),

                ])
                ->columns(2),

            Section::make('جدول الحلقة')
                ->schema([

                    TimePicker::make('starts_at')
                        ->label('وقت البداية')
                        ->seconds(false),

                    TimePicker::make('ends_at')
                        ->label('وقت النهاية')
                        ->seconds(false),

                    Select::make('days')
                        ->label('أيام الدراسة')
                        ->multiple()
                        ->options([
                            'saturday' => 'السبت',
                            'sunday' => 'الأحد',
                            'monday' => 'الاثنين',
                            'tuesday' => 'الثلاثاء',
                            'wednesday' => 'الأربعاء',
                            'thursday' => 'الخميس',
                            'friday' => 'الجمعة',
                        ])
                        ->required(),

                ])
                ->columns(2),

            Section::make('حالة الحلقة')
                ->schema([

                    Toggle::make('is_active')
                        ->label('الحلقة نشطة')
                        ->default(true)
                        ->required(),

                ]),

        ]);
    }

    /**
     * السماح باختيار المركز للمدير العام ومدير المركز.
     */
    protected static function canSelectCenter(): bool
    {
        $user = Auth::user();

        return $user !== null
            && (
                $user->hasRole('super_admin')
                || $user->hasRole('center_manager')
            );
    }

    /**
     * المراكز التي يستطيع المستخدم الوصول إليها.
     */
    protected static function centersQuery(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        if ($user->hasRole('center_manager')) {
            $centerIds = collect(
                app(CenterHierarchy::class)
                    ->accessibleIdsForUser($user)
            )->all();

            return empty($centerIds)
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('centers.id', $centerIds);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * المعلمون النشطون المرتبطون بالمركز المختار عبر center_teacher.
     */
    protected static function teachersOptions(
        mixed $centerId
    ): array {
        if (blank($centerId)) {
            return [];
        }

        return Teacher::query()
            ->where('teachers.is_active', true)
            ->whereHas(
                'centers',
                fn (Builder $query) => $query
                    ->where('centers.id', $centerId)
                    ->where('center_teacher.is_active', true)
            )
            ->orderBy('full_name')
            ->pluck('full_name', 'id')
            ->all();
    }

    /**
     * معرف السجل الحالي عند التعديل.
     */
    protected static function currentRecordId(): ?int
    {
        $record = request()->route('record');

        if ($record instanceof Circle) {
            return $record->getKey();
        }

        if (is_numeric($record)) {
            return (int) $record;
        }

        return null;
    }
}