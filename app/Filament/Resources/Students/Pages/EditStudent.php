<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Student;
use App\Support\StudentCenterAccess;
use Filament\Resources\Pages\EditRecord;
use GuzzleHttp\Psr7\Request;
use Illuminate\Validation\ValidationException;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Request()->user();

        abort_unless($user, 403);

        /** @var Student $student */
        $student = $this->getRecord();

        abort_unless($user->can('update', $student), 403);

        $originalCenterId = (int) $student->center_id;

        // حتى المشرف العام لا يستطيع نقل الطالب إلى مركز رئيسي
        // أو مركز غير فعال أو غير معتمد.
        $branchIds = StudentCenterAccess::branchIdsFor($user);

        if ($user->hasRole('super_admin')) {
            $requestedCenterId = $data['center_id'] ?? null;

            if (
                $requestedCenterId === null
                || ! in_array((int) $requestedCenterId, $branchIds, true)
            ) {
                throw ValidationException::withMessages([
                    'center_id' => 'يجب اختيار مركز فرعي فعال ومعتمد.',
                ]);
            }

            $data['center_id'] = (int) $requestedCenterId;

            return $data;
        }

        // مدير الفرع لا يغيّر مركز الطالب أثناء التعديل.
        abort_unless(
            $user->hasRole('center_manager')
                && in_array($originalCenterId, $branchIds, true),
            403
        );

        $data['center_id'] = $originalCenterId;

        return $data;
    }
}
