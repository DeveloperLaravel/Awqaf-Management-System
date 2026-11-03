<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuardianStudents\Schemas;

use App\Models\Center;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\CenterHierarchy;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class GuardianStudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Select::make('center_id')
                ->label('المركز')
                ->options(fn () => self::availableCenters()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->preload()
                ->live()
                ->required()
                ->afterStateUpdated(function ($set) {
                    $set('student_id', null);
                    $set('guardian_id', null);
                }),

            Select::make('student_id')
                ->label('الطالب')
                ->options(function (Get $get) {
                    $centerId = $get('center_id');

                    if (! $centerId) {
                        return [];
                    }

                    return Student::query()
                        ->where('center_id', $centerId)
                        ->orderBy('full_name')
                        ->pluck('full_name', 'id')
                        ->all();
                })
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->disabled(fn (Get $get) => ! $get('center_id')),

           Select::make('guardian_id')
    ->label('ولي الأمر')
    ->options(function (Get $get) {
        $centerId = $get('center_id');

        if (! $centerId) {
            return [];
        }

        return Guardian::query()
            ->where('center_id', $centerId)
            ->orderBy('full_name')
            ->pluck('full_name', 'id')
            ->all();
    })
    ->searchable()
    ->preload()
    ->required()
    ->live()
    ->disabled(fn (Get $get) => ! $get('center_id')),

            TextInput::make('relationship')
                ->label('صلة القرابة')
                ->placeholder('مثال: أب، أم، أخ، عم')
                ->maxLength(50),

            Checkbox::make('is_primary')
                ->label('ولي الأمر الأساسي')
                ->default(false),

            Checkbox::make('can_receive_notifications')
                ->label('السماح باستقبال الإشعارات')
                ->default(true),

            Checkbox::make('can_pickup')
                ->label('السماح باستلام الطالب')
                ->default(false),

        ]);
    }

    protected static function availableCenters()
    {
        $user = Auth::user();

        if (! $user) {
            return Center::query()->whereRaw('1 = 0');
        }

        $centerIds = app(CenterHierarchy::class)
            ->accessibleIdsForUser($user);

        return Center::query()
            ->whereIn('id', $centerIds)
            ->where('is_active', true)
            ->where('approval_status', 'active');
    }
}
