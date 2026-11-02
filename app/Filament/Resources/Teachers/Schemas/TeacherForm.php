<?php

declare(strict_types=1);

namespace App\Filament\Resources\Teachers\Schemas;

use App\Filament\Resources\Teachers\TeacherResource;
use App\Models\Center;
use App\Models\Teacher;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

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

            // المركز الأساسي للتوافق مع جدول teachers الحالي.
            Hidden::make('center_id')
                ->required(),

            // المراكز التي يعمل بها المعلم.
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
                ->live()
                ->columnSpanFull()

                // عند فتح سجل موجود، مزامنة المركز الأساسي.
                ->afterStateHydrated(
                    function (
                        Select $component,
                        $state,
                        Set $set
                    ): void {
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

                        if ($visibleIds !== []) {
                            $set('center_id', $visibleIds[0]);
                        }
                    }
                )

                // تحديث center_id عند تغيير المراكز المختارة.
                ->afterStateUpdated(
                    function ($state, Set $set): void {
                        $selectedIds = collect($state ?? [])
                            ->map(fn ($id): int => (int) $id)
                            ->unique()
                            ->values()
                            ->all();

                        $set('center_id', $selectedIds[0] ?? null);
                    }
                )

                // حفظ علاقة المراكز في center_teacher.
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
                            ->unique()
                            ->values()
                            ->all();

                        if ($selectedIds === []) {
                            throw ValidationException::withMessages([
                                'data.centers' =>
                                    'يجب اختيار مركز واحد على الأقل.',
                            ]);
                        }

                        // منع ربط المعلم بمراكز خارج صلاحية المستخدم.
                        $invalidIds = array_diff(
                            $selectedIds,
                            $allowedIds
                        );

                        if ($invalidIds !== []) {
                            throw ValidationException::withMessages([
                                'data.centers' =>
                                    'لا يمكنك ربط المعلم بمركز غير مسموح لك بإدارته.',
                            ]);
                        }

                        // التأكد من وجود جميع المراكز.
                        $existingCount = Center::query()
                            ->whereIn('id', $selectedIds)
                            ->count();

                        if ($existingCount !== count($selectedIds)) {
                            throw ValidationException::withMessages([
                                'data.centers' =>
                                    'يوجد مركز غير موجود ضمن الاختيارات.',
                            ]);
                        }

                        // الاحتفاظ بالارتباطات خارج نطاق المستخدم.
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
