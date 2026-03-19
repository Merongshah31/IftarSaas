<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SponsorshipResource extends JsonResource
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
            'masjid_id' => $this->masjid_id,
            'nama_sponsor' => $this->nama_sponsor,
            'phone' => $this->phone,
            'jumlah_tajaan' => $this->jumlah_tajaan,
            'jenis_tajaan' => $this->jenis_tajaan,
            'sponsor_coverage' => $this->sponsor_coverage,
            'payment_status' => $this->payment_status,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Conditional includes
            'masjid' => new MasjidResource($this->whenLoaded('masjid')),
            'iftar_day' => new IftarDayResource($this->whenLoaded('iftarDay')),
        ];
    }
}
