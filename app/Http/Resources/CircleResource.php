<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CircleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'level' => $this->level,

            'center_id' => $this->center_id,
            'teacher_id' => $this->teacher_id,

            'room' => $this->room,
            'capacity' => $this->capacity,

            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,

            'days' => $this->days,

            'is_active' => (bool) $this->is_active,

            'center' => $this->whenLoaded(
                'center',
                fn () => [
                    'id' => $this->center?->id,
                    'name' => $this->center?->name,
                ]
            ),

            'teacher' => $this->whenLoaded(
                'teacher',
                fn () => $this->teacher ? [
                    'id' => $this->teacher->id,
                    'user_id' => $this->teacher->user_id,
                    'full_name' => $this->teacher->full_name,
                ] : null
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
