<?php
// File: app/Models/Payment.php
// Deskripsi: Payment model untuk transaksi pembayaran

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Enums\PaymentStatus;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'cash_payment_id',
        'amount',
        'commission',
        'agency_revenue',
        'payment_type',
        'status',
        'payment_method',
        'transaction_id',
        'payment_channel',
        'paid_at',
        'expired_at',
        'payment_detail',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'commission' => 'decimal:2',
            'agency_revenue' => 'decimal:2',
            'paid_at' => 'datetime',
            'expired_at' => 'datetime',
            'payment_detail' => 'array',
        ];
    }

    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->status === 'paid') {
                    return false;
                }
                if ($this->expired_at) {
                    return now()->greaterThan($this->expired_at);
                }
                return false;
            },
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => PaymentStatus::tryFrom($this->status)?->label() ?? $this->status,
        );
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function cashPayment(): BelongsTo
    {
        return $this->belongsTo(CashPayment::class);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('payment_type', $type);
    }
}

// End of file