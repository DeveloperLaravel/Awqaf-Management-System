<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\TeacherResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EditTeacher extends EditRecord
{
    protected static string $resource = TeacherResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['account_password'] = null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {

            $email = $data['email'] ?? null;
            $password = $data['account_password'] ?? null;

            unset(
                $data['account_password'],
                $data['centers']
            );

            if (blank($email)) {
                throw ValidationException::withMessages([
                    'email' => 'البريد الإلكتروني مطلوب.',
                ]);
            }

            // جلب حساب الدخول المرتبط بالمعلم.
            $user = User::query()
                ->whereKey($record->user_id)
                ->lockForUpdate()
                ->first();

            if (! $user) {
                throw ValidationException::withMessages([
                    'email' => 'لا يوجد حساب دخول مرتبط بهذا المعلم.',
                ]);
            }

            // التأكد من عدم استخدام البريد في حساب آخر.
            $emailExists = User::query()
                ->where('email', $email)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($emailExists) {
                throw ValidationException::withMessages([
                    'email' => 'هذا البريد الإلكتروني مستخدم لدى حساب آخر.',
                ]);
            }

            // تحديث بيانات المعلم.
            $record->update($data);

            // تحديث بيانات حساب الدخول.
            $userData = [
                'name' => $record->full_name,
                'email' => $email,
            ];

            if (filled($password)) {
                $userData['password'] = Hash::make($password);
            }

            $user->update($userData);

            // تثبيت دور المعلم وعدم السماح بتغييره.
            $user->syncRoles(['teacher']);

            return $record->refresh();
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
