<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_name',
        'origin_city_code',       // FK ke indonesia_cities
        'destination_city_code',  // FK ke indonesia_cities
        'distance_km',
        'estimated_duration',
        'max_price',
        'cod_available',
        'cod_min_deposit',
        'payment_methods',
        'description',
        'photo',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'estimated_duration' => 'integer',
            'max_price' => 'decimal:2',
            'cod_available' => 'boolean',
            'cod_min_deposit' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ═══════════════════════════════════════
    // RELASI KE LARAVOLT
    // ═══════════════════════════════════════

    public function originCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'origin_city_code', 'code');
    }

    public function destinationCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'destination_city_code', 'code');
    }

    // ═══════════════════════════════════════
    // RELASI KE TABEL LAIN
    // ═══════════════════════════════════════

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class)->orderBy('stop_order');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    // ═══════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════

    public function getOriginCityNameAttribute(): string
    {
        return $this->originCity?->name ?? 'Unknown';
    }

    public function getDestinationCityNameAttribute(): string
    {
        return $this->destinationCity?->name ?? 'Unknown';
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) return null;
        if (str_starts_with($this->photo, 'http')) return $this->photo;
        return asset('storage/' . $this->photo);
    }

    // ═══════════════════════════════════════
    // PAYMENT METHODS
    // ═══════════════════════════════════════

    public function getPaymentMethodsArrayAttribute(): array
    {
        $value = $this->attributes['payment_methods'] ?? null;
        if (empty($value)) return ['midtrans', 'cash', 'cod'];
        if (is_array($value)) return $value;
        return explode(',', $value);
    }

    public function isPaymentMethodAvailable(string $method): bool
    {
        return in_array($method, $this->payment_methods_array);
    }

    public function setPaymentMethodsAttribute($value): void
    {
        if (is_array($value)) {
            $value = array_filter($value);
            $this->attributes['payment_methods'] = !empty($value) ? implode(',', $value) : null;
        } elseif (is_string($value)) {
            $this->attributes['payment_methods'] = !empty($value) ? $value : null;
        } else {
            $this->attributes['payment_methods'] = null;
        }
    }

    // ═══════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByOriginCity(Builder $query, string $cityCode): Builder
    {
        return $query->where('origin_city_code', $cityCode);
    }

    public function scopeByDestinationCity(Builder $query, string $cityCode): Builder
    {
        return $query->where('destination_city_code', $cityCode);
    }
}