<?php
// File: app/Models/ReferralCode.php
// Deskripsi: Model untuk kode referral customer

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralCode extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'code', 'total_referred', 'successful_referrals'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateCode(string $name): string
    {
        $base = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 4));
        $random = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
        $code = $base . $random;
        
        // Ensure uniqueness
        while (self::where('code', $code)->exists()) {
            $random = str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
            $code = $base . $random;
        }
        
        return $code;
    }
}

// End of file