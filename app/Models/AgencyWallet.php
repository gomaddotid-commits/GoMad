<?php
// File: app/Models/AgencyWallet.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgencyWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'available_balance',
        'pending_balance',
        'deposit_balance',      // 👈 PASTIKAN INI ADA
        'cod_hold_balance',     // 👈 PASTIKAN INI ADA
        'total_earned',
        'total_withdrawn',
    ];

    protected function casts(): array
    {
        return [
            'available_balance' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'deposit_balance' => 'decimal:2',     // 👈 PASTIKAN INI ADA
            'cod_hold_balance' => 'decimal:2',     // 👈 PASTIKAN INI ADA
            'total_earned' => 'decimal:2',
            'total_withdrawn' => 'decimal:2',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'agency_id', 'agency_id');
    }

    public function getTotalBalanceAttribute(): float
    {
        return (float) $this->available_balance + (float) $this->pending_balance;
    }
}

// End of file