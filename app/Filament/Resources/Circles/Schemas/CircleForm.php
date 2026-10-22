<?php

declare(strict_types=1);

namespace App\Filament\Resources\Circles\Schemas;

use App\Models\Center;
use App\Models\Circle;
use App\Models\Teacher;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CircleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات الحلقة')
                ->description('بيانات مركز التحفيظ والحلقة والمعلم المسؤول.')
                ->schema([
                    Select::make('center_id')
                        ->label('مركز التحفيظ')
                        ->relationship(
                            name: 'center',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query) =>
                                self::centersQuery($query),
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->visible(fn (): bool =>
                            self::isSuperAdmin()
                        )
                        ->rules([
                            'required',
                            'integer',
                            Rule::exists('centers', 'id'),
                        ]),

                    Select::make('teacher_id')
                        ->label('المعلم المسؤول')
                        ->relationship(
                            name: 'teacher',
                            titleAttribute: 'full_name',
                            modifyQueryUsing: fn (Builder $query) =>
                                self::teachersQuery($query),
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->rules([
                            'required',
                            'integer',
                            Rule::exists('teachers', 'id'),
                        ]),

                    TextInput::make('name')
                        ->label('اسم الحلقة')
                        ->required()
                        ->maxLength(255)
                        ->rules([
                            'unique:circles,name,' .
                                request()->route('record') .
                                ',id,center_id,' .
                                (self::isSuperAdmin()
                                    ? request()->input('data.center_id')
                                    : (Auth::user()?->center_id ?? 0)),
                        ]),

                    TextInput::make('code')
                        ->label('رمز الحلقة')
                        ->required()
                        ->maxLength(50)
                        ->rules([
                            'unique:circles,code,' .
                                request()->route('record') .
                                ',id,center_id,' .
                                (self::isSuperAdmin()
                                    ? request()->input('data.center_id')
                                    : (Auth::user()?->center_id ?? 0)),
                        ]),

                    Select::make('level')
                        ->label('مستوى الحلقة')
                        ->options([
                            'beginner' => 'مبتدئ',
                            'intermediate' => 'متوسط',
                            'advanced' => 'متقدم',
                        ])
                        ->searchable()
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
                    \Filament\Forms\Components\Toggle::make('is_active')
                        ->label('الحلقة نشطة')
                        ->default(true)
                        ->required(),
                ]),
        ]);
    }

    protected static function isSuperAdmin(): bool
    {
        return request()->user()?->hasRole('super_admin') ?? false;
    }

    protected static function centersQuery(Builder $query): Builder
    {
        $user = request()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        if (
            $user->hasRole('center_manager')
            && filled($user->center_id)
        ) {
            return $query->whereKey($user->center_id);
        }

        return $query->whereRaw('1 = 0');
    }

    protected static function teachersQuery(Builder $query): Builder
    {
        $user = request()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        if (
            $user->hasRole('center_manager')
            && filled($user->center_id)
        ) {
            return $query->where(
                'center_id',
                $user->center_id
            );
        }

        if ($user->hasRole('teacher')) {
            return $query->where('user_id', $user->getKey());
        }

        return $query->whereRaw('1 = 0');
    }
}
