<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Concerns\HasDashboardCenterScope;
use App\Models\AttendanceRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentAttendance extends TableWidget
{
    use HasDashboardCenterScope;

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return $this->dashboardQuery(AttendanceRecord::class)
            ->with(['student', 'circle', 'teacher'])
            ->latest('attendance_date');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->heading('أحدث سجلات الحضور')
            ->description('متابعة آخر عمليات تسجيل الحضور')
            ->columns([
                Tables\Columns\TextColumn::make('attendance_date')
                    ->label('التاريخ')
                    ->date('Y/m/d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.full_name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('circle.name')
                    ->label('الحلقة')
                    ->placeholder('غير محددة'),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('المعلم')
                    ->placeholder('غير محدد'),

                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'present' => 'حاضر',
                        'absent' => 'غائب',
                        'late' => 'متأخر',
                        'excused' => 'مستأذن',
                        default => 'غير محدد',
                    })
                    ->color(fn ($state) => match ($state) {
                        'present' => 'success',
                        'absent' => 'danger',
                        'late' => 'warning',
                        'excused' => 'info',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('attendance_date', 'desc')
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}