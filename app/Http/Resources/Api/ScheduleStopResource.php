<?php
// File: app/Http/Resources/Api/ScheduleStopResource.php
// Deskripsi: API Resource untuk ScheduleStop

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleStopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'route_stop_id' => $this->route_stop_id,
            'city_name' => $this->routeStop?->city_name,
            'stop_order' => $this->routeStop?->stop_order,
            'is_pickup_available' => $this->is_pickup_available,
            'is_dropoff_available' => $this->is_dropoff_available,
            'estimated_time' => $this->estimated_time,
            'latitude' => $this->routeStop?->latitude ? (float) $this->routeStop->latitude : null,
            'longitude' => $this->routeStop?->longitude ? (float) $this->routeStop->longitude : null,
        ];
    }
}

// End of file