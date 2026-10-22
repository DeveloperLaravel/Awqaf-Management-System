<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Support\StudentCenterAccess;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = request()->user();

        abort_unless($user, 403);

        abort_unless($user->can('create', \App\Models\Student::class), 403);

        $branchIds = StudentCenterAccess::branchIdsFor($user);

        $centerId = $data['center_id'] ?? null;

        if (
            $centerId === null
            || ! in_array((int) $centerId, $branchIds, true)
        ) {
            throw ValidationException::withMessages([
                'center_id' => 'لا يمكنك إضافة طالب إلى هذا المركز الفرعي.',
            ]);
        }

        // نحفظ المركز الذي اختاره المستخدم من قائمة فروعه المصرح بها.
        $data['center_id'] = (int) $centerId;

        return $data;
    }
}
