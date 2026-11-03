<?php

namespace App\Filament\Resources\StudentAssessments\Pages;

use App\Filament\Resources\StudentAssessments\StudentAssessmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStudentAssessments extends ListRecords
{
    protected static string $resource = StudentAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make() ->label('إضافة تقييم أسبوعي'),
        ];
    }
}
