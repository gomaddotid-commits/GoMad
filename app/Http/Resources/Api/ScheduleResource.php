<?php
// File: app/Http/Resources/Api/ScheduleResource.php
// Deskripsi: API Resource untuk Schedule

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency' => [
                'id' => $this->agency?->id,
                'name' => $this->agency?->agency_name,
                'slug' => $this->agency?->slug,
                'logo' => $this->agency?->logo ? asset('storage/' . $this->agency->logo) : null,
                'is_verified' => $this->agency?->is_verified,
                'rating' => (float) ($this->agency?->rating ?? 0),
            ],
            'route' => [
                'id' => $this->route?->id,
                'route_name' => $this->route?->route_name,
                'origin_city' => $this->route?->origin_city,
                'destination_city' => $this->route?->destination_city,
                'distance_km' => (float) ($this->route?->distance_km ?? 0),
                'estimated_duration' => $this->route?->estimated_duration,
            ],
            'vehicle' => [
                'id' => $this->vehicle?->id,
                'plate_number' => $this->vehicle?->plate_number,
                'brand' => $this->vehicle?->brand,
                'model' => $this->vehicle?->model,
                'capacity' => $this->vehicle?->capacity,
                'type' => $this->vehicle?->type,
            ],
            'driver' => $this->when($this->driver_id, [
                'id' => $this->driver?->id,
                'name' => $this->driver?->name,
                'phone' => $this->driver?->phone,
            ]),
            'departure_date' => $this->departure_date->format('Y-m-d'),
            'departure_date_formatted' => $this->departure_date->format('d M Y'),
            'departure_time' => $this->departure_time,
            'travel_class' => $this->travel_class,
            'travel_class_label' => \App\Enums\TravelClass::tryFrom($this->travel_class)?->label() ?? $this->travel_class,
            'price_per_seat' => (float) $this->price_per_seat,
            'price_per_seat_formatted' => 'Rp ' . number_format($this->price_per_seat, 0, ',', '.'),
            'baggage_limit_kg' => (float) $this->baggage_limit_kg,
            'max_capacity' => $this->max_capacity,
            'available_seats' => $this->available_seats,
            'is_full' => $this->is_full,
            'occupancy_rate' => $this->occupancy_rate,
            'is_active' => $this->is_active,
            'stops' => ScheduleStopResource::collection($this->whenLoaded('scheduleStops')),
            'route_pricing' => RoutePricingResource::collection($this->whenLoaded('routePricing')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

// End of file