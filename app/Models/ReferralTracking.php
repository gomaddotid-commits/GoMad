<?php
// File: app/Models/ReferralTracking.php
// Deskripsi: Model untuk tracking referral

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id', 'referred_user_id', 'referral_code',
        'is_successful', 'successful_at',
    ];

    protected function casts(): array
    {
        return [
            'is_successful' => 'boolean',
            'successful_at' => 'datetime',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}

// End of file