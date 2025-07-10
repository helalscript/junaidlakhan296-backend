<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBookingIndexResource extends JsonResource
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
            'unique_id' => $this->unique_id,
            'pricing_type' => $this->pricing_type,
            'number_of_slot' => $this->number_of_slot,
            'start_time' => $this->start_time->format('M-d-Y H:i A'),
            'end_time' => $this->end_time->format('M-d-Y H:i A'),
            'created_at' => $this->created_at->format('M-d-Y H:i A'),
            'is_critical' => $this->is_critical,
            'is_expired' => $this->is_expired,
            'is_running' => $this->is_running,
            'parking_status' => $this->parking_status,
            'status' => $this->status,
            'parking_space' => $this->parkingSpace,
            'payment' => $this->payment,
        ];
    }
}
