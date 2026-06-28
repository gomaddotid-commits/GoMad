<?php
// File: app/Models/BookingPassenger.php
// Deskripsi: BookingPassenger model untuk data penumpang per booking

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPassenger extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'passenger_name',
        'passenger_phone',
        'baggage_weight',
        'seat_number',
        'picked_up_at',
        'dropped_off_at',
        'cod_paid',
        'cod_paid_at',
        'cod_confirmed_by',
    ];

    protected function casts(): array
    {
        return [
            'baggage_weight' => 'decimal:2',
            'seat_number' => 'integer',
            'picked_up_at' => 'datetime',
            'dropped_off_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}

// End of file