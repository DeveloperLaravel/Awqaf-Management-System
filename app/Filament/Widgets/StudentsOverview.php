<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Concerns\HasDashboardCenterScope;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentsOverview extends BaseWidget
{
    use HasDashboardCenterScope;

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $students = $this->dashboardQuery(Student::class);

        $total = (clone $students)->count();

        $active = (clone $students)
            ->where('is_active', true)
            ->count();

        $inactive = (clone $students)
            ->where('is_active', false)
            ->count();

        $newThisMonth = (clone $students)
            ->whereBetween('created_at', [
                CarbonImmutable::now()->startOfMonth(),
                CarbonImmutable::now()->endOfMonth(),
            ])
            ->count();

        return [
            Stat::make('إجمالي الطلاب', number_format($total))
                ->description('جميع الطلاب المسجلين')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),

            Stat::make('الطلاب النشطون', number_format($active))
                ->description('الطلاب المستمرون في الدراسة')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('الطلاب غير النشطين', number_format($inactive))
                ->description('الطلاب غير النشطين')
                ->descriptionIcon('heroicon-o-user-minus')
                ->color('danger'),

            Stat::make('تسجيلات هذا الشهر', number_format($newThisMonth))
                ->description('الطلاب المضافون خلال الشهر')
                ->descriptionIcon('heroicon-o-user-plus')
                ->color('info'),
        ];
    }
}