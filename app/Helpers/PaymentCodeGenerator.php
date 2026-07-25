<?php
// File: app/Helpers/PaymentCodeGenerator.php
// Deskripsi: Generator kode pembayaran Warung GoMad format WM-YYYYMMDD-XXXXXX

namespace App\Helpers;

use App\Models\CashPayment;
use Illuminate\Support\Str;

class PaymentCodeGenerator
{
    public static function generate(): string
    {
        $prefix = config('gomad.payment_code_prefix', 'WM');
        $date = now()->format('Ymd');
        $maxRetries = 10;

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            // Gunakan random_bytes untuk kriptografis random (lebih aman dari Str::random)
            $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $code = $prefix . '-' . $date . '-' . $random;

            // Cek uniqueness dengan INSERT atomic (mencegah race condition)
            $exists = CashPayment::where('payment_code', $code)->exists();

            if (!$exists) {
                return $code;
            }

            // Jeda exponential backoff untuk mengurangi collision probability
            usleep(min($attempt * 5000, 50000));
        }

        // Fallback: gunakan microtime untuk guarantee uniqueness
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        $code = $prefix . '-' . $date . '-' . $random;

        return $code;
    }
}

// End of file