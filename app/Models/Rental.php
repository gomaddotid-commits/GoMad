<?php

namespace App\Models;

use App\Enums\RentalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Rental extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'rental_code',
        'agency_id', 'vehicle_id', 'customer_id',
        'type',
        'start_datetime', 'end_datetime', 'duration', 'duration_unit',
        'price_per_unit', 'driver_fee_per_unit',
        'subtotal', 'platform_fee', 'deposit_amount', 'total_price',
        'discount_amount', 'promo_id',  // 👈 PASTIKAN INI ADA
        'status',
        'payment_id',
        'notes',
        'started_at', 'returned_at', 'cancelled_at',
        'pickup_address',
        'destination_address',
        'pickup_maps_link',
        'destination_maps_link',
        'driver_id',
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'duration' => 'integer',
            'price_per_unit' => 'decimal:2',
            'driver_fee_per_unit' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'total_price' => 'decimal:2',
            'started_at' => 'datetime',
            'returned_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => RentalStatus::tryFrom($this->status)?->label() ?? $this->status,
        );
    }

    protected function statusColor(): Attribute
    {
        return Attribute::make(
            get: fn () => RentalStatus::tryFrom($this->status)?->color() ?? 'gray',
        );
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}