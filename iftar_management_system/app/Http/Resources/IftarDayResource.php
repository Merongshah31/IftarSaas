<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IftarDayResource extends JsonResource
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
            'masjid_id' => $this->masjid_id,
            'tarikh' => $this->tarikh->format('Y-m-d'),
            'kapasiti_max' => $this->kapasiti_max,
            'jumlah_daftar' => $this->jumlah_daftar,
            'available_slots' => $this->kapasiti_max - $this->jumlah_daftar,
            'capacity_percentage' => round(($this->jumlah_daftar / $this->kapasiti_max) * 100, 2),
            'status' => $this->status,
            'is_full' => $this->jumlah_daftar >= $this->kapasiti_max,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Conditional includes
            'masjid' => new MasjidResource($this->whenLoaded('masjid')),
            'participants' => ParticipantResource::collection($this->whenLoaded('participants')),
            'participants_count' => $this->when(isset($this->participants_count), $this->participants_count),
        ];
    }
}
