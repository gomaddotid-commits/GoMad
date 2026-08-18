<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosTransaction extends Model
{
    protected $table = 'pos_transactions';

    protected $fillable = [
        'payment_agent_id', 'invoice_no', 'type',
        'subtotal', 'discount', 'total', 'paid', 'change',
        'payment_method', 'customer_name', 'notes', 'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid' => 'decimal:2',
        'change' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (PosTransaction $transaction) {
            if (empty($transaction->invoice_no)) {
                $transaction->invoice_no = 'INV-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            }
        });
    }

    public function paymentAgent(): BelongsTo
    {
        return $this->belongsTo(PaymentAgent::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosTransactionItem::class, 'transaction_id');
    }
}
