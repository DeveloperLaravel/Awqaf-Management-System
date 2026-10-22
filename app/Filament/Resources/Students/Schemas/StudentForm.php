<?php

namespace App\Filament\Resources\Students\Schemas;

use App\Models\Center;
use App\Models\Guardian;
use App\Support\StudentCenterAccess;
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
use Illuminate\Database\Eloquent\Builder;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

          
     
Select::make('center_id')
    ->label('المركز الفرعي')
    ->relationship(
        name: 'center',
        titleAttribute: 'name',
        modifyQueryUsing: function (Builder $query): Builder {
            $user = request()->user();

            if (! $user) {
                return $query->whereRaw('1 = 0');
            }

            $branchIds = StudentCenterAccess::branchIdsFor($user);

            if ($branchIds === []) {
                return $query->whereRaw('1 = 0');
            }

            return $query
                ->whereIn('id', $branchIds)
                ->orderBy('name');
        }
    )
    ->default(function (): ?int {
        $user = request()->user();

        if (! $user || ! $user->hasRole('center_manager')) {
            return null;
        }

        $branchIds = StudentCenterAccess::branchIdsFor($user);

        // التحديد التلقائي فقط عندما يكون للمستخدم فرع واحد متاح.
        if (count($branchIds) === 1) {
            return $branchIds[0];
        }

        if (
            $user->center_id !== null
            && in_array((int) $user->center_id, $branchIds, true)
        ) {
            return (int) $user->center_id;
        }

        return null;
    })
   ->disabled(function (string $operation): bool {
    $user = request()->user();

    if (! $user?->hasRole('center_manager')) {
        return false;
    }

    return $operation === 'edit'
        || count(StudentCenterAccess::branchIdsFor($user)) <= 1;}),

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
