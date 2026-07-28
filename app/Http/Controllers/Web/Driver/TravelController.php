<?php

namespace App\Http\Controllers\Web\Driver;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\Schedule;
use App\Services\DriverService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TravelController extends Controller
{
    public function __construct(
        private readonly DriverService $driverService,
    ) {}

    /**
     * Daftar jadwal travel driver
     */
    public function index(): View
    {
        $driver = auth()->user();

        $todaySchedule = $this->driverService->getDriverTodaySchedule($driver);
        $upcomingSchedules = $this->driverService->getDriverUpcomingSchedules($driver, 30);

        // Jadwal selesai (7 hari terakhir)
        $recentCompletedSchedules = Schedule::with(['route', 'vehicle'])
            ->where('driver_id', $driver->id)
            ->whereNotNull('finished_at')
            ->orderBy('finished_at', 'desc')
            ->limit(10)
            ->get();

        return view('driver.travel.index', compact(
            'todaySchedule',
            'upcomingSchedules',
            'recentCompletedSchedules'
        ));
    }

    /**
     * Detail jadwal + penumpang
     */
    public function show(Schedule $schedule): View
    {
        $driver = auth()->user();

        if ($schedule->driver_id !== $driver->id) {
            abort(403);
        }

        $schedule->load([
            'route.stops',
            'vehicle',
            'agency',
            'bookings' => function ($q) {
                $q->whereNotIn('status', ['cancelled'])
                    ->with(['originStop', 'destinationStop', 'passengers', 'customer', 'payment']);
            },
        ]);

        return view('driver.travel.show', compact('schedule'));
    }

    /**
     * Driver menyelesaikan seluruh jadwal
     */
    public function finish(Schedule $schedule): RedirectResponse
    {
        $driver = auth()->user();

        if ($schedule->driver_id !== $driver->id) {
            return back()->with('error', 'Anda tidak bertugas di jadwal ini.');
        }

        if (!$schedule->started_at) {
            return back()->with('error', 'Jadwal belum dimulai oleh agency.');
        }

        if ($schedule->finished_at) {
            return back()->with('error', 'Jadwal sudah selesai.');
        }

        try {
            DB::transaction(function () use ($schedule) {
                $bookings = $schedule->bookings()
                    ->where('status', '!=', 'completed')
                    ->where('status', '!=', 'cancelled')
                    ->get();

                $walletService = app(\App\Services\WalletService::class);
                $notificationService = app(\App\Services\NotificationService::class);

                foreach ($bookings as $booking) {
                    BookingPassenger::where('booking_id', $booking->id)
                        ->whereNull('picked_up_at')
                        ->update(['picked_up_at' => now()]);

                    BookingPassenger::where('booking_id', $booking->id)
                        ->whereNull('dropped_off_at')
                        ->update(['dropped_off_at' => now()]);

                    $booking->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);

                    $walletService->releaseFunds($booking);
                    $booking->schedule->agency->increment('total_bookings');
                    $notificationService->bookingCompleted($booking);
                }

                $schedule->update(['finished_at' => now()]);

                if ($schedule->allow_cod && $schedule->cod_min_balance > 0) {
                    $walletService->releaseCodDeposit(
                        $schedule->agency,
                        $schedule->cod_min_balance,
                        $schedule->id
                    );
                }
            });

            return redirect()->route('driver.travel.index')
                ->with('success', 'Seluruh perjalanan selesai!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyelesaikan jadwal: ' . $e->getMessage());
        }
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

        BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('picked_up_at')
            ->update(['picked_up_at' => now()]);

        if (in_array($booking->status, ['paid', 'confirmed'])) {
            $booking->update(['status' => 'on_going']);
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

        BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('dropped_off_at')
            ->update(['dropped_off_at' => now()]);

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

        $allDroppedOff = BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('dropped_off_at')->doesntExist();

        if (!$allDroppedOff) {
            return back()->with('error', 'Semua penumpang harus diturunkan terlebih dahulu.');
        }

        $booking->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        app(\App\Services\WalletService::class)->releaseFunds($booking);
        $booking->schedule->agency->increment('total_bookings');

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

        try {
            DB::transaction(function () use ($booking, $driver) {
                app(\App\Services\WalletService::class)->releaseCodBalance($booking);

                $booking->payment->update([
                    'status' => \App\Enums\PaymentStatus::COD_CONFIRMED->value,
                    'paid_at' => now(),
                ]);

                $booking->update(['status' => \App\Enums\BookingStatus::PAID->value]);

                BookingPassenger::where('booking_id', $booking->id)
                    ->update([
                        'cod_paid' => true,
                        'cod_paid_at' => now(),
                        'cod_confirmed_by' => $driver->id,
                    ]);
            });

            return back()->with('success', 'Pembayaran COD berhasil dikonfirmasi!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}