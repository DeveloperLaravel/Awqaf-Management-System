<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Concerns\HasDashboardCenterScope;
use App\Models\Teacher;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TeachersOverview extends BaseWidget
{
    use HasDashboardCenterScope;

    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $teachers = $this->dashboardQuery(Teacher::class);

        $total = (clone $teachers)->count();

        $active = (clone $teachers)
            ->where('is_active', true)
            ->count();

        $inactive = (clone $teachers)
            ->where('is_active', false)
            ->count();

        return [
            Stat::make('إجمالي المعلمين', number_format($total))
                ->description('المعلمون المسجلون')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),

            Stat::make('المعلمون النشطون', number_format($active))
                ->description('المعلمون على رأس العمل')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('المعلمون غير النشطين', number_format($inactive))
                ->description('المعلمون غير النشطين')
                ->descriptionIcon('heroicon-o-user-minus')
                ->color('danger'),
        ];
    }
}