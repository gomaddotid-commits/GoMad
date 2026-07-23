<?php
// File: app/Http/Controllers/Web/Driver/ScheduleController.php
// Deskripsi: Web Controller untuk jadwal driver

namespace App\Http\Controllers\Web\Driver;

use App\Http\Controllers\Controller;
use App\Models\BookingPassenger;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * Halaman jadwal driver
     */
    public function index(): View
    {
        return view('driver.schedule.index');
    }

    /**
     * Detail jadwal + penumpang
     */
    public function show(Schedule $schedule): View
    {
        $schedule->load([
            'bookings' => function($q) {
                $q->whereNotIn('status', ['cancelled'])
                    ->with(['originStop', 'destinationStop', 'passengers', 'customer', 'payment']);
            },
            'route.stops',
            'vehicle',
            'agency',
        ]);

        return view('driver.booking.show', compact('schedule'));
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
                    // Pastikan semua penumpang sudah dijemput & diturunkan
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

                    // Release funds
                    $walletService->releaseFunds($booking);
                    $booking->schedule->agency->increment('total_bookings');
                    $notificationService->bookingCompleted($booking);
                }

                // Tandai jadwal selesai
                $schedule->update(['finished_at' => now()]);

                // Release saldo COD yang di-hold untuk jadwal ini
                if ($schedule->allow_cod && $schedule->cod_min_balance > 0) {
                    $walletService->releaseCodDeposit(
                        $schedule->agency,
                        $schedule->cod_min_balance,
                        $schedule->id
                    );
                }
            });

            return back()->with('success', 'Seluruh perjalanan selesai!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyelesaikan jadwal: ' . $e->getMessage());
        }
    }
}

// End of file