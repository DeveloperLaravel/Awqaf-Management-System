<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuardianStudents\Pages;

use App\Filament\Resources\GuardianStudents\GuardianStudentResource;
use App\Models\Center;
use App\Models\Guardian;
use App\Models\GuardianStudent;
use App\Models\Student;
use App\Services\CenterHierarchy;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateGuardianStudent extends CreateRecord
{
    protected static string $resource = GuardianStudentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $centerId = (int) ($data['center_id'] ?? 0);
        $studentId = (int) ($data['student_id'] ?? 0);
        $guardianId = (int) ($data['guardian_id'] ?? 0);

        $user = Auth::user();

        if (! $user) {
            throw ValidationException::withMessages([
                'data.center_id' => 'يجب تسجيل الدخول أولاً.',
            ]);
        }

        // التحقق من صلاحية الوصول إلى المركز.
        $accessibleIds = app(CenterHierarchy::class)
            ->accessibleIdsForUser($user);

        if (! in_array($centerId, $accessibleIds, true)) {
            throw ValidationException::withMessages([
                'data.center_id' => 'ليس لديك صلاحية الوصول إلى هذا المركز.',
            ]);
        }

        // التحقق من أن المركز نشط ومعتمد.
        $centerExists = Center::query()
            ->whereKey($centerId)
            ->where('is_active', true)
            ->where('approval_status', 'active')
            ->exists();

        if (! $centerExists) {
            throw ValidationException::withMessages([
                'data.center_id' => 'المركز غير نشط أو غير معتمد.',
            ]);
        }

        // التحقق من الطالب.
        $studentExists = Student::query()
            ->whereKey($studentId)
            ->where('center_id', $centerId)
            ->exists();

        if (! $studentExists) {
            throw ValidationException::withMessages([
                'data.student_id' => 'الطالب لا يتبع المركز المحدد.',
            ]);
        }

        // التحقق من ولي الأمر.
        $guardianExists = Guardian::query()
            ->whereKey($guardianId)
            ->where('center_id', $centerId)
            ->where('is_active', true)
            ->exists();

        if (! $guardianExists) {
            throw ValidationException::withMessages([
                'data.guardian_id' => 'ولي الأمر غير موجود أو غير نشط في هذا المركز.',
            ]);
        }

        // منع تكرار العلاقة.
        $alreadyLinked = GuardianStudent::query()
            ->where('center_id', $centerId)
            ->where('student_id', $studentId)
            ->where('guardian_id', $guardianId)
            ->exists();

        if ($alreadyLinked) {
            throw ValidationException::withMessages([
                'data.guardian_id' => 'ولي الأمر مرتبط بهذا الطالب مسبقاً.',
            ]);
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {

            // إذا كان ولي الأمر أساسياً، ألغِ صفة الأساسي عن السابق.
            if ($data['is_primary'] ?? false) {
                GuardianStudent::query()
                    ->where('student_id', $data['student_id'])
                    ->where('center_id', $data['center_id'])
                    ->where('is_primary', true)
                    ->update([
                        'is_primary' => false,
                    ]);
            }

            return GuardianStudent::create([
                'center_id' => $data['center_id'],
                'student_id' => $data['student_id'],
                'guardian_id' => $data['guardian_id'],
                'relationship' => $data['relationship'] ?? null,
                'is_primary' => $data['is_primary'] ?? false,
                'can_receive_notifications' =>
                    $data['can_receive_notifications'] ?? true,
                'can_pickup' => $data['can_pickup'] ?? false,
            ]);
        });
    }
}
