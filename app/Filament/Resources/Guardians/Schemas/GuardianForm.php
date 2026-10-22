<?php

declare(strict_types=1);

namespace App\Filament\Resources\Guardians\Schemas;

use App\Models\Center;
use App\Models\User;
use App\Services\CenterHierarchy;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class GuardianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('center_id')
                    ->label('المركز')
                    ->relationship(
                        name: 'center',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query): Builder {
                            $user = request()->user();

                            if (! $user instanceof User) {
                                return $query->whereRaw('1 = 0');
                            }

                            $centerIds = app(CenterHierarchy::class)
                                ->accessibleIdsForUser($user);

                            return $query->whereIn(
                                'centers.id',
                                collect($centerIds)->map(
                                    fn ($id) => (int) $id
                                )->all()
                            );
                        }
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->validationMessages([
                        'required' => 'يرجى اختيار المركز.',
                    ]),

                TextInput::make('full_name')
                    ->label('اسم ولي الأمر')
                    ->required()
                    ->maxLength(255)
                    ->validationMessages([
                        'required' => 'يرجى إدخال اسم ولي الأمر.',
                    ]),

                TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->tel()
                    ->required()
                    ->maxLength(30)
                    ->validationMessages([
                        'required' => 'يرجى إدخال رقم الهاتف.',
                    ]),

                TextInput::make('email')
                    ->label('البريد الإلكتروني')
                    ->email()
                    ->maxLength(255)
                    ->nullable(),

                TextInput::make('relationship')
                    ->label('صلة القرابة')
                    ->maxLength(100)
                    ->placeholder('مثال: الأب، الأم، الجد'),

                Toggle::make('is_active')
                    ->label('حساب ولي الأمر نشط')
                    ->default(true)
                    ->required(),

                Textarea::make('address')
                    ->label('العنوان')
                    ->rows(3)
                    ->columnSpanFull()
                    ->nullable(),
            ]);
    }
}
