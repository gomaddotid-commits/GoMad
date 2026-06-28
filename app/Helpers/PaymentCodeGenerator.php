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
        
        $random = strtoupper(Str::random(6));
        
        $code = $prefix . '-' . $date . '-' . $random;
        
        // Ensure uniqueness
        while (CashPayment::where('payment_code', $code)->exists()) {
            $random = strtoupper(Str::random(6));
            $code = $prefix . '-' . $date . '-' . $random;
        }
        
        return $code;
    }
}

// End of file