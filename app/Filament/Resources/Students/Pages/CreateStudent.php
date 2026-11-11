<?php

declare(strict_types=1);

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Student;
use App\Models\User;
use App\Support\StudentCenterAccess;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        abort_unless($user, 403);

        abort_unless(
            $user->can('create', Student::class),
            403
        );

        $branchIds = StudentCenterAccess::branchIdsFor($user);

        $centerId = $data['center_id'] ?? null;

        if (
            $centerId === null ||
            ! in_array((int) $centerId, $branchIds, true)
        ) {
            throw ValidationException::withMessages([
                'center_id' => 'لا يمكنك إضافة طالب إلى هذا المركز الفرعي.',
            ]);
        }

        $data['center_id'] = (int) $centerId;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {

            // استخراج بيانات الحساب قبل إنشاء الطالب
            $accountEmail = $data['account_email'];
            $accountPassword = $data['account_password'];

            unset(
                $data['account_email'],
                $data['account_password'],
                $data['account_password_confirmation']
            );

            // إنشاء الطالب
            $student = parent::handleRecordCreation($data);

            // إنشاء حساب المستخدم
            $account = User::create([
                'name' => $student->full_name,
                'email' => $accountEmail,
                'password' => $accountPassword,
                'center_id' => $student->center_id,
            ]);

            // إسناد دور الطالب
            $account->assignRole('student');

            // ربط حساب المستخدم بسجل الطالب
            $student->user()->associate($account);
            $student->save();

            return $student;
        });
    }
}
