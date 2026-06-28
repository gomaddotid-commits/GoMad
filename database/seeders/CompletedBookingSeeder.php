<?php
// File: database/seeders/CompletedBookingSeeder.php
// Deskripsi: Mengubah sebagian booking PAID menjadi COMPLETED + tambah data pendukung

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CompletedBookingSeeder extends Seeder
{
    public function run(): void
    {
        echo "🏁 GENERATING COMPLETED BOOKINGS...\n";
        echo "═══════════════════════════════════════════\n\n";

        // Ambil booking PAID yang schedule-nya SUDAH LEWAT (max 3 hari ke depan)
        $bookings = Booking::where('status', 'paid')
            ->whereHas('schedule', function ($query) {
                $query->where('departure_date', '<', Carbon::now()->subDay()->toDateString());
            })
            ->with(['schedule', 'payments', 'passengers'])
            ->get();

        if ($bookings->isEmpty()) {
            echo "⚠️  Tidak ada booking paid yang schedule-nya sudah lewat\n";
            echo "   (Butuh schedule dengan departure_date < hari ini)\n";
            return;
        }

        $completedCount = 0;

        foreach ($bookings as $booking) {
            // Update status booking jadi completed
            $booking->update([
                'status' => 'completed',
            ]);

            // Update schedule: set started_at & finished_at (kalau belum)
            $schedule = $booking->schedule;
            if ($schedule && !$schedule->started_at) {
                $departureDateTime = Carbon::parse($schedule->departure_date . ' ' . $schedule->departure_time);
                $schedule->update([
                    'started_at' => $departureDateTime,
                    'finished_at' => $departureDateTime->addMinutes($schedule->route->estimated_duration ?? 180),
                ]);
            }

            // Update payment: set completed_at
            $payment = $booking->payments->first();
            if ($payment) {
                $payment->update([
                    'completed_at' => $schedule->finished_at ?? now()->subDays(rand(1, 3)),
                ]);
            }

            $completedCount++;

            if ($completedCount % 10 == 0) {
                echo "  ✅ {$completedCount} bookings completed\n";
            }
        }

        echo "\n═══════════════════════════════════════════\n";
        echo "✅ {$completedCount} BOOKINGS UPDATED TO COMPLETED!\n";
        echo "═══════════════════════════════════════════\n\n";

        echo "📊 BOOKING STATUS BREAKDOWN:\n";
        echo "──────────────────────────────────────────────\n";
        $statuses = ['pending', 'paid', 'completed', 'cancelled'];
        foreach ($statuses as $status) {
            $count = Booking::where('status', $status)->count();
            $bar = str_repeat('█', $count > 0 ? ceil($count / max(1, Booking::count()) * 20) : 0);
            echo "  • {$status}: {$bar} ({$count})\n";
        }
        echo "──────────────────────────────────────────────\n";
    }
}