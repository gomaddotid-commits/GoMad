<?php
// File: app/Models/ScheduleStop.php
// Deskripsi: ScheduleStop model untuk konfigurasi stop per schedule

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleStop extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'route_stop_id',
        'is_pickup_available',
        'is_dropoff_available',
        'estimated_time',
    ];

    protected function casts(): array
    {
        return [
            'is_pickup_available' => 'boolean',
            'is_dropoff_available' => 'boolean',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function routeStop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class);
    }
}

// End of file