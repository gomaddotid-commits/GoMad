<?php
// File: app/Helpers/BookingCodeGenerator.php
// Deskripsi: Generator kode booking format GM-YYYYMMDD-XXXX (THREAD-SAFE for MySQL)

namespace App\Helpers;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class BookingCodeGenerator
{
    public static function generate(int $scheduleId): string
    {
        $prefix = config('gomad.booking_code_prefix', 'GM');
        $date = now()->format('Ymd');
        
        return DB::transaction(function () use ($prefix, $date) {
            // 🔒 PESSIMISTIC LOCK: Ambil booking TERAKHIR hari ini
            $lastBooking = Booking::whereDate('created_at', now()->toDateString())
                ->where('booking_code', 'like', $prefix . '-' . $date . '-%')
                ->lockForUpdate()
                ->orderBy('booking_code', 'desc')
                ->first();
            
            // Hitung counter dari kode terakhir (bukan dari count)
            if ($lastBooking) {
                // Ekstrak nomor dari kode terakhir: GM-20260727-0002 → 2
                $parts = explode('-', $lastBooking->booking_code);
                $lastNumber = (int) end($parts);
                $counter = $lastNumber + 1;
            } else {
                $counter = 1;
            }
            
            // Generate code dengan counter unik
            $code = $prefix . '-' . $date . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
            
            // Safety net: double-check uniqueness (harusnya tidak perlu dengan lock)
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
