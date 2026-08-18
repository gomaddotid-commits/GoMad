<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyPolicy extends Model
{
    protected $table = 'agency_policies';

    protected $fillable = [
        'agency_id',
        'allow_cod_without_deposit',
        'cod_min_balance',
        'cod_daily_limit',
        'cod_max_per_booking',
        'allow_ots',
        'ots_deposit_required',
        'commission_override',
        'settlement_schedule',
        'credit_limit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'allow_cod_without_deposit' => 'boolean',
            'cod_min_balance' => 'decimal:2',
            'cod_daily_limit' => 'decimal:2',
            'cod_max_per_booking' => 'decimal:2',
            'allow_ots' => 'boolean',
            'ots_deposit_required' => 'boolean',
            'commission_override' => 'decimal:2',
            'credit_limit' => 'decimal:2',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    // Cek apakah agency boleh COD
    public function canUseCod(float $depositBalance = 0): bool
    {
        // Jika agensi diizinkan COD tanpa deposit → boleh
        if ($this->allow_cod_without_deposit) return true;

        // Jika cod_min_balance di-set (>0), cek saldo deposit
        if ($this->cod_min_balance > 0) {
            return $depositBalance >= $this->cod_min_balance;
        }

        // Default: pakai deposit sebagai syarat
        return $depositBalance > 0;
    }

    // Cek apakah agency boleh OTS
    public function canUseOts(): bool
    {
        return $this->allow_ots;
    }

    // Komisi efektif (override atau null = ikut global)
    public function effectiveCommission(?float $globalCommission): ?float
    {
        return $this->commission_override ?? $globalCommission;
    }
}
