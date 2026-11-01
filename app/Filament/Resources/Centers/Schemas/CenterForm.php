<?php

declare(strict_types=1);

namespace App\Filament\Resources\Centers\Schemas;

use App\Models\Center;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class CenterForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = request()->user();

        $isSuperAdmin = $user?->hasRole('super_admin') ?? false;

        return $schema->components([

            /*
             * القسم الأول: بيانات المركز
             */
            Section::make('بيانات المركز')
                ->description('البيانات الأساسية والتبعية الإدارية للمركز.')
                ->icon('heroicon-o-building-office-2')
                ->columns(2)
                ->schema([

                    /*
                     * نوع المركز:
                     * يظهر للمدير العام فقط.
                     */
                    Select::make('center_creation_type')
                        ->label('نوع المركز')
                        ->options([
                            'root' => 'مركز رئيسي',
                            'child' => 'مركز فرعي',
                        ])
                        ->default('root')
                        ->live()
                        ->visible(fn (): bool => $isSuperAdmin)
                        ->required(fn (): bool => $isSuperAdmin)
                        ->dehydrated(fn (): bool => $isSuperAdmin),

                    /*
                     * المركز الأب.
                     */
                    Select::make('parent_center_id')
                        ->label('المركز الأب')
                        ->options(function () use ($isSuperAdmin): array {

                            $user = request()->user();

                            if (! $user) {
                                return [];
                            }

                            // المدير العام: المراكز الرئيسية المعتمدة فقط.
                            if ($isSuperAdmin) {
                                return Center::query()
                                    ->whereNull('parent_center_id')
                                    ->where('is_active', true)
                                    ->where('approval_status', 'active')
                                    ->whereNotNull('approved_at')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            }

                            // بقية المستخدمين: المراكز التي يديرونها مباشرة.
                            return app(CenterHierarchy::class)
                                ->directlyManagedCenters($user)
                                ->filter(fn (Center $center): bool =>
                                    (bool) $center->is_active
                                    && $center->approval_status === 'active'
                                    && $center->approved_at !== null
                                )
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->live()
                        ->visible(fn (Get $get): bool =>
                            ! $isSuperAdmin
                            || $get('center_creation_type') === 'child'
                        )
                        ->required(fn (Get $get): bool =>
                            ! $isSuperAdmin
                            || $get('center_creation_type') === 'child'
                        )
                        ->dehydrated(fn (Get $get): bool =>
                            ! $isSuperAdmin
                            || $get('center_creation_type') === 'child'
                        )
                        ->helperText(
                            'اختر المركز الأب المعتمد عند إنشاء مركز فرعي.'
                        ),

                    /*
                     * اسم المركز.
                     */
                    TextInput::make('name')
                        ->label('اسم المركز')
                        ->required()
                        ->maxLength(255)
                        ->autofocus(),

                    /*
                     * رمز المركز.
                     */
                    TextInput::make('code')
                        ->label('رمز المركز')
                        ->maxLength(100)
                        ->unique(
                            table: 'centers',
                            column: 'code',
                            ignoreRecord: true,
                        )
                        ->helperText(
                            'يمكن تركه فارغًا إذا كانت آلية إنشاء الرمز التلقائي مفعّلة.'
                        ),

                    /*
                     * رقم الهاتف.
                     */
                    TextInput::make('phone')
                        ->label('رقم الهاتف')
                        ->tel()
                        ->maxLength(30),

                    /*
                     * البريد الإلكتروني.
                     */
                    TextInput::make('email')
                        ->label('البريد الإلكتروني للمركز')
                        ->email()
                        ->maxLength(255),

                    /*
                     * المنطقة:
                     * المدير العام يحددها للمركز الرئيسي فقط.
                     * المركز الفرعي يرث منطقة المركز الأب.
                     */
                    Select::make('region_id')
                        ->label('المنطقة')
                        ->relationship(
                            name: 'region',
                            titleAttribute: 'name',
                        )
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get): bool =>
                            $isSuperAdmin
                            && $get('center_creation_type') !== 'child'
                        )
                        ->required(fn (Get $get): bool =>
                            $isSuperAdmin
                            && $get('center_creation_type') !== 'child'
                        )
                        ->dehydrated(fn (Get $get): bool =>
                            $isSuperAdmin
                            && $get('center_creation_type') !== 'child'
                        ),

                    /*
                     * العنوان.
                     */
                    Textarea::make('address')
                        ->label('العنوان')
                        ->rows(3)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                ]),

            /*
             * القسم الثاني: تعيين مدير المركز.
             */
            Section::make('مدير المركز')
                ->description(
                    'إنشاء حساب مدير جديد أو اختيار حساب مدير موجود.'
                )
                ->icon('heroicon-o-user-circle')
                ->columns(2)
                ->visible(fn (string $operation): bool =>
                    $operation === 'create'
                    && $isSuperAdmin
                )
                ->schema([

                    /*
                     * طريقة تعيين المدير.
                     */
                    Select::make('manager_mode')
                        ->label('طريقة تعيين المدير')
                        ->options([
                            'new' => 'إنشاء حساب مدير جديد',
                            'existing' => 'اختيار حساب موجود',
                        ])
                        ->default('new')
                        ->required()
                        ->live()
                        ->dehydrated(),

                    /*
                     * اختيار مدير موجود.
                     */
                    Select::make('manager_id')
                        ->label('حساب المدير الموجود')
                        ->options(function (): array {

                            return User::query()
                                ->whereHas(
                                    'roles',
                                    function ($query): void {
                                        $query
                                            ->whereIn('name', [
                                                'center_manager',
                                                'center_admin',
                                            ])
                                            ->where('guard_name', 'web');
                                    }
                                )
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get): bool =>
                            $get('manager_mode') === 'existing'
                        )
                        ->required(fn (Get $get): bool =>
                            $get('manager_mode') === 'existing'
                        )
                        ->dehydrated(fn (Get $get): bool =>
                            $get('manager_mode') === 'existing'
                        ),

                    /*
                     * اسم المدير الجديد.
                     */
                    TextInput::make('manager_name')
                        ->label('اسم المدير')
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool =>
                            $get('manager_mode') === 'new'
                        )
                        ->required(fn (Get $get): bool =>
                            $get('manager_mode') === 'new'
                        )
                        ->dehydrated(fn (Get $get): bool =>
                            $get('manager_mode') === 'new'
                        ),

                    /*
                     * بريد المدير الجديد.
                     */
                    TextInput::make('manager_email')
                        ->label('البريد الإلكتروني للمدير')
                        ->email()
                        ->maxLength(255)
                        ->unique(
                            table: 'users',
                            column: 'email',
                        )
                        ->visible(fn (Get $get): bool =>
                            $get('manager_mode') === 'new'
                        )
                        ->required(fn (Get $get): bool =>
                            $get('manager_mode') === 'new'
                        )
                        ->dehydrated(fn (Get $get): bool =>
                            $get('manager_mode') === 'new'
                        ),

                    /*
                     * كلمة المرور.
                     */
                    TextInput::make('manager_password')
                        ->label('كلمة المرور')
                        ->password()
                        ->revealable()
                        ->rule(Password::min(8))
                        ->visible(fn (Get $get): bool =>
                            $get('manager_mode') === 'new'
                        )
                        ->required(fn (Get $get): bool =>
                            $get('manager_mode') === 'new'
                        )
                        ->dehydrated(fn (Get $get): bool =>
                            $get('manager_mode') === 'new'
                        ),

                    /*
                     * تأكيد كلمة المرور.
                     */
                    TextInput::make('manager_password_confirmation')
                        ->label('تأكيد كلمة المرور')
                        ->password()
                        ->revealable()
                        ->same('manager_password')
                        ->visible(fn (Get $get): bool =>
                            $get('manager_mode') === 'new'
                        )
                        ->required(fn (Get $get): bool =>
                            $get('manager_mode') === 'new'
                        )
                        ->dehydrated(false),
                ]),

            /*
             * القسم الثالث: توضيح دورة الاعتماد.
             */
            Section::make('حالة المركز والاعتماد')
                ->description(
                    'تتحدد حالة المركز من خلال دورة الاعتماد والصلاحيات.'
                )
                ->icon('heroicon-o-shield-check')
                ->schema([

                    Placeholder::make('approval_workflow_info')
                        ->label('آلية الاعتماد')
                        ->content(
                            $isSuperAdmin
                                ? 'المركز الرئيسي ينشأ بواسطة المدير العام. أما المركز الفرعي فيحتاج إلى اختيار مركز أب معتمد، ثم يخضع لدورة الاعتماد.'
                                : 'المركز الفرعي ينتظر موافقة مدير المركز الأب، ثم الاعتماد النهائي من المدير العام.'
                        ),
                ]),
        ]);
    }
}
