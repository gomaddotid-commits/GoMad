<?php
// File: app/Http/Resources/Api/PaymentResource.php
// Deskripsi: API Resource untuk Payment

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => (float) $this->amount,
            'amount_formatted' => 'Rp ' . number_format($this->amount, 0, ',', '.'),
            'payment_type' => $this->payment_type,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'payment_method' => $this->payment_method,
            'payment_channel' => $this->payment_channel,
            'transaction_id' => $this->transaction_id,
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'expired_at' => $this->expired_at?->format('Y-m-d H:i:s'),
            'is_expired' => $this->is_expired,
        ];
    }
}

// End of file