<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Concerns\HasDashboardCenterScope;
use App\Models\Student;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentStudents extends TableWidget
{
    use HasDashboardCenterScope;

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return $this->dashboardQuery(Student::class)
            ->with('center')
            ->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->heading('أحدث الطلاب المسجلين')
            ->description('آخر الطلاب الذين تمت إضافتهم إلى المنظومة')
            ->columns([
                Tables\Columns\TextColumn::make('student_code')
                    ->label('رقم الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('center.name')
                    ->label('المركز')
                    ->searchable(),

                Tables\Columns\TextColumn::make('gender')
                    ->label('الجنس')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                        default => 'غير محدد',
                    })
                    ->color(fn ($state) => match ($state) {
                        'male' => 'info',
                        'female' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('enrollment_date')
                    ->label('تاريخ الالتحاق')
                    ->date('Y/m/d')
                    ->sortable(),

                Tables\Columns\TextColumn::make('is_active')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state
                        ? 'نشط'
                        : 'غير نشط')
                    ->color(fn ($state) => $state
                        ? 'success'
                        : 'danger'),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}