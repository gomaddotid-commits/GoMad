<?php
// File: app/Models/RoutePricing.php
// Deskripsi: RoutePricing model untuk harga antar stop

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoutePricing extends Model
{
    use HasFactory;

    protected $table = 'route_pricing';

    protected $fillable = [
        'schedule_id',
        'origin_stop_id',
        'destination_stop_id',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function originStop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class, 'origin_stop_id');
    }

    public function destinationStop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class, 'destination_stop_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}

// End of file