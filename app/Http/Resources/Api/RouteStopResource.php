<?php
// File: app/Http/Resources/Api/RouteStopResource.php
// Deskripsi: API Resource untuk RouteStop

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteStopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'route_id' => $this->route_id,
            'city_name' => $this->city_name,
            'stop_order' => $this->stop_order,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'distance_from_origin' => (float) ($this->distance_from_origin ?? 0),
        ];
    }
}

// End of file