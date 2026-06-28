<?php
// File: app/Http/Resources/Api/WalletResource.php
// Deskripsi: API Resource untuk Wallet

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'available_balance' => (float) $this->available_balance,
            'available_balance_formatted' => 'Rp ' . number_format($this->available_balance, 0, ',', '.'),
            'pending_balance' => (float) $this->pending_balance,
            'pending_balance_formatted' => 'Rp ' . number_format($this->pending_balance, 0, ',', '.'),
            'total_balance' => (float) ($this->available_balance + $this->pending_balance),
            'total_balance_formatted' => 'Rp ' . number_format($this->available_balance + $this->pending_balance, 0, ',', '.'),
            'total_earned' => (float) $this->total_earned,
            'total_withdrawn' => (float) $this->total_withdrawn,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

// End of file