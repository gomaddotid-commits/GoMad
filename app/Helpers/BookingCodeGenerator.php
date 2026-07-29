<?php

namespace App\Helpers;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class BookingCodeGenerator
{
    /**
     * Generate kode booking format GM-YYYYMMDD-XXXX (THREAD-SAFE)
     * 
     * @param int $scheduleId - Tidak digunakan lagi untuk locking, 
     *                          tapi dipertahankan untuk backward compatibility
     */
    public static function generate(int $scheduleId): string
    {
        $prefix = config('gomad.booking_code_prefix', 'GM');
        $date = now()->format('Ymd');
        
        return DB::transaction(function () use ($prefix, $date) {
            // 🔒 PESSIMISTIC LOCK pada tabel bookings untuk mencegah race condition
            // Gunakan SELECT ... FOR UPDATE pada level tabel
            $lastBooking = Booking::whereDate('created_at', now()->toDateString())
                ->where('booking_code', 'like', $prefix . '-' . $date . '-%')
                ->lockForUpdate()  // ✅ Lock semua booking hari ini
                ->orderBy('booking_code', 'desc')
                ->first();
            
            // Hitung counter
            if ($lastBooking) {
                $parts = explode('-', $lastBooking->booking_code);
                $lastNumber = (int) end($parts);
                $counter = $lastNumber + 1;
            } else {
                $counter = 1;
            }
            
            // Generate code
            $code = $prefix . '-' . $date . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
            
            // ✅ DOUBLE-CHECK dengan re-query dalam lock yang sama
            $attempts = 0;
            while (Booking::where('booking_code', $code)->exists() && $attempts < 100) {
                $counter++;
                $code = $prefix . '-' . $date . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
                $attempts++;
            }
            
            if ($attempts >= 100) {
                throw new \RuntimeException(
                    'Unable to generate unique booking code after 100 attempts'
                );
            }
            
            return $code;
        });
    }
}