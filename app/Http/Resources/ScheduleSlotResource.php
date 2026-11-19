<?php

namespace App\Http\Resources;

use App\Models\ProgramSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProgramSchedule
 */
class ScheduleSlotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day' => $this->day_of_week->value,
            'start' => substr($this->start_time, 0, 5),
            'end' => substr($this->end_time, 0, 5),
            'program' => $this->whenLoaded('program', fn () => ProgramResource::make($this->program)->resolve($request)),
        ];
    }
}
