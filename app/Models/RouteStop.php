<?php
// File: app/Models/RouteStop.php
// Deskripsi: RouteStop model untuk titik pemberhentian dalam rute

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RouteStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'city_name',
        'stop_order',
        'latitude',
        'longitude',
        'distance_from_origin',
    ];

    protected function casts(): array
    {
        return [
            'stop_order' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'distance_from_origin' => 'decimal:2',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function scheduleStops(): HasMany
    {
        return $this->hasMany(ScheduleStop::class);
    }

    public function originPricing(): HasMany
    {
        return $this->hasMany(RoutePricing::class, 'origin_stop_id');
    }

    public function destinationPricing(): HasMany
    {
        return $this->hasMany(RoutePricing::class, 'destination_stop_id');
    }

    public function originBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'origin_stop_id');
    }

    public function destinationBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'destination_stop_id');
    }
}

// End of file