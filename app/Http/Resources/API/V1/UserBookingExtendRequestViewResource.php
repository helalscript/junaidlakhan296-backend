<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBookingExtendRequestViewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'current_booking' => [
                'id' => $this->id,
                'unique_id' => $this->unique_id,
                'parking_space' => $this->parkingSpace,
                'start_time' => $this->start_time->format('M-d-Y H:i A'),
                'end_time' => $this->end_time->format('M-d-Y H:i A'),
            ],
            'extend_booking' => [
                'current_end_time' => $this->end_time->format('M-d-Y H:i A'),
                'new_end_time' =>$this->extension_new_end_time,
                'extension_fee' =>$this->extension_price,
                'service_fee' =>$this->extension_fee,
                'total_amount' =>$this->extension_total_price,
            ],
        ];
    }
}
