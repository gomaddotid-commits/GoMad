<?php
// File: app/Http/Resources/Api/AgencyResource.php
// Deskripsi: API Resource untuk Agency (Public)

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_name' => $this->agency_name,
            'slug' => $this->slug,
            'logo' => $this->logo ?? null,
            'cover_image' => $this->cover_image ?? null,
            'address' => $this->address,
            'description' => $this->description,
            'founded_year' => $this->founded_year,
            'fleet_size' => $this->fleet_size,
            'is_verified' => $this->is_verified,
            'rating' => (float) $this->rating,
            'total_bookings' => $this->total_bookings,
            'services' => $this->services,
            'social_media' => $this->social_media,
            'business_hours' => $this->business_hours,
            'contact_person' => $this->contact_person,
            'contact_alternate' => $this->contact_alternate,
            'email_alternate' => $this->email_alternate,
            'gallery' => $this->when($this->gallery, function () {
                return collect($this->gallery)->map(fn($item) => $item)->toArray();
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

// End of file