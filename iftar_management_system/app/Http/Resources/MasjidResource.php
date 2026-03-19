<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasjidResource extends JsonResource
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
            'nama_masjid' => $this->nama_masjid,
            'alamat' => $this->alamat,
            'negeri' => $this->negeri,
            'contact_phone' => $this->contact_phone,
            'logo_url' => $this->logo_url,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Conditional includes
            'iftar_days' => IftarDayResource::collection($this->whenLoaded('iftarDays')),
            'sponsorships' => SponsorshipResource::collection($this->whenLoaded('sponsorships')),
        ];
    }
}
