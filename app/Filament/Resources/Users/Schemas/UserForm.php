<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Center;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('الاسم الكامل')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('البريد الإلكتروني')
                ->email()
                ->required()
                ->unique(table: 'users', column: 'email')
                ->maxLength(255),

            Select::make('center_id')
                ->label('المركز')
                ->options(fn (): array => Center::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->visible(fn (): bool =>
                    request()->user()?->hasRole('super_admin') ?? false
                )
                ->required(fn (): bool =>
                    request()->user()?->hasRole('super_admin') ?? false
                )
                ->searchable()
                ->preload()
                ->native(false),

            Select::make('role_id')
                ->label('دور المستخدم')
                ->options(function (): array {
                    $user = request()->user();

                    if (! $user || ! $user->hasPermissionTo('Create:User', 'web')) {
                        return [];
                    }

                    $roles = Role::query()
                        ->where('guard_name', 'web');

                    if (! $user->hasRole('super_admin')) {
                        $roles->whereIn('name', [
                            'center_manager',
                            'circle_supervisor',
                            'teacher',
                            'accountant',
                            'guardian',
                        ]);
                    }

                    return $roles
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all();
                })
                ->required()
                ->searchable()
                ->preload()
                ->native(false)
                ->helperText(
                    'اختر الدور الذي سيُمنح للمستخدم.'
                ),

            TextInput::make('password')
                ->label('كلمة المرور')
                ->password()
                ->revealable()
                ->required()
                ->minLength(8)
                ->maxLength(255),
        ]);
    }
}
