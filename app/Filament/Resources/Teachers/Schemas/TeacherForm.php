<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers\Schemas;

use App\Filament\Resources\Teachers\TeacherResource;
use App\Models\Teacher;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class TeacherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('full_name')
                ->label('اسم المعلم')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            TextInput::make('phone')
                ->label('رقم الهاتف')
                ->tel()
                ->maxLength(30),

            TextInput::make('email')
                ->label('البريد الإلكتروني')
                ->email()
                ->maxLength(255),

            TextInput::make('qualification')
                ->label('المؤهل العلمي')
                ->maxLength(255),

            DatePicker::make('hire_date')
                ->label('تاريخ التعيين')
                ->native(false),

            Select::make('centers')
                ->label('المراكز التي يعمل بها المعلم')
                ->relationship(
                    name: 'centers',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query): Builder =>
                        $query->whereIn(
                            'centers.id',
                            TeacherResource::allowedCenterIds()
                        )
                )
                ->multiple()
                ->searchable()
                ->preload()
                ->required()
                ->minItems(1)
                ->columnSpanFull()
                ->afterStateHydrated(
                    function (Select $component, $state): void {
                        $allowedIds = TeacherResource::allowedCenterIds();

                        $visibleIds = collect($state ?? [])
                            ->map(fn ($id): int => (int) $id)
                            ->filter(
                                fn (int $id): bool =>
                                    in_array($id, $allowedIds, true)
                            )
                            ->values()
                            ->all();

                        $component->state($visibleIds);
                    }
                )
                ->saveRelationshipsUsing(
                    function (
                        Select $component,
                        Model $record,
                        $state
                    ): void {
                        if (! $record instanceof Teacher) {
                            return;
                        }

                        $allowedIds = TeacherResource::allowedCenterIds();

                        $selectedIds = collect($state ?? [])
                            ->map(fn ($id): int => (int) $id)
                            ->filter(
                                fn (int $id): bool =>
                                    in_array($id, $allowedIds, true)
                            )
                            ->unique()
                            ->values()
                            ->all();

                        $existingIds = $record->centers()
                            ->pluck('centers.id')
                            ->map(fn ($id): int => (int) $id)
                            ->all();

                        $protectedIds = array_values(
                            array_diff($existingIds, $allowedIds)
                        );

                        $finalIds = array_values(
                            array_unique(
                                array_merge($protectedIds, $selectedIds)
                            )
                        );

                        if ($finalIds === []) {
                            throw new \RuntimeException(
                                'يجب ربط المعلم بمركز واحد على الأقل.'
                            );
                        }

                        $record->centers()->sync($finalIds);
                    }
                ),

            Select::make('user_id')
                ->label('حساب المستخدم المرتبط بالمعلم')
                ->relationship(
                    name: 'user',
                    titleAttribute: 'name',
                    modifyQueryUsing: function (
                        Builder $query
                    ): Builder {
                        if (Gate::allows('centers.view_all')) {
                            return $query;
                        }

                        return $query->whereIn(
                            'center_id',
                            TeacherResource::allowedCenterIds()
                        );
                    }
                )
                ->searchable()
                ->preload()
                ->nullable(),

            Toggle::make('is_active')
                ->label('المعلم نشط')
                ->default(true),
        ]);
    }
}