<?php

namespace App\Filament\Resources\StudentAssessments\Pages;

use App\Filament\Resources\StudentAssessments\StudentAssessmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentAssessment extends ViewRecord
{
    protected static string $resource = StudentAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
