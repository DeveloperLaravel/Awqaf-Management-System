<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\TeacherResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateTeacher extends CreateRecord
{
    protected static string $resource = TeacherResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {

            $email = $data['email'] ?? null;
            $password = $data['account_password'] ?? null;

            // إزالة كلمة المرور من بيانات جدول المعلمين.
            unset(
                $data['account_password'],
                $data['centers']
            );

            if (empty($email) || empty($password)) {
                throw ValidationException::withMessages([
                    'email' => 'البريد الإلكتروني وكلمة المرور مطلوبان.',
                ]);
            }

            // التأكد من أن البريد غير مستخدم في حساب آخر.
            if (User::query()->where('email', $email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'هذا البريد الإلكتروني مستخدم بالفعل.',
                ]);
            }

            // إنشاء سجل المعلم.
            $teacher = static::getModel()::create($data);

            // إنشاء حساب الدخول.
            $user = User::create([
                'name' => $teacher->full_name,
                'email' => $email,
                'password' => Hash::make($password),
                'center_id' => $teacher->center_id,
            ]);

            // إسناد دور المعلم.
            $user->assignRole('teacher');

            // ربط حساب الدخول بسجل المعلم.
            $teacher->update([
                'user_id' => $user->id,
            ]);

            return $teacher;
        });
    }
}
