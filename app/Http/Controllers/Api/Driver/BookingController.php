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

    /**
     * Driver konfirmasi pembayaran COD (API)
     */
    public function confirmCod(Booking $booking): JsonResponse
    {
        $driver = request()->user();

        // Validasi: driver harus bertugas di jadwal ini
        if ($booking->schedule->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak bertugas di jadwal ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        // Validasi: jadwal harus sudah dimulai
        if (!$booking->schedule->started_at) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal belum dimulai oleh agency.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        // Validasi: harus pembayaran COD
        if (!$booking->payment || $booking->payment->payment_type !== 'cod') {
            return response()->json([
                'success' => false,
                'message' => 'Bukan pembayaran COD.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        // Validasi: status pembayaran harus cod_pending
        if ($booking->payment->status !== 'cod_pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran COD sudah dikonfirmasi sebelumnya.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        // Validasi: semua penumpang harus sudah diturunkan
        $allDroppedOff = \App\Models\BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('dropped_off_at')
            ->doesntExist();

        if (!$allDroppedOff) {
            return response()->json([
                'success' => false,
                'message' => 'Semua penumpang harus diantar ke tujuan terlebih dahulu sebelum konfirmasi COD.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($booking, $driver) {
                // Release saldo COD
                app(\App\Services\WalletService::class)->releaseCodBalance($booking);

                // Update payment status
                $booking->payment->update([
                    'status' => \App\Enums\PaymentStatus::COD_CONFIRMED->value,
                    'paid_at' => now(),
                ]);

                // Update booking status
                $booking->update(['status' => \App\Enums\BookingStatus::PAID->value]);

                // Tandai semua penumpang sudah bayar COD
                \App\Models\BookingPassenger::where('booking_id', $booking->id)
                    ->update([
                        'cod_paid' => true,
                        'cod_paid_at' => now(),
                        'cod_confirmed_by' => $driver->id,
                    ]);

                // Notifikasi ke agency
                if ($booking->schedule->agency && $booking->schedule->agency->user) {
                    \App\Models\Notification::create([
                        'user_id' => $booking->schedule->agency->user_id,
                        'title' => '💰 Pembayaran COD Dikonfirmasi',
                        'body' => "Booking {$booking->booking_code}: Pembayaran COD Rp " . number_format($booking->total_price, 0, ',', '.') . " telah diterima oleh driver.",
                        'data' => json_encode([
                            'booking_id' => $booking->id,
                            'type' => 'cod_confirmed',
                        ]),
                    ]);
                }

                // Notifikasi ke customer
                if ($booking->customer && $booking->customer->phone) {
                    app(\App\Services\NotificationService::class)->sendWhatsApp(
                        $booking->customer->phone,
                        "✅ Pembayaran COD untuk booking *{$booking->booking_code}* telah dikonfirmasi oleh sopir.\n\n" .
                        "Total: Rp " . number_format($booking->total_price, 0, ',', '.') . "\n" .
                        "E-Ticket sekarang dapat diakses di aplikasi GoMad."
                    );
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran COD berhasil dikonfirmasi.',
                'data' => [
                    'booking_id' => $booking->id,
                    'booking_code' => $booking->booking_code,
                    'booking_status' => $booking->fresh()->status,
                    'payment_status' => $booking->payment->fresh()->status,
                    'confirmed_at' => now()->format('Y-m-d H:i:s'),
                ],
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            \Log::error('COD confirmation error: ' . $e->getMessage(), [
                'booking_id' => $booking->id,
                'driver_id' => $driver->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengkonfirmasi pembayaran COD: ' . $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 500);
        }
    }
}

// End of file