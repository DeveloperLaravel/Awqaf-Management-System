<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Concerns\HasDashboardCenterScope;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class PaymentsOverview extends ChartWidget
{
    use HasDashboardCenterScope;

    protected static ?int $sort = 7;

    protected ?string $heading = 'تحليل الإيرادات';

    protected ?string $description = 'إجمالي المدفوعات خلال آخر 12 شهراً بالدينار الليبي';

    protected function getData(): array
    {
        $start = CarbonImmutable::today()
            ->startOfMonth()
            ->subMonths(11);

        $end = CarbonImmutable::today()->endOfMonth();

        $records = $this->dashboardQuery(Payment::class)
            ->whereBetween('paid_at', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->selectRaw("
                DATE_TRUNC('month', paid_at) as month,
                SUM(amount) as total
            ")
            ->groupByRaw("DATE_TRUNC('month', paid_at)")
            ->orderByRaw("DATE_TRUNC('month', paid_at)")
            ->get();

        $values = [];
        $labels = [];

        for ($i = 0; $i < 12; $i++) {
            $month = $start->addMonths($i);

            $key = $month->format('Y-m');

            $labels[] = $month->format('m/Y');

            $values[$key] = 0;
        }

        foreach ($records as $record) {
            $key = CarbonImmutable::parse($record->month)
                ->format('Y-m');

            if (isset($values[$key])) {
                $values[$key] = round((float) $record->total, 2);
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'الإيرادات بالدينار الليبي',
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
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}