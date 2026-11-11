<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemorizationRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'center_id' => $this->center_id,
            'student_id' => $this->student_id,
            'circle_id' => $this->circle_id,
            'teacher_id' => $this->teacher_id,

            'center' => $this->whenLoaded('center', fn () => [
                'id' => $this->center?->id,
                'name' => $this->center?->name,
            ]),

            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student?->id,
                'name' => $this->student?->full_name,
            ]),

            'circle' => $this->whenLoaded('circle', fn () => [
                'id' => $this->circle?->id,
                'name' => $this->circle?->name,
            ]),

            'teacher' => $this->whenLoaded('teacher', fn () => [
                'id' => $this->teacher?->id,
                'name' => $this->teacher?->full_name,
            ]),

            'record_date' => $this->record_date?->format('Y-m-d'),

            'record_type' => $this->record_type,

            'record_type_label' => match ($this->record_type) {
                'new' => 'حفظ جديد',
                'review' => 'مراجعة',
                'revision' => 'مراجعة سابقة',
                default => 'غير محدد',
            },

            'surah_name' => $this->surah_name,

            'from_ayah' => $this->from_ayah,
            'to_ayah' => $this->to_ayah,

            'ayah_range' => $this->from_ayah . ' - ' . $this->to_ayah,

            'grade' => $this->grade !== null
                ? (float) $this->grade
                : null,

            'notes' => $this->notes,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
