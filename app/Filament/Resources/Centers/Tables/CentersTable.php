<?php

declare(strict_types=1);

namespace App\Filament\Resources\Centers\Tables;

use App\Models\Center;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CentersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم المركز')
                    ->searchable()
                    ->sortable()
                    ->description(
                        fn (Center $record): string =>
                            $record->code ?: 'بدون رمز'
                    ),

                TextColumn::make('region.name')
                    ->label('المنطقة')
                    ->placeholder('غير محددة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('parentCenter.name')
                    ->label('المركز الأب')
                    ->placeholder('مركز رئيسي')
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('approval_status')
    ->label('حالة الاعتماد')
    ->badge()
    ->formatStateUsing(
        fn (?string $state): string => match ($state) {
            'pending_parent_approval' => 'بانتظار موافقة المركز الأب',
            'pending_super_admin_approval' => 'بانتظار الاعتماد النهائي',
            'active' => 'معتمد',
            'rejected' => 'مرفوض',
            default => 'غير محدد',
        }
    )
    ->color(
        fn (?string $state): string => match ($state) {
            'active' => 'success',
            'pending_parent_approval',
            'pending_super_admin_approval' => 'warning',
            'rejected' => 'danger',
            default => 'gray',
        }
    )
    ->sortable(),

                IconColumn::make('is_active')
                    ->label('مفعّل')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('approval_status')
                    ->label('حالة الاعتماد')
                    ->options([
                        'pending_parent_approval' =>
                            'بانتظار موافقة المركز الأب',

                        'pending_super_admin_approval' =>
                            'بانتظار الاعتماد النهائي',

                        'active' => 'معتمد',

                        'rejected' => 'مرفوض',
                    ]),

                SelectFilter::make('is_active')
                    ->label('حالة التفعيل')
                    ->options([
                        '1' => 'مفعّل',
                        '0' => 'غير مفعّل',
                    ]),
            ])
            ->recordActions([
                self::parentApprovalAction(),
                self::parentRejectionAction(),
                self::finalApprovalAction(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * موافقة مدير المركز الأب.
     */
    protected static function parentApprovalAction(): Action
    {
        return Action::make('approveByParent')
            ->label('موافقة المركز الأب')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('الموافقة على المركز الفرعي')
            ->modalDescription(
                'ستُنقل حالة المركز إلى مرحلة الاعتماد النهائي، ولن يُفعّل بعد.'
            )
            ->visible(function (Center $record): bool {
                $user = request()->user();

                return $user instanceof User
                    && $record->approval_status === 'pending_parent_approval'
                    && $record->parent_center_id !== null
                    && Gate::forUser($user)->allows('centers.approve_child')
                    && self::managesParent($user, $record);
            })
            ->action(function (Center $record): void {
                $user = request()->user();

                abort_unless($user instanceof User, 403);

                abort_unless(
                    Gate::forUser($user)->allows('centers.approve_child'),
                    403,
                );

                DB::transaction(function () use ($record, $user): void {
                    $center = Center::query()
                        ->whereKey($record->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        $center->approval_status !== 'pending_parent_approval'
                        || $center->parent_center_id === null
                    ) {
                        throw ValidationException::withMessages([
                            'approval_status' =>
                                'لم تعد حالة المركز تسمح بهذه الموافقة.',
                        ]);
                    }

                    abort_unless(
                        self::managesParent($user, $center),
                        403,
                        'لا يمكنك اعتماد مركز لا تدير مركزه الأب مباشرة.',
                    );

                    $parent = Center::query()
                        ->whereKey($center->parent_center_id)
                        ->lockForUpdate()
                        ->first();

                    if (
                        ! $parent
                        || ! $parent->is_active
                        || $parent->approval_status !== 'active'
                        || $parent->approved_at === null
                    ) {
                        throw ValidationException::withMessages([
                            'parent_center_id' =>
                                'المركز الأب غير معتمد أو غير مفعّل.',
                        ]);
                    }

                    $center->forceFill([
                        'approval_status' => 'pending_super_admin_approval',
                        'parent_approved_by' => $user->id,
                        'parent_approved_at' => now(),
                        'is_active' => false,
                        'approved_by' => null,
                        'approved_at' => null,
                        'rejection_reason' => null,
                    ])->save();
                });
            });
    }

    /**
     * رفض المركز من مدير المركز الأب.
     */
    protected static function parentRejectionAction(): Action
    {
        return Action::make('rejectByParent')
            ->label('رفض المركز')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('رفض طلب المركز')
            ->schema([
                Textarea::make('rejection_reason')
                    ->label('سبب الرفض')
                    ->required()
                    ->minLength(5)
                    ->maxLength(2000),
            ])
            ->visible(function (Center $record): bool {
                $user = request()->user();

                return $user instanceof User
                    && $record->approval_status === 'pending_parent_approval'
                    && $record->parent_center_id !== null
                    && Gate::forUser($user)->allows('centers.approve_child')
                    && self::managesParent($user, $record);
            })
            ->action(function (
                Center $record,
                array $data,
            ): void {
                $user = request()->user();

                abort_unless($user instanceof User, 403);

                abort_unless(
                    Gate::forUser($user)->allows('centers.approve_child'),
                    403,
                );

                DB::transaction(function () use (
                    $record,
                    $data,
                    $user,
                ): void {
                    $center = Center::query()
                        ->whereKey($record->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        $center->approval_status !== 'pending_parent_approval'
                        || $center->parent_center_id === null
                    ) {
                        throw ValidationException::withMessages([
                            'approval_status' =>
                                'لم تعد حالة المركز تسمح بالرفض من هذه المرحلة.',
                        ]);
                    }

                    abort_unless(
                        self::managesParent($user, $center),
                        403,
                    );

                    $center->forceFill([
                        'approval_status' => 'rejected',
                        'is_active' => false,
                        'parent_approved_by' => null,
                        'parent_approved_at' => null,
                        'approved_by' => null,
                        'approved_at' => null,
                        'rejection_reason' => $data['rejection_reason'],
                    ])->save();
                });
            });
    }

    /**
     * الاعتماد النهائي بواسطة المدير العام.
     */
    protected static function finalApprovalAction(): Action
    {
        return Action::make('approveFinal')
            ->label('الاعتماد النهائي')
            ->icon('heroicon-o-shield-check')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('الاعتماد النهائي للمركز')
            ->modalDescription(
                'سيتم اعتماد المركز وتفعيله في المنظومة.'
            )
            ->visible(function (Center $record): bool {
                $user = request()->user();

                return $user instanceof User
                    && $user->hasRole('super_admin')
                    && $record->approval_status === 'pending_super_admin_approval'
                    && $record->parent_approved_at !== null
                    && Gate::forUser($user)->allows('centers.approve_final');
            })
            ->action(function (Center $record): void {
                $user = request()->user();

                abort_unless($user instanceof User, 403);

                abort_unless(
                    $user->hasRole('super_admin')
                    && Gate::forUser($user)->allows('centers.approve_final'),
                    403,
                );

                DB::transaction(function () use ($record, $user): void {
                    $center = Center::query()
                        ->whereKey($record->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (
                        $center->approval_status !== 'pending_super_admin_approval'
                        || $center->parent_approved_at === null
                    ) {
                        throw ValidationException::withMessages([
                            'approval_status' =>
                                'يجب موافقة المركز الأب أولًا قبل الاعتماد النهائي.',
                        ]);
                    }

                    $parent = Center::query()
                        ->whereKey($center->parent_center_id)
                        ->lockForUpdate()
                        ->first();

                    if (
                        ! $parent
                        || ! $parent->is_active
                        || $parent->approval_status !== 'active'
                        || $parent->approved_at === null
                    ) {
                        throw ValidationException::withMessages([
                            'parent_center_id' =>
                                'لا يمكن اعتماد المركز لأن المركز الأب غير معتمد أو غير مفعّل.',
                        ]);
                    }

                    $center->forceFill([
                        'approval_status' => 'active',
                        'is_active' => true,
                        'approved_by' => $user->id,
                        'approved_at' => now(),
                        'rejection_reason' => null,
                    ])->save();
                });
            });
    }

    /**
     * يتحقق من أن المستخدم يدير المركز الأب مباشرة.
     *
     * المركز الأب فقط هو الذي يمنح حق الموافقة في هذه المرحلة.
     */
    protected static function managesParent(
        User $user,
        Center $center,
    ): bool {
        if ($center->parent_center_id === null) {
            return false;
        }

        if (
            $user->center_id !== null
            && (int) $user->center_id === (int) $center->parent_center_id
        ) {
            return true;
        }

        return $user->managedCenters()
            ->where('centers.id', $center->parent_center_id)
            ->exists();
    }
}
