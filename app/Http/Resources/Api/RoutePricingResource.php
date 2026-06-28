<?php
// File: app/Http/Resources/Api/RoutePricingResource.php
// Deskripsi: API Resource untuk RoutePricing

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoutePricingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'origin_stop_id' => $this->origin_stop_id,
            'origin_city' => $this->originStop?->city_name,
            'destination_stop_id' => $this->destination_stop_id,
            'destination_city' => $this->destinationStop?->city_name,
            'price' => (float) $this->price,
            'price_formatted' => 'Rp ' . number_format($this->price, 0, ',', '.'),
        ];
    }
}

// End of file