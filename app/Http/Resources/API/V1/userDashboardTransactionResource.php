<?php

namespace App\Http\Resources\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserDashboardTransactionResource extends JsonResource
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
            'transaction_number' => $this->transaction_number,
            'amount' => $this->amount,
            'address' => $this->booking?->parkingSpace?->address,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('M-d-Y '),
        ];
    }
}
