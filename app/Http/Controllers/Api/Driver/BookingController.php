<?php
// File: app/Http/Controllers/Api/Driver/BookingController.php
// Deskripsi: API Controller untuk driver - Jemput, Antar, Selesai

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPassenger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Driver klik JEMPUT untuk satu booking
     */
    public function pickupBooking(Booking $booking): JsonResponse
    {
        $driver = request()->user();

        if ($booking->schedule->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak bertugas di jadwal ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        if (!$booking->schedule->started_at) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal belum dimulai oleh agency.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('picked_up_at')
            ->update(['picked_up_at' => now()]);

        if ($booking->status === 'paid') {
            $booking->update(['status' => 'on_going']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Penumpang berhasil dijemput!',
            'data' => ['status' => $booking->fresh()->status],
            'meta' => null,
        ]);
    }

    /**
     * Driver klik ANTAR untuk satu booking
     */
    public function dropoffBooking(Booking $booking): JsonResponse
    {
        $driver = request()->user();

        if ($booking->schedule->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak bertugas di jadwal ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('dropped_off_at')
            ->update(['dropped_off_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Penumpang berhasil diturunkan!',
            'data' => null,
            'meta' => null,
        ]);
    }

    /**
     * Driver klik SELESAI untuk satu booking
     */
    public function completeBooking(Booking $booking): JsonResponse
    {
        $driver = request()->user();

        if ($booking->schedule->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak bertugas di jadwal ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        $allDroppedOff = BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('dropped_off_at')->doesntExist();

        if (!$allDroppedOff) {
            return response()->json([
                'success' => false,
                'message' => 'Semua penumpang harus diturunkan terlebih dahulu.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        $booking->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        app(\App\Services\WalletService::class)->releaseFunds($booking);
        $booking->schedule->agency->increment('total_bookings');

        return response()->json([
            'success' => true,
            'message' => 'Perjalanan selesai!',
            'data' => ['status' => 'completed'],
            'meta' => null,
        ]);
    }
}

// End of file