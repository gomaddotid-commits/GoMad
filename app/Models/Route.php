<?php
// File: app/Models/Route.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Route extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_name',
        'origin_city',
        'destination_city',
        'distance_km',
        'estimated_duration',
        'payment_methods',    // 👈 TAMBAHKAN
        'max_price',        // 👈 Tambahkan
        'cod_min_deposit',  // 👈 Tambahkan
        'cod_available',    // 👈 Tambahkan
        'description',   // 👈 Tambahkan
        'photo',         // 👈 Tambahkan
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'estimated_duration' => 'integer',
            'max_price' => 'decimal:2',
            'cod_min_deposit' => 'decimal:2',
            'cod_available' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Accessor untuk URL foto
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) return null;
        
        // Cloudinary URL
        if (str_starts_with($this->photo, 'http')) {
            return $this->photo;
        }
        
        // Local storage fallback
        return asset('storage/' . $this->photo);
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class)->orderBy('stop_order');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCities(Builder $query, string $origin, string $destination): Builder
    {
        return $query->where(function (Builder $q) use ($origin, $destination) {
            $q->where('origin_city', $origin)
              ->where('destination_city', $destination);
        });
    }

    // 👇 TAMBAHKAN ACCESSOR
    public function getPaymentMethodsArrayAttribute(): array
    {
        $value = $this->attributes['payment_methods'] ?? null;
        
        if (empty($value)) {
            return ['midtrans', 'cash', 'cod']; // Default: semua tersedia
        }
        
        if (is_array($value)) {
            return $value;
        }
        
        return explode(',', $value);
    }

    // 👇 TAMBAHKAN METHOD UNTUK CEK
    public function isPaymentMethodAvailable(string $method): bool
    {
        return in_array($method, $this->payment_methods_array);
    }

    // 👇 TAMBAHKAN MUTATOR
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

}

// End of file