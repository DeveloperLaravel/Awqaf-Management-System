<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'لوحة التحكم';

    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'xl' => 3,
        ];
    }
}