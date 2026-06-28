<?php
// File: app/Http/Resources/Api/ReviewResource.php
// Deskripsi: API Resource untuk Review

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'review' => $this->review,
            'customer' => [
                'id' => $this->customer?->id,
                'name' => $this->customer?->name,
                'avatar_url' => $this->customer?->avatar_url,
            ],
            'created_at' => $this->created_at->format('d M Y'),
        ];
    }
}

// End of file