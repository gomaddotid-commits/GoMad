<?php
// File: app/Http/Resources/Api/SettlementResource.php
// Deskripsi: API Resource untuk Settlement

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettlementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_agent' => [
                'id' => $this->paymentAgent?->id,
                'agent_name' => $this->paymentAgent?->agent_name,
                'owner_name' => $this->paymentAgent?->owner_name,
            ],
            'period_start' => $this->period_start->format('Y-m-d'),
            'period_end' => $this->period_end->format('Y-m-d'),
            'period_label' => $this->period_start->format('d M') . ' - ' . $this->period_end->format('d M Y'),
            'total_transactions' => $this->total_transactions,
            'total_amount' => (float) $this->total_amount,
            'total_amount_formatted' => 'Rp ' . number_format($this->total_amount, 0, ',', '.'),
            'total_commission' => (float) $this->total_commission,
            'total_commission_formatted' => 'Rp ' . number_format($this->total_commission, 0, ',', '.'),
            'amount_to_settle' => (float) $this->amount_to_settle,
            'amount_to_settle_formatted' => 'Rp ' . number_format($this->amount_to_settle, 0, ',', '.'),
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'payment_method' => $this->payment_method,
            'transaction_id' => $this->transaction_id,
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'verified_by' => $this->when($this->verified_by, [
                'id' => $this->verifier?->id,
                'name' => $this->verifier?->name,
            ]),
            'verified_at' => $this->verified_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

// End of file