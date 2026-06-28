<?php
// File: app/Http/Resources/Api/PaymentAgentResource.php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentAgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agent_name' => $this->agent_name,
            'owner_name' => $this->owner_name,
            'owner_phone' => $this->owner_phone,
            'guard_name' => $this->guard_name,
            'guard_phone' => $this->guard_phone,
            'address' => $this->address,
            'kecamatan' => $this->kecamatan,
            'maps_link' => $this->maps_link,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'photo_warung' => $this->photo_warung ? asset('storage/' . $this->photo_warung) : null,
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'commission_rate' => (float) $this->commission_rate,
            'total_transactions' => $this->total_transactions,
            'total_commission' => (float) $this->total_commission,
            'distance_km' => $this->when(isset($this->distance_km), fn() => (float) $this->distance_km),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

// End of file