<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'iftar_day_id' => $this->iftar_day_id,
            'nama' => $this->nama,
            'no_telefon' => $this->no_telefon,
            'bil_pax' => $this->bil_pax,
            'checkin_status' => $this->checkin_status,
            'reminder_sent' => $this->reminder_sent,
            'is_cancelled' => $this->is_cancelled,
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancellation_count' => $this->cancellation_count,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'iftar_day' => new IftarDayResource($this->whenLoaded('iftarDay')),
        ];
    }
}
