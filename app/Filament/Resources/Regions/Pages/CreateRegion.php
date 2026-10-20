<?php

namespace App\Filament\Resources\Regions\Pages;

use App\Filament\Resources\Regions\RegionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRegion extends CreateRecord
{
    protected static string $resource = RegionResource::class;
      protected function authorizeAccess(): void
    {
        abort_unless(
            RegionResource::canCreate(),
            403
        );
    }
}
