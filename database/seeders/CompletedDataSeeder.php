<?php
// File: database/seeders/CompletedDataSeeder.php
// Deskripsi: Mengubah schedule & booking menjadi COMPLETED (riwayat perjalanan)

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\ScheduleStop;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CompletedDataSeeder extends Seeder
{
    public function run(): void
    {
        echo "🏁 GENERATING COMPLETED BOOKING HISTORY...\n";
        echo "═══════════════════════════════════════════\n\n";

        // ========================================
        // BAGIAN 1: UPDATE SCHEDULE KE MASA LALU
        // ========================================
        echo "📅 UPDATING SCHEDULES TO PAST DATES...\n";

        $schedules = Schedule::where('departure_date', '>=', Carbon::now()->toDateString())
            ->orderBy('id')
            ->take(5)
            ->get();

        $pastDates = [14, 10, 7, 7, 5, 5, 3, 3, 2, 1];

        foreach ($schedules as $index => $schedule) {
            $daysAgo = $pastDates[$index] ?? rand(1, 14);
            $pastDate = Carbon::now()->subDays($daysAgo)->toDateString();
            $departureTime = $schedule->departure_time ?? '08:00';
            $departureDateTime = Carbon::parse($pastDate . ' ' . $departureTime);
            $durationMinutes = $schedule->route->estimated_duration ?? 180;

            $schedule->update([
                'departure_date' => $pastDate,
                'started_at' => $departureDateTime,
                'finished_at' => $departureDateTime->copy()->addMinutes($durationMinutes),
            ]);

            // Update schedule stops estimated time
            $stops = ScheduleStop::where('schedule_id', $schedule->id)->orderBy('id')->get();
            $stopCount = $stops->count();
            foreach ($stops as $stopIndex => $stop) {
                $minutesFromStart = $stopCount > 1 ? ($stopIndex / ($stopCount - 1)) * $durationMinutes : 0;
                $stop->update([
                    'estimated_time' => $departureDateTime->copy()->addMinutes($minutesFromStart)->format('H:i'),
                ]);
            }

            echo "  ✅ Schedule #{$schedule->id}: {$pastDate} ({$daysAgo} hari lalu)\n";
        }

        // ========================================
        // BAGIAN 2: UPDATE BOOKING JADI COMPLETED
        // ========================================
        echo "\n🏁 UPDATING BOOKINGS TO COMPLETED...\n";

        $scheduleIds = $schedules->pluck('id')->toArray();

        $bookings = Booking::whereIn('schedule_id', $scheduleIds)
            ->where('status', 'paid')
            ->with(['schedule'])
            ->get();

        $completedCount = 0;

        $positiveComments = [
            'Pelayanan sangat memuaskan! Sopir ramah dan tepat waktu.',
            'Mobil bersih dan nyaman. Perjalanan menyenangkan.',
            'Recommended banget! Harga terjangkau, fasilitas lengkap.',
            'Sopir sangat profesional dan hati-hati dalam berkendara.',
            'Tepat waktu dan amanah. Pasti booking lagi di sini.',
            'Pelayanan prima! Fasilitas sesuai dengan yang dijanjikan.',
            'Sangat puas dengan pelayanannya. Sopir ramah banget!',
            'Perjalanan lancar, mobil nyaman, sopir sopan. Top!',
            'Booking mudah, pelayanan bagus. Recommended!',
            'Armada bagus dan terawat. Perjalanan jadi nyaman.',
        ];

        $neutralComments = [
            'Cukup baik, tapi AC kurang dingin.',
            'Perjalanan oke, tapi sedikit terlambat 15 menit.',
            'Lumayan, sesuai dengan harga.',
            'Biasa saja, tidak ada yang spesial.',
        ];

        $negativeComments = [
            'Sopir kurang ramah dan ugal-ugalan.',
            'Mobil kotor dan berbau rokok.',
            'Terlambat 1 jam! Tidak tepat waktu.',
        ];

        $reviewedAgencies = [];

        foreach ($bookings as $booking) {
            $schedule = $booking->schedule;

            // Update booking status + completed_at (ADA di tabel bookings)
            $booking->update([
                'status' => 'completed',
                'completed_at' => $schedule->finished_at ?? now()->subDays(rand(1, 3)),
            ]);

            // Add review (60% chance)
            $customerId = $booking->customer_id;
            $agencyId = $schedule->agency_id;
            $reviewKey = "{$customerId}-{$agencyId}";

            if (fake()->boolean(60) && !in_array($reviewKey, $reviewedAgencies)) {
                $ratingRand = rand(1, 100);

                if ($ratingRand <= 60) {
                    $rating = rand(4, 5);
                    $comment = fake()->randomElement($positiveComments);
                } elseif ($ratingRand <= 85) {
                    $rating = rand(3, 4);
                    $comment = fake()->randomElement($neutralComments);
                } else {
                    $rating = rand(1, 2);
                    $comment = fake()->randomElement($negativeComments);
                }

                $existingReview = Review::where('booking_id', $booking->id)->first();
                if (!$existingReview) {
                    Review::create([
                        'booking_id' => $booking->id,
                        'agency_id' => $agencyId,
                        'customer_id' => $customerId,
                        'rating' => $rating,
                        'review' => $comment,
                        'created_at' => $schedule->finished_at ?? now()->subDays(rand(1, 3)),
                        'updated_at' => $schedule->finished_at ?? now()->subDays(rand(1, 3)),
                    ]);

                    $reviewedAgencies[] = $reviewKey;
                }
            }

            $completedCount++;
        }

        echo "  ✅ {$completedCount} bookings updated to COMPLETED\n";

        // ========================================
        // RINGKASAN
        // ========================================
        echo "\n═══════════════════════════════════════════\n";
        echo "✅ COMPLETED DATA GENERATED!\n";
        echo "═══════════════════════════════════════════\n\n";

        echo "📊 BOOKING STATUS BREAKDOWN:\n";
        echo "──────────────────────────────────────────────\n";
        $total = Booking::count();
        $statuses = ['pending', 'paid', 'completed', 'cancelled'];
        foreach ($statuses as $status) {
            $count = Booking::where('status', $status)->count();
            $pct = $total > 0 ? round($count / max(1, $total) * 100, 1) : 0;
            $bar = str_repeat('█', $count > 0 ? ceil($count / max(1, $total) * 20) : 0);
            echo "  • {$status}: {$bar} ({$count} - {$pct}%)\n";
        }
        echo "──────────────────────────────────────────────\n\n";

        echo "📊 SCHEDULE STATUS BREAKDOWN:\n";
        echo "──────────────────────────────────────────────\n";
        $upcoming = Schedule::where('departure_date', '>=', Carbon::now()->toDateString())->count();
        $past = Schedule::where('departure_date', '<', Carbon::now()->toDateString())->count();
        $started = Schedule::whereNotNull('started_at')->count();
        $finished = Schedule::whereNotNull('finished_at')->count();
        echo "  • Upcoming: {$upcoming}\n";
        echo "  • Past: {$past}\n";
        echo "  • Started: {$started}\n";
        echo "  • Finished: {$finished}\n";
        echo "──────────────────────────────────────────────\n";
    }
}