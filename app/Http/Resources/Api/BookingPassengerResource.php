<?php
// File: app/Http/Resources/Api/BookingPassengerResource.php
// Deskripsi: API Resource untuk BookingPassenger

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingPassengerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'passenger_name' => $this->passenger_name,
            'passenger_phone' => $this->passenger_phone,
            'baggage_weight' => (float) ($this->baggage_weight ?? 0),
            'seat_number' => $this->seat_number,
            'picked_up_at' => $this->picked_up_at?->format('Y-m-d H:i:s'),
            'dropped_off_at' => $this->dropped_off_at?->format('Y-m-d H:i:s'),
            'is_picked_up' => !is_null($this->picked_up_at),
            'is_dropped_off' => !is_null($this->dropped_off_at),
        ];
    }
}

// End of file