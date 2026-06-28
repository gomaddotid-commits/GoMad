<?php
// File: app/Models/PaymentAgent.php
// Deskripsi: PaymentAgent model untuk Warung GoMad

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class PaymentAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'agent_name',
        'owner_name',
        'owner_phone',
        'guard_name',
        'guard_phone',
        'address',
        'kecamatan',
        'maps_link',
        'latitude',
        'longitude',
        'photo_warung',
        'photo_ktp_owner',
        'photo_ktp_guard',
        'pin',
        'is_active',
        'is_verified',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'commission_rate',
        'total_transactions',
        'total_commission',
        'balance_to_settle',
        'last_settlement_at',
    ];

    protected $hidden = [
        'pin',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
            'is_verified' => 'boolean',
            'commission_rate' => 'decimal:2',
            'total_transactions' => 'integer',
            'total_commission' => 'decimal:2',
            'balance_to_settle' => 'decimal:2',
            'last_settlement_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function cashPayments(): HasMany
    {
        return $this->hasMany(CashPayment::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeByKecamatan(Builder $query, string $kecamatan): Builder
    {
        return $query->where('kecamatan', $kecamatan);
    }
}

// End of file