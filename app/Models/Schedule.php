<?php
// File: app/Models/Schedule.php
// Deskripsi: Schedule model untuk jadwal perjalanan

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Promo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'vehicle_id',
        'route_id',
        'driver_id',
        'departure_date',
        'departure_time',
        'travel_class',
        'max_overload',
        'price_per_seat',
        'baggage_limit_kg',
        'is_active',
        'allow_passenger_transfer',
        'accept_external_transfer',
        'transfer_fee_per_passenger',
        'max_transfer_fee_percent',
        'allow_cod',
        'cod_min_balance',
        'started_at',
        'finished_at'
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'departure_time' => 'string',
            'max_overload' => 'integer',
            'price_per_seat' => 'decimal:2',
            'baggage_limit_kg' => 'decimal:2',
            'is_active' => 'boolean',
            'allow_passenger_transfer' => 'boolean',
            'accept_external_transfer' => 'boolean',
            'transfer_fee_per_passenger' => 'decimal:2',
            'max_transfer_fee_percent' => 'decimal:2',
            'allow_cod' => 'boolean',
            'cod_min_balance' => 'decimal:2',
            'transferred_out_count' => 'integer',
            'transferred_in_count' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    protected function maxCapacity(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->travel_class === 'economy') {
                    return ($this->vehicle->capacity ?? 8) + $this->max_overload;
                }
                return $this->vehicle->capacity ?? 8;
            },
        );
    }

    protected function availableSeats(): Attribute
    {
        return Attribute::make(
            get: function () {
                $bookedSeats = $this->bookings()
                    ->whereNotIn('status', ['cancelled'])
                    ->sum('total_passengers');
                return max(0, $this->max_capacity - $bookedSeats);
            },
        );
    }

    protected function isFull(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->available_seats <= 0,
        );
    }

    protected function occupancyRate(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->max_capacity <= 0) {
                    return 0;
                }
                $bookedSeats = $this->bookings()
                    ->whereNotIn('status', ['cancelled'])
                    ->sum('total_passengers');
                return round(($bookedSeats / $this->max_capacity) * 100, 2);
            },
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

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function scheduleStops(): HasMany
    {
        return $this->hasMany(ScheduleStop::class);
    }

    public function routePricing(): HasMany
    {
        return $this->hasMany(RoutePricing::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function driverLocations(): HasMany
    {
        return $this->hasMany(DriverLocation::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByDate(Builder $query, string $date): Builder
    {
        return $query->where('departure_date', $date);
    }

    public function scopeByRoute(Builder $query, int $routeId): Builder
    {
        return $query->where('route_id', $routeId);
    }

    public function scopeByClass(Builder $query, string $travelClass): Builder
    {
        return $query->where('travel_class', $travelClass);
    }

    public function scopeByAgency(Builder $query, int $agencyId): Builder
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeByDriver(Builder $query, int $driverId): Builder
    {
        return $query->where('driver_id', $driverId);
    }

    // Relationships transfer penumpang:
    public function transfersOut(): HasMany
    {
        return $this->hasMany(PassengerTransfer::class, 'from_schedule_id');
    }

    public function transfersIn(): HasMany
    {
        return $this->hasMany(PassengerTransfer::class, 'to_schedule_id');
    }

    public function promos(): BelongsToMany
    {
        return $this->belongsToMany(Promo::class, 'promo_schedule')->withTimestamps();
    }

    public function passengerTransfersOut(): HasMany
    {
        return $this->hasMany(PassengerTransfer::class, 'from_schedule_id');
    }

    public function passengerTransfersIn(): HasMany
    {
        return $this->hasMany(PassengerTransfer::class, 'to_schedule_id');
    }
}

// End of file