<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceRecord;
use App\Models\Center;
use App\Models\Circle;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Support\CenterQueryScope;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CenterOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        $students = $this->scope(Student::query());
        $teachers = $this->scope(Teacher::query());
        $circles = $this->scope(Circle::query());

        $attendance = $this->scope(
            AttendanceRecord::query()
        );

        $payments = $this->scope(Payment::query());

        $today = CarbonImmutable::today();

        $monthStart = $today->startOfMonth();
        $monthEnd = $today->endOfMonth();

        // عدد المراكز: مدير النظام يرى الكل،
        // ومدير المركز يرى مركزه فقط.
        $centersQuery = Center::query();

        if (! Gate::allows('centers.view_all')) {
            if (! $user->center_id) {
                $centersQuery->whereRaw('1 = 0');
            } else {
                $centersQuery->whereKey($user->center_id);
            }
        }

        $activeStudents = (clone $students)
            ->where('is_active', true)
            ->count();

        $activeTeachers = (clone $teachers)
            ->where('is_active', true)
            ->count();

        $activeCircles = (clone $circles)
            ->where('is_active', true)
            ->count();

        $todayAttendance = (clone $attendance)
            ->whereDate('attendance_date', $today)
            ->count();

        $monthlyPayments = (clone $payments)
            ->whereBetween('paid_at', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->sum('amount');

        return [
            Stat::make(
               'إجمالي المراكز',
                Center::count()
            )
                ->description('عدد مراكز التحفيظ المسجلة')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('primary')
                ->chart([2, 4, 3, 6, 5, 8, Center::count()]),
  Stat::make(
                'المراكز غير النشطة',
                Center::where('is_active', false)->count()
            ) ->description('المراكز المتوقفة')
                ->descriptionIcon('heroicon-m-pause-circle')
                ->color('danger'),

          
            Stat::make(
                'المعلمون النشطون',
                number_format($activeTeachers)
            )
                ->description('المعلمون بحالة نشطة')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('info'),

            Stat::make(
                'الحلقات النشطة',
                number_format($activeCircles)
            )
                ->description('الحلقات المتاحة')
                ->descriptionIcon('heroicon-o-book-open')
                ->color('warning'),

            Stat::make(
                'سجلات الحضور اليوم',
                number_format($todayAttendance)
            )
                ->description('جميع حالات الحضور المسجلة اليوم')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('success'),

            Stat::make(
                'مدفوعات الشهر',
                number_format((float) $monthlyPayments, 2)
                    . ' د.ل'
            )
                ->description('إجمالي المبالغ المسجلة هذا الشهر')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('primary'),
        ];
    }

    /**
     * تطبيق نطاق المركز على الاستعلام.
     */
    private function scope(Builder $query): Builder
    {
        return CenterQueryScope::apply($query);
    }
}
