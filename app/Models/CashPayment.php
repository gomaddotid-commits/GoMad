<?php
// File: app/Models/CashPayment.php
// Deskripsi: CashPayment model untuk pembayaran via Warung GoMad

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;  
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CashPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_id',
        'payment_agent_id',
        'payment_code',
        'amount',
        'agent_commission',
        'platform_commission',
        'status',
        'settlement_id',
        'confirmed_at',
        'expired_at',
        'settled_at',
        'rental_id',   
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'agent_commission' => 'decimal:2',
            'platform_commission' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'expired_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function paymentAgent(): BelongsTo
    {
        return $this->belongsTo(PaymentAgent::class);
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'cash_payment_id');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentCode($query, string $code)
    {
        return $query->where('payment_code', $code);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}

// End of file