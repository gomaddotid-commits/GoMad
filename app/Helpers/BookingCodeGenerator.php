<?php
// File: app/Helpers/BookingCodeGenerator.php
// Deskripsi: Generator kode booking format GM-YYYYMMDD-XXXX

namespace App\Helpers;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class BookingCodeGenerator
{
    public static function generate(int $scheduleId): string
    {
        $prefix = config('gomad.booking_code_prefix', 'GM');
        $date = now()->format('Ymd');
        
        // Get counter for today
        $todayBookingsCount = Booking::whereDate('created_at', now()->toDateString())->count();
        $counter = $todayBookingsCount + 1;
        
        $code = $prefix . '-' . $date . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
        
        // Ensure uniqueness (in case of race conditions)
        while (Booking::where('booking_code', $code)->exists()) {
            $counter++;
            $code = $prefix . '-' . $date . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
        }
        
        return $code;
    }
}

// End of file