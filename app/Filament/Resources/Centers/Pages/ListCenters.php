<?php

declare(strict_types=1);

namespace App\Filament\Resources\Centers\Pages;

use App\Filament\Resources\Centers\CenterResource;
use App\Models\Center;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ListCenters extends ListRecords
{
    protected static string $resource = CenterResource::class;

    protected function getHeaderActions(): array
    {
        $user = Auth::user();

        return [

            // 1. إنشاء مركز رئيسي للمدير العام.
            CreateAction::make()
                ->label('إضافة مركز رئيسي')
                ->icon('heroicon-o-building-library')
                ->visible(
                    fn (): bool =>
                        $user !== null
                        && $user->hasRole('super_admin')
                        && CenterResource::canCreate()
                ),

            // 2. إنشاء فرع للمستخدم الذي يملك صلاحية الإنشاء.
            Action::make('CreateChild:Center')
                ->label('إنشاء فرع')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(
                    fn (): string => CenterResource::getUrl('create')
                )
                ->visible(
                    fn (): bool =>
                        $user !== null
                        && ! $user->hasRole('super_admin')
                        && Gate::forUser($user)
                            ->allows('create', Center::class)
                ),

            // 3. عرض طلبات الاعتماد النهائي للمدير العام.
            Action::make('pendingFinalApprovals')
                ->label('طلبات الاعتماد النهائي')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning')
                ->badge(
                    fn (): int => Center::query()
                        ->where(
                            'approval_status',
                            'pending_super_admin_approval'
                        )
                        ->count()
                )
                ->visible(
                    fn (): bool =>
                        $user !== null
                        && $user->hasRole('super_admin')
                        && $user->can('ApproveFinal:Center')
                        && Center::query()
                            ->where(
                                'approval_status',
                                'pending_super_admin_approval'
                            )
                            ->exists()
                )
                ->url(
                    fn (): string => CenterResource::getUrl('index', [
                        'tableFilters' => [
                            'approval_status' => [
                                'value' => 'pending_super_admin_approval',
                            ],
                        ],
                    ])
                ),
        ];
    }
}
