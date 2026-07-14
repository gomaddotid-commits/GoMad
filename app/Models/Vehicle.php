<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'plate_number',
        'brand',
        'model',
        'year',
        'capacity',
        'type',
        'vehicle_image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'capacity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    // 👇 TAMBAHKAN RELASI INI
    public function rentalSetting(): HasOne
    {
        return $this->hasOne(VehicleRentalSetting::class);
    }

    // 👇 TAMBAHKAN RELASI INI
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    // 👇 TAMBAHKAN RELASI INI
    public function rentalPromos(): BelongsToMany
    {
        return $this->belongsToMany(Promo::class, 'promo_rental_vehicle')->withTimestamps();
    }

    // 👇 TAMBAHKAN HELPER
    public function isAvailableForRental(): bool
    {
        return $this->rentalSetting && $this->rentalSetting->is_available_for_rental;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByAgency(Builder $query, int $agencyId): Builder
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeAvailable(Builder $query, string $date): Builder
    {
        return $query->whereDoesntHave('schedules', function (Builder $q) use ($date) {
            $q->where('departure_date', $date)->where('is_active', true);
        });
    }
}