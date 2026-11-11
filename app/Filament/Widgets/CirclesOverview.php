<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Concerns\HasDashboardCenterScope;
use App\Models\Circle;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CirclesOverview extends BaseWidget
{
    use HasDashboardCenterScope;

    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $circles = $this->dashboardQuery(Circle::class);

        $total = (clone $circles)->count();

        $active = (clone $circles)
            ->where('is_active', true)
            ->count();

        $inactive = (clone $circles)
            ->where('is_active', false)
            ->count();

        return [
            Stat::make('إجمالي الحلقات', number_format($total))
                ->description('الحلقات المسجلة')
                ->descriptionIcon('heroicon-o-book-open')
                ->color('primary'),

            Stat::make('الحلقات النشطة', number_format($active))
                ->description('الحلقات المتاحة')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('الحلقات المتوقفة', number_format($inactive))
                ->description('الحلقات غير النشطة')
                ->descriptionIcon('heroicon-o-pause-circle')
                ->color('danger'),
        ];
    }
}