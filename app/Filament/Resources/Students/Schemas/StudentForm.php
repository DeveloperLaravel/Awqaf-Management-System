<?php

namespace App\Filament\Resources\Students\Schemas;

use App\Models\Center;
use App\Models\Guardian;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // المركز
            Select::make('center_id')
                ->label('مركز التحفيظ')
                ->options(fn (): array => Center::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all()
                )
                ->searchable()
                ->preload()
                ->live()
                ->visible(
                    fn (): bool => Gate::allows('centers.view_all')
                )
                ->required(
                    fn (): bool => Gate::allows('centers.view_all')
                )
                ->default(fn () => Auth::user()?->center_id)
                ->dehydrated(),

            // رقم الطالب
            TextInput::make('student_code')
                ->label('رقم الطالب')
                ->required()
                ->maxLength(50)
                ->rules(function (Get $get, ?Model $record): array {
                    $centerId = Gate::allows('centers.view_all')
                        ? $get('center_id')
                        : Auth::user()?->center_id;

                    $rule = Rule::unique('students', 'student_code')
                        ->ignore($record?->getKey());

                    if ($centerId !== null) {
                        $rule->where('center_id', $centerId);
                    }

                    return [$rule];
                }),

            // الاسم الكامل
            TextInput::make('full_name')
                ->label('اسم الطالب بالكامل')
                ->required()
                ->maxLength(255),

            // الجنس
            Select::make('gender')
                ->label('الجنس')
                ->options([
                    'male' => 'ذكر',
                    'female' => 'أنثى',
                ])
                ->required(),

            // تاريخ الميلاد
            DatePicker::make('birth_date')
                ->label('تاريخ الميلاد')
                ->native(false)
                ->maxDate(now())
                ->closeOnDateSelection()
                ->nullable(),

            // تاريخ التسجيل
            DatePicker::make('enrollment_date')
                ->label('تاريخ التسجيل')
                ->native(false)
                ->default(now())
                ->required(),

            // المرحلة الدراسية
            TextInput::make('school_grade')
                ->label('المرحلة الدراسية')
                ->maxLength(100)
                ->nullable(),

            // ولي الأمر
            Select::make('guardian_id')
                ->label('ولي الأمر')
                ->options(function (Get $get): array {
                    $user = Auth::user();

                    if (! $user) {
                        return [];
                    }

                    $centerId = Gate::allows('centers.view_all')
                        ? $get('center_id')
                        : $user->center_id;

                    if (! $centerId) {
                        return [];
                    }

                    return Guardian::query()
                        ->where('center_id', $centerId)
                        ->where('is_active', true)
                        ->orderBy('full_name')
                        ->pluck('full_name', 'id')
                        ->all();
                })
                ->searchable()
                ->preload()
                ->live()
                ->nullable()
                ->disabled(fn (Get $get): bool => ! (
                    Gate::allows('centers.view_all')
                        ? $get('center_id')
                        : Auth::user()?->center_id
                ))
                ->dehydrated(),

            // الملاحظات
            Textarea::make('notes')
                ->label('ملاحظات')
                ->rows(4)
                ->columnSpanFull()
                ->nullable(),

            // حالة الطالب
            Toggle::make('is_active')
                ->label('الطالب نشط')
                ->default(true)
                ->required(),
        ]);
    }
}
