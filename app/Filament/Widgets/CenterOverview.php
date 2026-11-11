<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Concerns\HasDashboardCenterScope;
use App\Models\AttendanceRecord;
use App\Models\Center;
use App\Models\Circle;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CenterOverview extends BaseWidget
{
    use HasDashboardCenterScope;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = CarbonImmutable::today();

        $centers = Center::query()
            ->whereIn('id', $this->dashboardCenterIds());

        $students = $this->dashboardQuery(Student::class);
        $teachers = $this->dashboardQuery(Teacher::class);
        $circles = $this->dashboardQuery(Circle::class);
        $attendance = $this->dashboardQuery(AttendanceRecord::class);
        $payments = $this->dashboardQuery(Payment::class);

        $centerCount = (clone $centers)->count();

        $studentCount = (clone $students)
            ->where('is_active', true)->count();

        $teacherCount = (clone $teachers)
            ->where('is_active', true)->count();

        $circleCount = (clone $circles)
            ->where('is_active', true)->count();

        $todayAttendance = (clone $attendance)
            ->whereDate('attendance_date', $today)
            ->count();

        $monthlyPayments = (clone $payments)
            ->whereBetween('paid_at', [
                $today->startOfMonth()->toDateString(),
                $today->endOfMonth()->toDateString(),
            ])
            ->sum('amount');

        return [
            Stat::make('المراكز التابعة', number_format($centerCount))
                ->description('المراكز ضمن نطاق صلاحياتك')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('primary'),

            Stat::make('الطلاب النشطون', number_format($studentCount))
                ->description('إجمالي الطلاب النشطين')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('success'),

            Stat::make('المعلمون النشطون', number_format($teacherCount))
                ->description('المعلمون المسجلون')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('info'),

            Stat::make('الحلقات النشطة', number_format($circleCount))
                ->description('الحلقات التعليمية')
                ->descriptionIcon('heroicon-o-book-open')
                ->color('warning'),

            Stat::make('حضور اليوم', number_format($todayAttendance))
                ->description('سجلات الحضور اليوم')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('success'),

            Stat::make(
                'تحصيل الشهر',
                number_format((float) $monthlyPayments, 2) . ' د.ل'
            )
                ->description('إجمالي المدفوعات المسجلة')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('primary'),
        ];
    }
}