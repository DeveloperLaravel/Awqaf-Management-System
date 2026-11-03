<?php

declare(strict_types=1);

namespace App\Filament\Resources\CircleStudents\Pages;

use App\Filament\Resources\CircleStudents\CircleStudentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCircleStudents extends ListRecords
{
    protected static string $resource = CircleStudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('تسجيل طالب في حلقة')
                ->icon('heroicon-o-plus'),
        ];
    }
}
