<?php
// File: app/Http/Resources/Api/RouteResource.php
// Deskripsi: API Resource untuk Route

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'route_name' => $this->route_name,
            'origin_city' => $this->origin_city,
            'destination_city' => $this->destination_city,
            'distance_km' => (float) ($this->distance_km ?? 0),
            'estimated_duration' => $this->estimated_duration,
            'estimated_duration_formatted' => $this->estimated_duration ? 
                floor($this->estimated_duration / 60) . ' jam ' . ($this->estimated_duration % 60) . ' menit' : null,
            'is_active' => $this->is_active,
            'stops' => RouteStopResource::collection($this->whenLoaded('stops')),
            'stops_count' => $this->when($this->stops_count, $this->stops_count),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

// End of file