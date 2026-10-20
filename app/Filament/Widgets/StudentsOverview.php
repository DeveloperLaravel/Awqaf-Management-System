<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class StudentsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();

        // منع عرض أي إحصائيات دون مستخدم مسجل.
        if (! $user) {
            return [];
        }

        $query = Student::query();

        // عزل الإحصائيات حسب صلاحيات المستخدم.
        if (! Gate::allows('centers.view_all')) {
            if (! $user->center_id) {
                return [
                    Stat::make('إجمالي الطلاب', 0)
                        ->description('لا يوجد مركز مرتبط بحسابك')
                        ->color('gray'),
                ];
            }

            $query->where(
                'center_id',
                $user->center_id
            );
        }

        $total = (clone $query)->count();

        $active = (clone $query)
            ->where('is_active', true)
            ->count();

        $inactive = (clone $query)
            ->where('is_active', false)
            ->count();

        return [
            Stat::make('إجمالي الطلاب', $total)
                ->description('جميع الطلاب المسجلين')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('الطلاب النشطون', $active)
                ->description('الطلاب النشطون في المركز')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('success'),

            Stat::make('الطلاب غير النشطين', $inactive)
                ->description('الطلاب غير النشطين')
                ->descriptionIcon('heroicon-m-pause-circle')
                ->color('danger'),
        ];
    }
}