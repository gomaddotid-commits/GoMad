<?php
// File: app/Http/Controllers/Web/Driver/BookingController.php
// Deskripsi: Web Controller untuk driver - Jemput, Antar, Selesai per Booking

namespace App\Http\Controllers\Web\Driver;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPassenger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Halaman daftar penumpang
     */
    public function index(): View
    {
        $schedule = \App\Models\Schedule::with([
            'bookings' => function($q) {
                $q->whereNotIn('status', ['cancelled'])
                    ->with(['originStop', 'destinationStop', 'passengers', 'customer']);
            },
            'route.stops',
            'vehicle'
        ])
        ->where('driver_id', auth()->id())
        ->where('departure_date', now()->toDateString())
        ->where('is_active', true)
        ->first();

        return view('driver.booking.index', compact('schedule'));
    }

    /**
     * Driver klik JEMPUT untuk satu booking
     */
    public function pickupBooking(Booking $booking): RedirectResponse
    {
        $driver = auth()->user();

        if ($booking->schedule->driver_id !== $driver->id) {
            return back()->with('error', 'Anda tidak bertugas di jadwal ini.');
        }

        if (!$booking->schedule->started_at) {
            return back()->with('error', 'Jadwal belum dimulai oleh agency.');
        }

        // Jemput semua penumpang dalam booking ini
        BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('picked_up_at')
            ->update(['picked_up_at' => now()]);

        // Update status booking
        if ($booking->status === 'paid') {
            $booking->update(['status' => 'on_going']);
        }

        // Log ke agency
        if ($booking->schedule->agency && $booking->schedule->agency->user) {
            \App\Models\Notification::create([
                'user_id' => $booking->schedule->agency->user_id,
                'title' => '✅ Penumpang Dijemput',
                'body' => "Booking {$booking->booking_code}: {$booking->customer->name} telah dijemput oleh driver.",
                'data' => json_encode(['booking_id' => $booking->id, 'type' => 'pickup']),
            ]);
        }

        return back()->with('success', 'Penumpang berhasil dijemput!');
    }

    /**
     * Driver klik ANTAR/TURUNKAN untuk satu booking
     */
    public function dropoffBooking(Booking $booking): RedirectResponse
    {
        $driver = auth()->user();

        if ($booking->schedule->driver_id !== $driver->id) {
            return back()->with('error', 'Anda tidak bertugas di jadwal ini.');
        }

        // Turunkan semua penumpang dalam booking ini
        BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('dropped_off_at')
            ->update(['dropped_off_at' => now()]);

        // Log ke agency
        if ($booking->schedule->agency && $booking->schedule->agency->user) {
            \App\Models\Notification::create([
                'user_id' => $booking->schedule->agency->user_id,
                'title' => '🎯 Penumpang Diturunkan',
                'body' => "Booking {$booking->booking_code}: {$booking->customer->name} telah diantar ke tujuan.",
                'data' => json_encode(['booking_id' => $booking->id, 'type' => 'dropoff']),
            ]);
        }

        return back()->with('success', 'Penumpang berhasil diturunkan!');
    }

    /**
     * Driver klik SELESAI untuk satu booking
     */
    public function completeBooking(Booking $booking): RedirectResponse
    {
        $driver = auth()->user();

        if ($booking->schedule->driver_id !== $driver->id) {
            return back()->with('error', 'Anda tidak bertugas di jadwal ini.');
        }

        // Pastikan semua sudah diturunkan
        $allDroppedOff = BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('dropped_off_at')->doesntExist();

        if (!$allDroppedOff) {
            return back()->with('error', 'Semua penumpang harus diturunkan terlebih dahulu.');
        }

        $booking->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Release funds
        app(\App\Services\WalletService::class)->releaseFunds($booking);
        $booking->schedule->agency->increment('total_bookings');

        // Log ke agency
        if ($booking->schedule->agency && $booking->schedule->agency->user) {
            \App\Models\Notification::create([
                'user_id' => $booking->schedule->agency->user_id,
                'title' => '🎉 Perjalanan Selesai',
                'body' => "Booking {$booking->booking_code} telah selesai.",
                'data' => json_encode(['booking_id' => $booking->id, 'type' => 'complete']),
            ]);
        }

        // Cek apakah semua booking di jadwal ini selesai
        $allCompleted = Booking::where('schedule_id', $booking->schedule_id)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->doesntExist();

        if ($allCompleted) {
            $booking->schedule->update(['finished_at' => now()]);
        }

        return back()->with('success', 'Perjalanan selesai!');
    }

    /**
     * Driver konfirmasi pembayaran COD
     */
    public function confirmCod(Booking $booking): RedirectResponse
    {
        $driver = auth()->user();

        if ($booking->schedule->driver_id !== $driver->id) {
            return back()->with('error', 'Anda tidak bertugas di jadwal ini.');
        }

        if (!$booking->payment || $booking->payment->payment_type !== 'cod') {
            return back()->with('error', 'Bukan pembayaran COD.');
        }

        if ($booking->payment->status !== 'cod_pending') {
            return back()->with('error', 'Pembayaran sudah dikonfirmasi.');
        }

        // Release saldo COD
        app(\App\Services\WalletService::class)->releaseCodBalance($booking);

        // Update payment status
        $booking->payment->update([
            'status' => \App\Enums\PaymentStatus::COD_CONFIRMED->value,
            'paid_at' => now(),
        ]);

        // 👇 PASTIKAN booking jadi paid
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
                'data' => json_encode(['booking_id' => $booking->id, 'type' => 'cod_confirmed']),
            ]);
        }

        return back()->with('success', 'Pembayaran COD berhasil dikonfirmasi!');
    }
}

// End of file