<?php

declare(strict_types=1);

namespace App\Filament\Resources\AttendanceRecords\Schemas;

use App\Models\AttendanceRecord;
use App\Models\Circle;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\AttendanceRecords\AttendanceRecordResource;
class AttendanceRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('center_id')
            ->label('المركز')
->options(fn (): array => AttendanceRecordResource::centerOptions())
->default(fn (): ?int => AttendanceRecordResource::defaultCenterId())
            ->searchable()
            ->preload()
            ->live()
            ->required()
            ->afterStateUpdated(function (Set $set): void {
                $set('student_id', null);
                $set('circle_id', null);
            }),

        Select::make('circle_id')
            ->label('الحلقة')
            ->options(function (Get $get): array {
                $centerId = $get('center_id');

                if (! $centerId) {
                    return [];
                }

                return AttendanceRecordResource::allowedCirclesQuery()
                    ->where('center_id', $centerId)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all();
            })
            ->searchable()
            ->preload()
            ->live()
            ->required()
            ->disabled(fn (Get $get): bool => ! $get('center_id'))
            ->afterStateUpdated(function (Set $set): void {
                $set('student_id', null);
            }),

        Select::make('student_id')
            ->label('الطالب')
            ->options(function (Get $get): array {
                $circleId = $get('circle_id');

                if (! $circleId) {
                    return [];
                }

                $circle = AttendanceRecordResource::allowedCirclesQuery()
                    ->whereKey($circleId)
                    ->first();

                if (! $circle) {
                    return [];
                }

                return $circle->students()
                    ->wherePivotNull('left_at')
                    ->orderBy('full_name')
                    ->pluck('students.full_name', 'students.id')
                    ->all();
            })
            ->searchable()
            ->preload()
            ->required()
            ->disabled(fn (Get $get): bool => ! $get('circle_id')),

        DatePicker::make('attendance_date')
            ->label('تاريخ الحضور')
            ->default(now()->toDateString())
            ->required()
            ->native(false),

        Select::make('status')
            ->label('حالة الحضور')
            ->options([
                AttendanceRecord::STATUS_PRESENT => 'حاضر',
                AttendanceRecord::STATUS_ABSENT => 'غائب',
                AttendanceRecord::STATUS_LATE => 'متأخر',
                AttendanceRecord::STATUS_EXCUSED => 'غياب بعذر',
            ])
            ->default(AttendanceRecord::STATUS_PRESENT)
            ->required()
            ->native(false),

        Textarea::make('notes')
            ->label('ملاحظات')
            ->rows(3)
            ->columnSpanFull()
            ->nullable(),
    ]);
    }

//     private static function canSelectCircle(Circle $circle): bool
//     {
//         $user = request()->user();

//         if (! $user instanceof User) {
//             return false;
//         }

//         if ($user->hasRole('super_admin')) {
//             return true;
//         }

//         $teacher = Teacher::query()
//             ->where('user_id', $user->id)
//             ->first();

//         if ($teacher) {
//             return (int) $circle->teacher_id === (int) $teacher->id;
//         }

//         return app(CenterHierarchy::class)->canUserAccess(
//             $user,
//             (int) $circle->center_id
//         );
//     }
}
