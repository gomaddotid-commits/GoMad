<?php

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
        'province_code',      // FK ke indonesia_provinces
        'city_code',          // FK ke indonesia_cities
        'district_code',      // FK ke indonesia_districts
        'owner_name',
        'owner_phone',
        'guard_name',
        'guard_phone',
        'address',
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

    protected $hidden = ['pin'];

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

    // ═══════════════════════════════════════
    // RELASI KE LARAVOLT
    // ═══════════════════════════════════════

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code', 'code');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }

    // ═══════════════════════════════════════
    // RELASI KE TABEL LAIN
    // ═══════════════════════════════════════

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

    // ═══════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════

    public function getProvinceNameAttribute(): string
    {
        return $this->province?->name ?? '-';
    }

    public function getCityNameAttribute(): string
    {
        return $this->city?->name ?? '-';
    }

    public function getDistrictNameAttribute(): string
    {
        return $this->district?->name ?? '-';
    }

    public function getFullAddressAttribute(): string
    {
        $parts = [];
        if ($this->address) $parts[] = $this->address;
        if ($this->district_name !== '-') $parts[] = 'Kec. ' . $this->district_name;
        $parts[] = $this->city_name;
        $parts[] = $this->province_name;
        return implode(', ', $parts);
    }

    // ═══════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeInCity(Builder $query, string $cityCode): Builder
    {
        return $query->where('city_code', $cityCode);
    }

    public function scopeInDistrict(Builder $query, string $districtCode): Builder
    {
        return $query->where('district_code', $districtCode);
    }

    public function scopeNearby(Builder $query, float $lat, float $lng, float $radiusKm = 10): Builder
    {
        return $query->selectRaw("
            payment_agents.*,
            (6371 * acos(
                cos(radians(?)) * cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * sin(radians(latitude))
            )) AS distance
        ", [$lat, $lng, $lat])
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->having('distance', '<=', $radiusKm)
        ->orderBy('distance');
    }
}