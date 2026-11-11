<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Concerns\HasDashboardCenterScope;
use App\Models\MemorizationRecord;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class MemorizationChart extends ChartWidget
{
    use HasDashboardCenterScope;

    protected static ?int $sort = 6;

    protected ?string $heading = 'إنجاز الحفظ والمراجعة';

    protected ?string $description = 'عدد سجلات التسميع خلال آخر 6 أشهر';

    protected function getData(): array
    {
        $start = CarbonImmutable::today()
            ->startOfMonth()
            ->subMonths(5);

        $end = CarbonImmutable::today()->endOfMonth();

        $records = $this->dashboardQuery(MemorizationRecord::class)
            ->whereBetween('record_date', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->selectRaw("
                DATE_TRUNC('month', record_date) as month,
                COUNT(*) as total
            ")
            ->groupByRaw("DATE_TRUNC('month', record_date)")
            ->orderByRaw("DATE_TRUNC('month', record_date)")
            ->get();

        $values = [];
        $labels = [];

        for ($i = 0; $i < 6; $i++) {
            $month = $start->addMonths($i);

            $key = $month->format('Y-m');

            $labels[] = $month->format('m/Y');

            $values[$key] = 0;
        }

        foreach ($records as $record) {
            $key = CarbonImmutable::parse($record->month)
                ->format('Y-m');

            if (isset($values[$key])) {
                $values[$key] = (int) $record->total;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'سجلات الحفظ والمراجعة',
                    'data' => array_values($values),
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }
}