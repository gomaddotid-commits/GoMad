<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleRentalSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'is_available_for_rental',
        'description',
        'specifications',
        'price_per_hour',
        'price_per_day',
        'allow_self_drive',
        'allow_with_driver',
        'driver_fee_per_hour',
        'driver_fee_per_day',
        'deposit_amount',
        'requirements',
        'photos',
        'terms_conditions',
        'refund_policy',        // 👈 TAMBAH
        'use_system_terms',     // 👈 TAMBAH
        'use_system_refund',    // 👈 TAMBAH
        'pickup_location',
        'pickup_maps_link',
        'use_agency_address',
    ];

    protected function casts(): array
    {
        return [
            'is_available_for_rental' => 'boolean',
            'allow_self_drive' => 'boolean',
            'allow_with_driver' => 'boolean',
            'price_per_hour' => 'decimal:2',
            'price_per_day' => 'decimal:2',
            'driver_fee_per_hour' => 'decimal:2',
            'driver_fee_per_day' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'specifications' => 'json',
            'requirements' => 'json',
            'photos' => 'json',
            'terms_conditions' => 'json',
            'refund_policy' => 'json',           // 👈 TAMBAH
            'use_system_terms' => 'boolean',     // 👈 TAMBAH
            'use_system_refund' => 'boolean',    // 👈 TAMBAH
            'use_agency_address' => 'boolean',

        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function getAvailableTypesAttribute(): array
    {
        $types = [];
        if ($this->allow_self_drive) $types[] = 'self_drive';
        if ($this->allow_with_driver) $types[] = 'with_driver';
        return $types;
    }

    public function requiresDocument(string $docType): bool
    {
        $requirements = $this->requirements ?? [];
        return $requirements[$docType] ?? false;
    }

    // 👇 ACCESSOR: Dapatkan alamat pengambilan
    public function getPickupAddressAttribute(): string
    {
        if ($this->use_agency_address || empty($this->pickup_location)) {
            return $this->vehicle->agency->address ?? 'Alamat belum diatur';
        }
        return $this->pickup_location;
    }

    // 👇 ACCESSOR: Dapatkan maps link pengambilan
    public function getPickupMapsUrlAttribute(): string
    {
        if ($this->use_agency_address || empty($this->pickup_maps_link)) {
            return $this->vehicle->agency->maps_link ?? 
                'https://www.google.com/maps/search/?api=1&query=' . urlencode($this->pickup_address);
        }
        return $this->pickup_maps_link;
    }

}