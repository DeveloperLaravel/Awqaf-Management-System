<?php

declare(strict_types=1);

namespace App\Filament\Resources\Centers\Tables;

use App\Models\Center;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
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
                    ->label('المركز والفروع')
                    ->searchable()
                    ->sortable()
                    ->description(function (Center $record): string {
                        $level = 0;
                        $parent = $record->parentCenter;
                        $visited = [$record->getKey()];

                        while ($parent !== null) {
                            if (in_array($parent->getKey(), $visited, true)) {
                                break;
                            }

                            $visited[] = $parent->getKey();
                            $level++;
                            $parent = $parent->parentCenter;
                        }

                        $prefix = $level === 0
                            ? 'مركز رئيسي'
                            : '└─ فرع من: ' . ($record->parentCenter?->name ?? 'غير معروف');

                        return $prefix . ' | ' . ($record->code ?: 'بدون رمز');
                    }),

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
                        'pending_parent_approval' => 'بانتظار موافقة المركز الأب',
                        'pending_super_admin_approval' => 'بانتظار الاعتماد النهائي',
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
     *
     * تنقل المركز إلى انتظار الاعتماد النهائي فقط،
     * ولا تقوم بتفعيله.
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
                'ستُنقل حالة المركز إلى مرحلة الاعتماد النهائي، ولن يتم تفعيله بعد.'
            )
            ->visible(function (Center $record): bool {
                $user = auth()->user();

                return $user instanceof User
                    && Gate::forUser($user)->allows('approveChild', $record);
            })
            ->action(function (Center $record): void {
                $user = auth()->user();

                abort_unless($user instanceof User, 403);

                DB::transaction(function () use ($record, $user): void {
                    $center = Center::query()
                        ->whereKey($record->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    // إعادة التحقق من الصلاحية بعد قفل السجل.
                    Gate::forUser($user)->authorize('approveChild', $center);

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
                            'parent_center_id' => 'المركز الأب غير معتمد أو غير مفعّل.',
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

                Notification::make()
                    ->title('تمت موافقة المركز الأب')
                    ->body('أصبح الطلب بانتظار الاعتماد النهائي من المدير العام.')
                    ->success()
                    ->send();
            });
    }

    /**
     * رفض طلب المركز من مدير المركز الأب.
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
                $user = auth()->user();

                return $user instanceof User
                    && Gate::forUser($user)->allows('approveChild', $record);
            })
            ->action(function (Center $record, array $data): void {
                $user = auth()->user();

                abort_unless($user instanceof User, 403);

                DB::transaction(function () use ($record, $data, $user): void {
                    $center = Center::query()
                        ->whereKey($record->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    // إعادة التحقق من الصلاحية وحالة الطلب.
                    Gate::forUser($user)->authorize('approveChild', $center);

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

                Notification::make()
                    ->title('تم رفض طلب المركز')
                    ->body('تم حفظ سبب الرفض.')
                    ->danger()
                    ->send();
            });
    }

    /**
     * الاعتماد النهائي من المدير العام.
     *
     * لا يظهر الزر إلا بعد موافقة المركز الأب.
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
                'سيتم اعتماد المركز وتفعيله بعد موافقة المركز الأب.'
            )
            ->visible(function (Center $record): bool {
                $user = auth()->user();

                return $user instanceof User
                    && Gate::forUser($user)->allows('approveFinal', $record);
            })
            ->action(function (Center $record): void {
                $user = auth()->user();

                abort_unless($user instanceof User, 403);

                DB::transaction(function () use ($record, $user): void {
                    $center = Center::query()
                        ->whereKey($record->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    // التحقق من سياسة الاعتماد النهائي.
                    Gate::forUser($user)->authorize('approveFinal', $center);

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
                            'parent_center_id' => 'المركز الأب غير معتمد أو غير مفعّل.',
                        ]);
                    }

                    if ($center->parent_approved_at === null) {
                        throw ValidationException::withMessages([
                            'approval_status' => 'يجب موافقة المركز الأب أولاً.',
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

                Notification::make()
                    ->title('تم اعتماد المركز بنجاح')
                    ->body('أصبح المركز معتمداً ومفعّلاً في النظام.')
                    ->success()
                    ->send();
            });
    }
}
