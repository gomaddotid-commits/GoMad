<?php
// File: app/Http/Resources/Api/CashPaymentResource.php
// Deskripsi: API Resource untuk CashPayment

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CashPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_code' => $this->payment_code,
            'amount' => (float) $this->amount,
            'amount_formatted' => 'Rp ' . number_format($this->amount, 0, ',', '.'),
            'status' => $this->status,
            'payment_agent' => $this->when($this->payment_agent_id, [
                'id' => $this->paymentAgent?->id,
                'agent_name' => $this->paymentAgent?->agent_name,
                'address' => $this->paymentAgent?->address,
            ]),
            'confirmed_at' => $this->confirmed_at?->format('Y-m-d H:i:s'),
            'expired_at' => $this->expired_at?->format('Y-m-d H:i:s'),
            'is_expired' => $this->expired_at && now()->greaterThan($this->expired_at),
        ];
    }
}

// End of file