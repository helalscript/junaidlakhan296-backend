<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class indexForUserHourlyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'parking_space_id' => $this->parking_space_id,
            'parking_space_title' => optional($this->parkingSpace)->title,
            'per_hour_rate' => $this->rate,
            'available_slots' => $this->available_slots,
            'parking_space_slug' => optional($this->parkingSpace)->slug,
            'parking_space_images' => optional($this->parkingSpace)->gallery_images[0] ?? null,
            'parking_space_latitude' => optional($this->parkingSpace)->latitude,
            'parking_space_longitude' => optional($this->parkingSpace)->longitude,
            'parking_space_address' => optional($this->parkingSpace)->address,
            'estimated_hours' => $this->estimated_hours,
            'estimated_price' => $this->estimated_price,
            'distance' => $this->distance,
            'status' => ($this->available_slots > 0) ? 'available' : 'unavailable',
        ];
    }
}
