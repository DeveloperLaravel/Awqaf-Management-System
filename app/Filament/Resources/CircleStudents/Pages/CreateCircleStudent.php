<?php

declare(strict_types=1);

namespace App\Filament\Resources\CircleStudents\Pages;

use App\Filament\Resources\CircleStudents\CircleStudentResource;
use App\Models\Circle;
use App\Models\CircleStudent;
use App\Models\Student;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCircleStudent extends CreateRecord
{
    protected static string $resource = CircleStudentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $centerId = (int) ($data['center_id'] ?? 0);
        $circleId = (int) ($data['circle_id'] ?? 0);
        $studentIds = array_unique(
            array_map('intval', $data['student_ids'] ?? [])
        );

        if (! in_array(
            $centerId,
            CircleStudentResource::accessibleCenterIds(),
            true
        )) {
            throw ValidationException::withMessages([
                'data.center_id' => 'ليس لديك صلاحية التسجيل في هذا المركز.',
            ]);
        }

        if (! CircleStudentResource::getAvailableCenters()
            ->whereKey($centerId)
            ->exists()) {
            throw ValidationException::withMessages([
                'data.center_id' => 'المركز غير نشط أو غير معتمد.',
            ]);
        }

        $circleExists = Circle::query()
            ->whereKey($circleId)
            ->where('center_id', $centerId)
            ->where('is_active', true)
            ->exists();

        if (! $circleExists) {
            throw ValidationException::withMessages([
                'data.circle_id' => 'الحلقة غير موجودة أو غير نشطة في هذا المركز.',
            ]);
        }

        if (empty($studentIds)) {
            throw ValidationException::withMessages([
                'data.student_ids' => 'اختر طالباً واحداً على الأقل.',
            ]);
        }

        $studentsCount = Student::query()
            ->where('center_id', $centerId)
            ->whereIn('id', $studentIds)
            ->count();

        if ($studentsCount !== count($studentIds)) {
            throw ValidationException::withMessages([
                'data.student_ids' => 'يوجد طالب لا يتبع المركز المحدد.',
            ]);
        }

        $existingStudentIds = CircleStudent::query()
            ->whereIn('student_id', $studentIds)
            ->whereNull('left_at')
            ->pluck('student_id')
            ->all();

        if (! empty($existingStudentIds)) {
            throw ValidationException::withMessages([
                'data.student_ids' => 'بعض الطلاب لديهم تسجيلات نشطة بالفعل. أزلهم من الاختيار وحاول مجدداً.',
            ]);
        }

        $joinedAt = $data['joined_at'] ?? now()->toDateString();

        $data['student_ids'] = $studentIds;
        $data['joined_at'] = $joinedAt;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $studentIds = $data['student_ids'];

            unset($data['student_ids']);

            $firstRecord = null;

            foreach ($studentIds as $studentId) {
                $record = CircleStudent::create([
                    'center_id' => $data['center_id'],
                    'circle_id' => $data['circle_id'],
                    'student_id' => $studentId,
                    'joined_at' => $data['joined_at'],
                ]);

                $firstRecord ??= $record;
            }

            return $firstRecord;
        });
    }
}
