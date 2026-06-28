<?php
// File: app/Http/Resources/Api/BookingResource.php
// Deskripsi: API Resource untuk Booking

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'total_passengers' => $this->total_passengers,
            'total_price' => (float) $this->total_price,
            'total_price_formatted' => 'Rp ' . number_format($this->total_price, 0, ',', '.'),
            'schedule' => new ScheduleResource($this->whenLoaded('schedule')),
            'customer' => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->name,
                'phone' => $this->customer?->phone,
            ],
            'origin_stop' => [
                'id' => $this->originStop?->id,
                'city_name' => $this->originStop?->city_name,
            ],
            'destination_stop' => [
                'id' => $this->destinationStop?->id,
                'city_name' => $this->destinationStop?->city_name,
            ],
            'pickup_address' => $this->pickup_address,
            'pickup_maps_link' => $this->pickup_maps_link,
            'pickup_latitude' => $this->pickup_latitude ? (float) $this->pickup_latitude : null,
            'pickup_longitude' => $this->pickup_longitude ? (float) $this->pickup_longitude : null,
            'destination_address' => $this->destination_address,
            'destination_maps_link' => $this->destination_maps_link,
            'destination_latitude' => $this->destination_latitude ? (float) $this->destination_latitude : null,
            'destination_longitude' => $this->destination_longitude ? (float) $this->destination_longitude : null,
            'passengers' => BookingPassengerResource::collection($this->whenLoaded('passengers')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'cash_payment' => new CashPaymentResource($this->whenLoaded('cashPayment')),
            'review' => new ReviewResource($this->whenLoaded('review')),
            'e_ticket_url' => $this->e_ticket_url,
            'special_notes' => $this->special_notes,
            'can_cancel' => $this->can_cancel,
            'is_paid' => $this->is_paid,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

// End of file