<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuardianStudents\Tables;

use App\Models\Center;
use App\Models\GuardianStudent;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GuardianStudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query) => $query
                    ->whereIn('center_id', self::accessibleCenterIds())
            )
            ->columns([
                TextColumn::make('student.full_name')
                    ->label('اسم الطالب')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('guardian.full_name')
                    ->label('اسم ولي الأمر')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('guardian.phone')
                    ->label('رقم الهاتف')
                    ->searchable(),

                TextColumn::make('center.name')
                    ->label('المركز / الفرع')
                    ->sortable(),

                TextColumn::make('relationship')
                    ->label('صلة القرابة')
                    ->placeholder('غير محدد'),

                IconColumn::make('is_primary')
                    ->label('ولي الأمر الأساسي')
                    ->boolean(),

                IconColumn::make('can_receive_notifications')
                    ->label('استقبال الإشعارات')
                    ->boolean(),

                IconColumn::make('can_pickup')
                    ->label('مسموح بالاستلام')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('تاريخ الربط')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('center_id')
                    ->label('المركز / الفرع')
                    ->options(fn () => self::availableCenters()),

                SelectFilter::make('is_primary')
                    ->label('ولي الأمر الأساسي')
                    ->options([
                        1 => 'نعم',
                        0 => 'لا',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }

    protected static function accessibleCenterIds(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        if ($user->hasRole('super_admin')) {
            return Center::query()->pluck('id')->all();
        }

        return app(CenterHierarchy::class)
            ->accessibleIdsForUser($user);
    }

    protected static function availableCenters(): array
    {
        return Center::query()
            ->whereIn('id', self::accessibleCenterIds())
            ->where('is_active', true)
            ->where('approval_status', 'active')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
