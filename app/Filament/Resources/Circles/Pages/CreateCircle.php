<?php

declare(strict_types=1);

namespace App\Filament\Resources\Circles\Pages;

use App\Filament\Resources\Circles\CircleResource;
use App\Services\CircleAssignmentValidator;
use Filament\Resources\Pages\CreateRecord;

class CreateCircle extends CreateRecord
{
    protected static string $resource = CircleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        abort_unless($user, 403);

        app(CircleAssignmentValidator::class)
            ->validate($user, $data);

        return $data;
    }
}