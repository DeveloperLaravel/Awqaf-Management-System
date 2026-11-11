<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Centers\CenterResource;
use App\Concerns\HasDashboardCenterScope;
use App\Models\Center;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingCenters extends TableWidget
{
    use HasDashboardCenterScope;

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return Center::query()
            ->whereIn('id', $this->dashboardCenterIds())
            ->whereIn('approval_status', [
                'pending_parent_approval',
                'pending_super_admin_approval',
            ])
            ->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->heading('طلبات اعتماد المراكز')
            ->description('المراكز والفروع التي تنتظر الموافقة')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم المركز')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('رمز المركز')
                    ->searchable(),

                Tables\Columns\TextColumn::make('approval_status')
                    ->label('حالة الاعتماد')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending_parent_approval' => 'بانتظار موافقة المركز الأب',
                        'pending_super_admin_approval' => 'بانتظار موافقة الإدارة العامة',
                        default => $state,
                    })
                    ->color('warning'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (Center $record) =>
                        CenterResource::getUrl('edit', [
                            'record' => $record,
                        ])
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}