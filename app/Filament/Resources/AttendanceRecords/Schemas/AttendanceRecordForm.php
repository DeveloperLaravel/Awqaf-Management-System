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
use Filament\Forms\Get;
use Filament\Schemas\Components\Utilities\Get as UtilitiesGet;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AttendanceRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('circle_id')
                ->label('الحلقة')
                ->options(function (): array {
                    $user = request()->user();

                    if (! $user instanceof User) {
                        return [];
                    }

                    $query = Circle::query()
                        ->where('is_active', true);

                    if (! $user->hasRole('super_admin')) {
                        $teacher = Teacher::query()
                            ->where('user_id', $user->id)
                            ->first();

                        if ($teacher) {
                            $query->where('teacher_id', $teacher->id);
                        } else {
                            $centerIds = app(CenterHierarchy::class)
                                ->accessibleIdsForUser($user);

                            $query->whereIn('center_id', $centerIds);
                        }
                    }

                    return $query
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all();
                })
                ->searchable()
                ->live()
                ->required(),

            Select::make('student_id')
                ->label('الطالب')
                ->options(function (UtilitiesGet $get): array {
                    $circleId = $get('circle_id');

                    if (! $circleId) {
                        return [];
                    }

                    $circle = Circle::query()->find($circleId);

                    if (! $circle || ! self::canSelectCircle($circle)) {
                        return [];
                    }

                    return $circle->students()
                        ->wherePivotNull('left_at')
                        ->orderBy('full_name')
                        ->pluck('students.full_name', 'students.id')
                        ->all();
                })
                ->searchable()
                ->required()
                ->disabled(fn (UtilitiesGet $get): bool => ! $get('circle_id')),

            DatePicker::make('attendance_date')
                ->label('تاريخ الحضور')
                ->default(now()->toDateString())
                ->required(),

            Select::make('status')
                ->label('حالة الطالب')
                ->options([
                    AttendanceRecord::STATUS_PRESENT => 'حاضر',
                    AttendanceRecord::STATUS_ABSENT => 'غائب',
                    AttendanceRecord::STATUS_LATE => 'متأخر',
                    AttendanceRecord::STATUS_EXCUSED => 'غائب بعذر',
                ])
                ->default(AttendanceRecord::STATUS_PRESENT)
                ->required(),

            Textarea::make('notes')
                ->label('ملاحظات')
                ->rows(3)
                ->columnSpanFull()
                ->nullable(),
        ]);
    }

    private static function canSelectCircle(Circle $circle): bool
    {
        $user = request()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        $teacher = Teacher::query()
            ->where('user_id', $user->id)
            ->first();

        if ($teacher) {
            return (int) $circle->teacher_id === (int) $teacher->id;
        }

        return app(CenterHierarchy::class)->canUserAccess(
            $user,
            (int) $circle->center_id
        );
    }
}
