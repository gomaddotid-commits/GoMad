<?php

namespace Database\Seeders;

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

        $schedules = Schedule::where('allow_passenger_transfer', true)
            ->where('is_active', true)
            ->get();

        if ($schedules->count() < 2) {
            echo "⚠️  Minimal butuh 2 schedule untuk transfer\n";
            return;
        }

        $transferCount = 0;
        $adminId = \App\Models\User::where('email', 'admin@gomad.id')->first()?->id ?? 1;

        $groupedSchedules = $schedules->groupBy(function ($schedule) {
            return $schedule->route_id . '-' . $schedule->departure_date;
        });

        $statuses = ['completed', 'completed', 'completed', 'approved', 'pending', 'pending', 'rejected', 'cancelled'];
        $rejectionMessages = [
            'Jadwal tujuan sudah penuh.',
            'Agency tujuan menolak transfer.',
            'Biaya transfer tidak disetujui.',
            'Permintaan dibatalkan oleh customer.',
        ];

        foreach ($groupedSchedules as $group) {
            if ($group->count() < 2) continue;

            $fromSchedule = $group->first();
            $toSchedule = $group->last();

            if ($fromSchedule->agency_id === $toSchedule->agency_id) continue;
            if ($transferCount >= 30) break;

            $status = $statuses[array_rand($statuses)];
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
                    ? $rejectionMessages[array_rand($rejectionMessages)]
                    : null,
                'approved_by' => in_array($status, ['approved', 'completed']) ? $adminId : null,
                'approved_at' => in_array($status, ['approved', 'completed']) ? now()->subDays(rand(1, 7)) : null,
                'completed_at' => $status === 'completed' ? now()->subDays(rand(0, 3)) : null,
                'notes' => rand(0, 1) ? 'Transfer penumpang antar agency.' : null,
                'created_at' => now()->subDays(rand(1, 14)),
            ]);

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

        echo "📊 STATUS BREAKDOWN:\n";
        foreach (['pending', 'approved', 'rejected', 'completed', 'cancelled'] as $s) {
            echo "  • {$s}: " . PassengerTransfer::where('status', $s)->count() . "\n";
        }
    }
}
