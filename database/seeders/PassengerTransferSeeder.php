<?php
// File: database/seeders/PassengerTransferSeeder.php
// Deskripsi: Seeder untuk data transfer penumpang antar schedule

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Booking;
use App\Models\PassengerTransfer;
use App\Models\Schedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PassengerTransferSeeder extends Seeder
{
    public function run(): void
    {
        echo "🔄 GENERATING PASSENGER TRANSFERS...\n";
        echo "═══════════════════════════════════════════\n\n";

        // Ambil schedule yang allow transfer
        $schedules = Schedule::where('allow_passenger_transfer', true)
            ->where('is_active', true)
            ->get();

        if ($schedules->count() < 2) {
            echo "⚠️  Minimal butuh 2 schedule untuk transfer\n";
            return;
        }

        $transferCount = 0;
        $adminId = \App\Models\User::where('email', 'admin@gomad.id')->first()?->id ?? 1;

        // Grup schedule by route & date
        $groupedSchedules = $schedules->groupBy(function ($schedule) {
            return $schedule->route_id . '-' . $schedule->departure_date;
        });

        foreach ($groupedSchedules as $group) {
            if ($group->count() < 2) continue;

            $fromSchedule = $group->first();
            $toSchedule = $group->last();

            // Skip kalau agency sama
            if ($fromSchedule->agency_id === $toSchedule->agency_id) continue;

            // Hanya proses beberapa transfer
            if ($transferCount >= 30) break;

            $statuses = ['completed', 'completed', 'completed', 'approved', 'pending', 'pending', 'rejected', 'cancelled'];
            $status = fake()->randomElement($statuses);

            $totalPassengers = rand(1, 3);
            $transferFeePerPassenger = rand(15000, 30000);
            $totalTransferFee = $totalPassengers * $transferFeePerPassenger;
            $totalBookingValue = $totalPassengers * $fromSchedule->price_per_seat;

            $transfer = PassengerTransfer::create([
                'from_schedule_id' => $fromSchedule->id,
                'to_schedule_id' => $toSchedule->id,
                'from_agency_id' => $fromSchedule->agency_id,
                'to_agency_id' => $toSchedule->agency_id,
                'total_passengers' => $totalPassengers,
                'transfer_fee_per_passenger' => $transferFeePerPassenger,
                'total_transfer_fee' => $totalTransferFee,
                'total_booking_value' => $totalBookingValue,
                'status' => $status,
                'rejection_reason' => in_array($status, ['rejected', 'cancelled']) 
                    ? fake()->randomElement([
                        'Jadwal tujuan sudah penuh.',
                        'Agency tujuan menolak transfer.',
                        'Biaya transfer tidak disetujui.',
                        'Permintaan dibatalkan oleh customer.',
                    ]) 
                    : null,
                'approved_by' => in_array($status, ['approved', 'completed']) ? $adminId : null,
                'approved_at' => in_array($status, ['approved', 'completed']) ? now()->subDays(rand(1, 7)) : null,
                'completed_at' => $status === 'completed' ? now()->subDays(rand(0, 3)) : null,
                'notes' => fake()->boolean(50) ? fake()->sentence() : null,
                'created_at' => now()->subDays(rand(1, 14)),
            ]);

            // Attach bookings ke transfer
            $bookings = Booking::where('schedule_id', $fromSchedule->id)
                ->where('status', 'paid')
                ->take($totalPassengers)
                ->get();

            foreach ($bookings as $booking) {
                DB::table('passenger_transfer_bookings')->insert([
                    'passenger_transfer_id' => $transfer->id,
                    'booking_id' => $booking->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $transferCount++;
            echo "  ✅ Transfer #{$transferCount}: {$fromSchedule->agency->agency_name} → {$toSchedule->agency->agency_name} ({$status})\n";
        }

        echo "\n═══════════════════════════════════════════\n";
        echo "✅ {$transferCount} Passenger Transfers created\n";
        echo "═══════════════════════════════════════════\n\n";

        echo "📊 TRANSFER STATUS BREAKDOWN:\n";
        echo "──────────────────────────────────────────────\n";
        $statuses = ['pending', 'approved', 'rejected', 'completed', 'cancelled'];
        foreach ($statuses as $s) {
            $count = PassengerTransfer::where('status', $s)->count();
            echo "  • {$s}: {$count}\n";
        }
        echo "──────────────────────────────────────────────\n";
    }
}