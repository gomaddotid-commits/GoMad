<?php
// File: app/Http/Resources/Api/AgencyDetailResource.php
// Deskripsi: API Resource untuk Agency Detail (Owner/Admin)

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'agency_name' => $this->agency_name,
            'slug' => $this->slug,
            'logo' => $this->logo ? asset('storage/' . $this->logo) : null,
            'cover_image' => $this->cover_image ? asset('storage/' . $this->cover_image) : null,
            'business_license' => $this->business_license ? asset('storage/' . $this->business_license) : null,
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
            'zone_coverage' => $this->zone_coverage,
            'contact_person' => $this->contact_person,
            'contact_alternate' => $this->contact_alternate,
            'email_alternate' => $this->email_alternate,
            'gallery' => $this->when($this->gallery, function () {
                return collect($this->gallery)->map(fn($item) => asset('storage/' . $item))->toArray();
            }),
            'profile_complete_percentage' => $this->profile_complete_percentage,
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
            'vehicles_count' => $this->when($this->vehicles_count, $this->vehicles_count),
            'drivers_count' => $this->when($this->drivers_count, $this->drivers_count),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

// End of file