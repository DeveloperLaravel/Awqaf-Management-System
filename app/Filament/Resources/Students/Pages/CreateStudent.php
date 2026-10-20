<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Center;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    /**
     * تجهيز بيانات الطالب قبل الحفظ.
     */

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        // التأكد من تسجيل الدخول
       if (! $user) {
            abort(403);
        }
        // مدير النظام يستطيع اختيار المركز
        if (Gate::allows('centers.view')) {
            $centerId = $data['center_id'] ?? null;

            // يجب تحديد مركز صحيح
            if (
                ! $centerId ||
                ! Center::query()->whereKey($centerId)->exists()
            ) {
                throw ValidationException::withMessages([
                    'center_id' => 'يرجى اختيار مركز تحفيظ صحيح.',
                ]);
            }

            return $data;
        }

        // المستخدم العادي يجب أن يكون مرتبطًا بمركز
        if (! $user->center_id) {
            abort(403, 'حسابك غير مرتبط بأي مركز.');
        }

        // فرض مركز المستخدم من الخادم
        // حتى لو أرسل المتصفح center_id مختلفًا
        $data['center_id'] = $user->center_id;

        return $data;
    }
}