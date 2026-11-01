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
            // إنشاء مركز رئيسي للمدير العام.
            CreateAction::make()
                ->label('إضافة مركز')
                ->icon('heroicon-o-building-library')
                ->visible(
                    fn (): bool =>
                        $user !== null
                        && $user->hasRole('super_admin')
                        && CenterResource::canCreate()
                ),

            // إنشاء فرع لمدير المركز.
            Action::make('createChild')
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
                        &&  Gate::forUser(Auth::user())
            ->allows('create', Center::class)
                ),
        ];
    }
}
