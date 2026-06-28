<?php
// File: app/Models/PromoUsage.php
// Deskripsi: Model untuk tracking penggunaan promo

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'promo_id', 'user_id', 'booking_id', 'discount_amount', 'promo_code',
    ];

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}

// End of file