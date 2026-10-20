<?php

namespace App\Filament\Widgets;

use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AttendanceChart extends ChartWidget
{
    protected ?string $heading = 'إحصائيات الحضور';

    protected ?string $description =
        'متابعة سجلات الحضور والغياب خلال آخر 7 أيام';

    protected static ?int $sort = 2;

    protected function getType(): string
    {
        return 'line';
    }

    public static function canView(): bool
    {
        return Gate::allows('attendance.view');
    }

    protected function getData(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $startDate = CarbonImmutable::today()->subDays(6);
        $endDate = CarbonImmutable::today();

        $query = DB::table('attendance_records')
            ->whereBetween('attendance_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ]);

        // مدير النطاق العام يرى جميع المراكز،
        // بشرط امتلاكه أيضًا صلاحية عرض الحضور.
        if (! Gate::allows('centers.view_all')) {
            if (! $user->center_id) {
                return [
                    'datasets' => [],
                    'labels' => [],
                ];
            }

            $query->where('center_id', $user->center_id);
        }

        $records = $query
            ->selectRaw(
                'attendance_date, status, COUNT(*) as total'
            )
            ->groupBy('attendance_date', 'status')
            ->orderBy('attendance_date')
            ->get();

        $dates = [];
        $labels = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->addDays($i);

            $key = $date->toDateString();

            $dates[] = $key;
            $labels[] = $date->format('d/m');
        }

        $statuses = [
            'present' => 'حضور',
            'absent' => 'غياب',
            'late' => 'تأخير',
            'excused' => 'غياب بعذر',
        ];

        $totals = [];

        foreach ($statuses as $status => $label) {
            $totals[$status] = array_fill(0, 7, 0);
        }

        foreach ($records as $record) {
            $dateIndex = array_search(
                $record->attendance_date,
                $dates,
                true
            );

            if (
                $dateIndex !== false
                && array_key_exists($record->status, $totals)
            ) {
                $totals[$record->status][$dateIndex] =
                    (int) $record->total;
            }
        }

        $datasets = [];

        foreach ($statuses as $status => $label) {
            $datasets[] = [
                'label' => $label,
                'data' => $totals[$status],
                'tension' => 0.35,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }
}
