<?php

declare(strict_types=1);

namespace App\Filament\Resources\CircleStudents\Schemas;

use App\Models\Circle;
use App\Models\CircleStudent;
use App\Models\Student;
use App\Filament\Resources\CircleStudents\CircleStudentResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
class CircleStudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('center_id')
                    ->label('المركز')
                    ->options(fn () => CircleStudentResource::getAvailableCenters()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->disabled(fn (?CircleStudent $record) => $record !== null)
                    ->afterStateUpdated(function ($set) {
                        $set('circle_id', null);
                        $set('student_id', null);
                    }),

                Select::make('circle_id')
                    ->label('الحلقة')
                    ->options(function (Get $get, ?CircleStudent $record) {
                        $centerId = $get('center_id');

                        if (! $centerId) {
                            return [];
                        }

                        return Circle::query()
                            ->where('center_id', $centerId)
                            ->where(function ($query) use ($record) {
                                $query->where('is_active', true);

                                if ($record) {
                                    $query->orWhere('id',$record->circle_id);
                                }
                            })
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required()
                    ->disabled(fn (Get $get, ?CircleStudent $record) =>
                        ! $get('center_id') || $record !== null
                    )
                    ->afterStateUpdated(fn ($set) =>
                        $set('student_id', null)
                    ),

             // اختيار عدة طلاب عند إنشاء التسجيلات.
Select::make('student_ids')
    ->label('الطلاب')
    ->multiple()
    ->options(function (Get $get) {
        $centerId = $get('center_id');

        if (! $centerId) {
            return [];
        }

        return Student::query()
            ->where('center_id', $centerId)
            ->whereNotIn(
                'id',
                CircleStudent::query()
                    ->select('student_id')
                    ->whereNull('left_at')
            )
            ->orderBy('full_name')
            ->pluck('full_name', 'id')
            ->all();
    })
    ->searchable()
    ->preload()
    ->required()
    ->visible(fn (?CircleStudent $record) => $record === null)
    ->disabled(fn (Get $get) =>
        ! $get('center_id') || ! $get('circle_id')
    ),

// اختيار الطالب الحالي عند تعديل التسجيل.
Select::make('student_id')
    ->label('الطالب')
    ->options(function (Get $get, ?CircleStudent $record) {
        if (! $record) {
            return [];
        }

        return Student::query()
            ->where('center_id', $record->center_id)
            ->whereKey($record->student_id)
            ->pluck('full_name', 'id')
            ->all();
    })
    ->disabled()
    ->visible(fn (?CircleStudent $record) => $record !== null),

                DatePicker::make('joined_at')
                    ->label('تاريخ الانضمام')
                    ->default(now()->toDateString())
                    ->native(false)
                    ->required(),

                DatePicker::make('left_at')
                    ->label('تاريخ مغادرة الحلقة')
                    ->native(false)
                    ->placeholder('اتركه فارغاً إذا كان التسجيل نشطاً')
                    ->helperText('عند تحديد التاريخ يصبح تسجيل الطالب منتهياً.')
                    ->nullable()
                    ->visible(fn (?CircleStudent $record) => $record !== null),
            ]);
    }
}
