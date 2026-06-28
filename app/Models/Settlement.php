<?php
// File: app/Models/Settlement.php
// Deskripsi: Settlement model untuk tagihan settlement warung

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\SettlementStatus;

class Settlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_agent_id',
        'period_start',
        'period_end',
        'total_transactions',
        'total_amount',
        'total_commission',
        'amount_to_settle',
        'status',
        'payment_method',
        'transaction_id',
        'payment_detail',
        'paid_at',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_transactions' => 'integer',
            'total_amount' => 'decimal:2',
            'total_commission' => 'decimal:2',
            'amount_to_settle' => 'decimal:2',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
            'payment_detail' => 'array',
        ];
    }

    public function paymentAgent(): BelongsTo
    {
        return $this->belongsTo(PaymentAgent::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function cashPayments(): HasMany
    {
        return $this->hasMany(CashPayment::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return SettlementStatus::tryFrom($this->status)?->label() ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return SettlementStatus::tryFrom($this->status)?->color() ?? 'gray';
    }

    public function scopeByPaymentAgent($query, int $agentId)
    {
        return $query->where('payment_agent_id', $agentId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPeriod($query, string $start, string $end)
    {
        return $query->where('period_start', $start)
                     ->where('period_end', $end);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'overdue']);
    }
}

// End of file