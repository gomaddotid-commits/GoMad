<?php
// File: app/Http/Resources/Api/WithdrawalResource.php
// Deskripsi: API Resource untuk Withdrawal

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'amount' => (float) $this->amount,
            'amount_formatted' => 'Rp ' . number_format($this->amount, 0, ',', '.'),
            'admin_fee' => (float) $this->admin_fee,
            'net_amount' => (float) $this->net_amount,
            'net_amount_formatted' => 'Rp ' . number_format($this->net_amount, 0, ',', '.'),
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->maskBankAccount($this->bank_account_number),
            'bank_account_name' => $this->bank_account_name,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'approved_by' => $this->when($this->approved_by, [
                'id' => $this->approver?->id,
                'name' => $this->approver?->name,
            ]),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'rejected_reason' => $this->rejected_reason,
            'transaction_id' => $this->transaction_id,
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    private function maskBankAccount(string $account): string
    {
        $length = strlen($account);
        if ($length <= 4) {
            return $account;
        }
        return substr($account, 0, 4) . str_repeat('*', $length - 8) . substr($account, -4);
    }
}

// End of file