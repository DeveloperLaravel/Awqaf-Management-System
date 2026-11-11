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

            /*
             * بيانات الحلقة.
             */
            Section::make('بيانات الحلقة')
                ->description('بيانات المركز والحلقة والمعلم المسؤول.')
                ->schema([

                    /*
                     * المركز.
                     */
                    Select::make('center_id')
                        ->label('مركز التحفيظ')
                        ->relationship(
                            name: 'center',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query) =>
                                self::centersQuery($query),
                        )
                        ->default(fn () => self::defaultCenterId())
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

                    /*
                     * المعلم المسؤول.
                     */
                 Select::make('teacher_id')
    ->label('المعلم المسؤول')
    ->options(
        fn (Get $get): array =>
            self::teachersOptions($get('center_id'))
    )
    ->searchable()
    ->preload()
    ->nullable()
    ->required(false)
    ->placeholder('اختر المعلم أو اتركه بدون تعيين')
    ->disabled(fn (Get $get): bool =>
        blank($get('center_id'))
    )
    ->visible(fn (): bool =>
        ! Auth::user()?->hasRole('teacher')
    )
    ->rules([
        'nullable',
        'integer',
        Rule::exists('teachers', 'id'),
    ]),
                    /*
                     * اسم الحلقة.
                     */
              TextInput::make('name')
    ->label('اسم الحلقة')
    ->required()
    ->maxLength(255)
    ->rules(function (Get $get, ?Circle $record) {
        return [
            Rule::unique('circles', 'name')
                ->where(
                    'center_id',
                    $get('center_id') ?? $record?->center_id
                )
                ->ignore($record?->id),
        ];
    }),

TextInput::make('code')
    ->label('رمز الحلقة')
    ->required()
    ->maxLength(50)
    ->rules(function (Get $get, ?Circle $record) {
        return [
            Rule::unique('circles', 'code')
                ->where(
                    'center_id',
                    $get('center_id') ?? $record?->center_id
                )
                ->ignore($record?->id),
        ];
    }),
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

            /*
             * جدول الحلقة.
             */
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

            /*
             * حالة الحلقة.
             */
            Section::make('حالة الحلقة')
                ->schema([
 Toggle::make('is_active')
            ->label('الحلقة نشطة')
            ->default(true)
            ->required()
            ->visible(fn (): bool =>
                ! Auth::user()?->hasRole('teacher')
            ),

                ]),

        ]);
    }

    /**
     * الأدوار التي يسمح لها باختيار المركز.
     */
    protected static function canSelectCenter(): bool
    {
        $user = Auth::user();

        return $user !== null
            && $user->hasAnyRole([
                'super_admin',
                'center_admin',
                'center_manager',
            ]);
    }

    /**
     * المركز الافتراضي.
     */
    protected static function defaultCenterId(): ?int
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if ($user->hasRole('super_admin')) {
            return $user->center_id
                ? (int) $user->center_id
                : null;
        }

        $centerIds = self::allowedCenterIds();

        $userCenterId = (int) ($user->center_id ?? 0);

        if (
            $userCenterId > 0
            && in_array($userCenterId, $centerIds, true)
        ) {
            return $userCenterId;
        }

        return count($centerIds) === 1
            ? $centerIds[0]
            : null;
    }

    /**
     * المراكز المسموح للمستخدم باختيارها.
     */
    protected static function allowedCenterIds(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        if ($user->hasRole('super_admin')) {
            return Center::query()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($user->hasAnyRole([
            'center_admin',
            'center_manager',
        ])) {
            return array_map(
                'intval',
                app(CenterHierarchy::class)
                    ->accessibleIdsForUser($user)
            );
        }

        return [];
    }

    /**
     * فلترة المراكز في القائمة.
     */
    protected static function centersQuery(
        Builder $query
    ): Builder {

        $centerIds = self::allowedCenterIds();

        if (empty($centerIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('centers.id', $centerIds);
    }

    /**
     * المعلمون النشطون المرتبطون بالمركز.
     */
    protected static function teachersOptions(
        mixed $centerId
    ): array {

        if (blank($centerId)) {
            return [];
        }

        $centerId = (int) $centerId;

        // لا نعرض معلمين لمركز خارج نطاق المستخدم.
        if (! in_array(
            $centerId,
            self::allowedCenterIds(),
            true
        )) {
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
     * معرف الحلقة الحالية عند التعديل.
     */
    protected static function currentRecordId(): ?int
    {
        $record = request()->route('record');

        if ($record instanceof Circle) {
            return (int) $record->getKey();
        }

        if (is_numeric($record)) {
            return (int) $record;
        }

        return null;
    }
}
