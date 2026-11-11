<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Concerns\HasDashboardCenterScope;
use App\Models\AttendanceRecord;
use Filament\Widgets\ChartWidget;

class AttendanceChart extends ChartWidget
{
    use HasDashboardCenterScope;

    protected static ?int $sort = 2;

    protected ?string $heading = 'إحصائيات الحضور خلال آخر 30 يوماً';

    protected function getData(): array
    {
        $startDate = now()->subDays(29)->startOfDay();
        $endDate = now()->endOfDay();

        $records = $this->dashboardQuery(AttendanceRecord::class)
            ->whereBetween('attendance_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->selectRaw('attendance_date, status, COUNT(*) as total')
            ->groupBy('attendance_date', 'status')
            ->orderBy('attendance_date')
            ->get();

        $labels = collect(range(0, 29))
            ->map(fn (int $day) => $startDate
                ->copy()
                ->addDays($day)
                ->format('d/m'))
            ->all();

        $statuses = [
            'present' => 'حاضر',
            'absent' => 'غائب',
            'late' => 'متأخر',
            'excused' => 'مستأذن',
        ];

        $datasets = [];

        foreach ($statuses as $status => $label) {
            $datasets[] = [
                'label' => $label,
                'data' => collect(range(0, 29))
                    ->map(function (int $day) use ($startDate, $records, $status) {
                        $date = $startDate->copy()
                            ->addDays($day)
                            ->toDateString();

                        return (int) $records
                            ->where('status', $status)
                            ->firstWhere(
                                fn ($record) => $record->attendance_date
                                    ->format('Y-m-d') === $date
                            )?->total ?? 0;
                    })
                    ->all(),
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
