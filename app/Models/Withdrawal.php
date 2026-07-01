<?php
// File: app/Models/Withdrawal.php
// Deskripsi: Withdrawal model untuk penarikan dana agency

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;  
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\WithdrawalStatus;

class Withdrawal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'amount',
        'admin_fee',
        'net_amount',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'status',
        'approved_by',
        'approved_at',
        'rejected_reason',
        'transaction_id',
        'payment_detail',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'admin_fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
            'payment_detail' => 'array',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return WithdrawalStatus::tryFrom($this->status)?->label() ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return WithdrawalStatus::tryFrom($this->status)?->color() ?? 'gray';
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeNeedManualApproval($query)
    {
        return $query->where('status', 'pending')
                     ->where('amount', '>=', 5000000);
    }
}

// End of file