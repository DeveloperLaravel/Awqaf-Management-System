<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Center;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class UserForm
{
    /**
     * الأدوار التي يسمح للمستخدم العادي بإسنادها.
     *
     * الأدوار الإدارية العليا تبقى للمدير العام فقط.
     */
    private const ALLOWED_ROLES = [
        'teacher',
        'center_manager',
        'center_admin',
        'guardian',
        'attendance_officer',
        'data_entry',
        'viewer',
        'student',
    ];

    /**
     * أسماء الأدوار بالعربية.
     */
    private const ROLE_LABELS = [
        'super_admin' => 'المدير العام',
        'center_admin' => 'مدير المركز',
        'center_manager' => 'مدير مركز فرعي',
        'supervisor' => 'مشرف الحلقات',
        'teacher' => 'معلم',
        'accountant' => 'محاسب',
        'attendance_officer' => 'مسؤول الحضور',
        'data_entry' => 'مدخل بيانات',
        'guardian' => 'ولي أمر',
        'student' => 'طالب',
        'viewer' => 'مشاهد',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            /*
            |--------------------------------------------------------------------------
            | الاسم
            |--------------------------------------------------------------------------
            */

            TextInput::make('name')
                ->label('الاسم الكامل')
                ->required()
                ->maxLength(255),

            /*
            |--------------------------------------------------------------------------
            | البريد الإلكتروني
            |--------------------------------------------------------------------------
            */

            TextInput::make('email')
                ->label('البريد الإلكتروني')
                ->email()
                ->required()
                ->unique(
                    table: 'users',
                    column: 'email',
                    ignoreRecord: true,
                )
                ->maxLength(255),

            /*
            |--------------------------------------------------------------------------
            | المركز
            |--------------------------------------------------------------------------
            */

            Select::make('center_id')
                ->label('المركز')
                ->options(function (): array {
                    $user = Auth::user();

                    if (! $user instanceof User) {
                        return [];
                    }

                    if (! $user->hasRole('super_admin')) {
                        return [];
                    }

                    return Center::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all();
                })
                ->visible(
                    fn (): bool =>
                        Auth::user()?->hasRole('super_admin') ?? false
                )
                ->required(
                    fn (): bool =>
                        Auth::user()?->hasRole('super_admin') ?? false
                )
                ->searchable()
                ->preload()
                ->native(false)
                ->placeholder('اختر المركز')
                ->helperText(
                    'يستطيع المدير العام تحديد المركز المرتبط بالمستخدم.'
                ),

            /*
            |--------------------------------------------------------------------------
            | دور المستخدم
            |--------------------------------------------------------------------------
            */

            Select::make('role_id')
                ->label('دور المستخدم')
                ->options(function (
                    string $operation,
                    ?User $record,
                ): array {
                    $user = Auth::user();

                    if (! $user instanceof User) {
                        return [];
                    }

                    /*
                     * التحقق من صلاحية العملية.
                     */
                    $authorized = match ($operation) {
                        'create' => Gate::allows('create', User::class),

                        'edit' => $record instanceof User
                            && Gate::allows('update', $record),

                        default => false,
                    };

                    if (! $authorized) {
                        return [];
                    }

                    /*
                     * جلب أدوار النظام.
                     */
                    $roles = Role::query()
                        ->where('guard_name', 'web')
                        ->where('name', '!=', 'super_admin');

                    /*
                     * المستخدم العادي لا يستطيع إسناد
                     * الأدوار الإدارية العليا.
                     */
                    if (! $user->hasRole('super_admin')) {
                        $roles->whereIn(
                            'name',
                            self::ALLOWED_ROLES,
                        );
                    }

                    return $roles
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (Role $role): array => [
                            $role->getKey() =>
                                self::ROLE_LABELS[$role->name]
                                ?? $role->name,
                        ])
                        ->all();
                })

                /*
                 * إظهار الدور الحالي عند التعديل.
                 */
                ->afterStateHydrated(
                    function (
                        Select $component,
                        ?User $record,
                    ): void {
                        if (! $record instanceof User) {
                            return;
                        }

                        $role = $record->roles()
                            ->where('guard_name', 'web')
                            ->where('name', '!=', 'super_admin')
                            ->first();

                        $component->state($role?->getKey());
                    }
                )

                ->required()
                ->searchable()
                ->preload()
                ->native(false)
                ->placeholder('اختر دور المستخدم')
                ->helperText(
                    'يحدد الدور صلاحيات المستخدم داخل المنظومة.'
                ),

            /*
            |--------------------------------------------------------------------------
            | كلمة المرور
            |--------------------------------------------------------------------------
            */

            TextInput::make('password')
                ->label('كلمة المرور')
                ->password()
                ->revealable()
                ->required(
                    fn (string $operation): bool =>
                        $operation === 'create'
                )
                ->minLength(8)
                ->maxLength(255)
                ->dehydrated(
                    fn (?string $state): bool =>
                        filled($state)
                )
                ->helperText(
                    'اترك الحقل فارغاً عند التعديل للاحتفاظ بكلمة المرور الحالية.'
                ),

        ]);
    }
}
