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

        // ⚡ VALIDASI: Driver harus punya agency
        if (!$driver->agency_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak terdaftar di agency manapun.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        // ⚡ VALIDASI: Booking harus milik schedule yang di-drive oleh driver ini
        $schedule = $booking->schedule;

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak memiliki jadwal terkait.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        // ⚡ VALIDASI: Driver harus bertugas di schedule ini
        if ($schedule->driver_id !== $driver->id) {
            Log::warning('Driver attempted to access another driver booking', [
                'driver_id' => $driver->id,
                'booking_id' => $booking->id,
                'schedule_driver_id' => $schedule->driver_id,
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Anda tidak bertugas di jadwal ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        // ⚡ VALIDASI: Schedule harus aktif
        if (!$schedule->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal sudah tidak aktif.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        // ⚡ VALIDASI: Schedule harus sudah dimulai
        if (!$schedule->started_at) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal belum dimulai oleh agency.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        // ⚡ VALIDASI: Schedule belum selesai
        if ($schedule->finished_at) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal sudah selesai.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        // ⚡ VALIDASI: Booking tidak boleh cancelled
        if ($booking->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Booking sudah dibatalkan.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        // ⚡ VALIDASI: Booking harus dalam status yang valid untuk pickup
        $validStatuses = ['paid', 'confirmed', 'on_going'];
        if (!in_array($booking->status, $validStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak dalam status yang bisa dijemput. Status saat ini: ' . $booking->status_label,
                'data' => null,
                'meta' => null,
            ], 400);
        }

        // Proses pickup
        BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('picked_up_at')
            ->update(['picked_up_at' => now()]);

        // Update booking status ke on_going jika masih paid
        if (in_array($booking->status, ['paid', 'confirmed'])) {
            $booking->update(['status' => 'on_going']);
        }

        Log::info('Driver picked up booking', [
            'driver_id' => $driver->id,
            'booking_id' => $booking->id,
            'booking_code' => $booking->booking_code,
        ]);

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

        // ⚡ VALIDASI: Driver harus punya agency
        if (!$driver->agency_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak terdaftar di agency manapun.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        // ⚡ VALIDASI: Booking harus milik schedule yang di-drive oleh driver ini
        $schedule = $booking->schedule;

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak memiliki jadwal terkait.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        if ($schedule->driver_id !== $driver->id) {
            Log::warning('Driver attempted to dropoff another driver booking', [
                'driver_id' => $driver->id,
                'booking_id' => $booking->id,
                'schedule_driver_id' => $schedule->driver_id,
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Anda tidak bertugas di jadwal ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        // ⚡ VALIDASI: Schedule harus aktif dan sudah dimulai
        if (!$schedule->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal sudah tidak aktif.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        if (!$schedule->started_at) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal belum dimulai oleh agency.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        if ($schedule->finished_at) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal sudah selesai.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        // ⚡ VALIDASI: Semua penumpang harus sudah dijemput
        $allPickedUp = BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('picked_up_at')
            ->doesntExist();

        if (!$allPickedUp) {
            return response()->json([
                'success' => false,
                'message' => 'Semua penumpang harus dijemput terlebih dahulu.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        // Proses dropoff
        BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('dropped_off_at')
            ->update(['dropped_off_at' => now()]);

        Log::info('Driver dropped off booking', [
            'driver_id' => $driver->id,
            'booking_id' => $booking->id,
            'booking_code' => $booking->booking_code,
        ]);

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

        // ⚡ VALIDASI: Driver harus punya agency
        if (!$driver->agency_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak terdaftar di agency manapun.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        // ⚡ VALIDASI: Booking harus milik schedule driver ini
        $schedule = $booking->schedule;

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak memiliki jadwal terkait.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        if ($schedule->driver_id !== $driver->id) {
            Log::warning('Driver attempted to complete another driver booking', [
                'driver_id' => $driver->id,
                'booking_id' => $booking->id,
                'schedule_driver_id' => $schedule->driver_id,
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Anda tidak bertugas di jadwal ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        // ⚡ VALIDASI: Booking tidak boleh cancelled
        if ($booking->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Booking sudah dibatalkan.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        // ⚡ VALIDASI: Booking tidak boleh sudah completed
        if ($booking->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Booking sudah selesai.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        // ⚡ VALIDASI: Semua penumpang harus sudah diturunkan
        $allDroppedOff = BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('dropped_off_at')
            ->doesntExist();

        if (!$allDroppedOff) {
            return response()->json([
                'success' => false,
                'message' => 'Semua penumpang harus diturunkan terlebih dahulu.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        // ⚡ VALIDASI: Jika COD, pastikan sudah dikonfirmasi
        if ($booking->payment && $booking->payment->payment_type === 'cod') {
            if ($booking->payment->status !== 'cod_confirmed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembayaran COD harus dikonfirmasi terlebih dahulu.',
                    'data' => null,
                    'meta' => null,
                ], 400);
            }
        }

        $booking->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        app(\App\Services\WalletService::class)->releaseFunds($booking);
        $booking->schedule->agency->increment('total_bookings');

        Log::info('Driver completed booking', [
            'driver_id' => $driver->id,
            'booking_id' => $booking->id,
            'booking_code' => $booking->booking_code,
        ]);

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