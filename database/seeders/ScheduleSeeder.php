<?php
// File: database/seeders/ScheduleSeeder.php
// Deskripsi: Seeder untuk jadwal, schedule stops, route pricing, bookings, dan data transfer

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\Payment;
use App\Models\Route;
use App\Models\RoutePricing;
use App\Models\Schedule;
use App\Models\ScheduleStop;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $agency1 = Agency::where('slug', 'travel-jaya-abadi')->first();
        $agency2 = Agency::where('slug', 'makmur-travel')->first();
        
        if (!$agency1 || !$agency2) {
            echo "⚠️ Agency tidak ditemukan. Pastikan UserSeeder sudah dijalankan.\n";
            return;
        }

        // Verifikasi agency2
        $agency2->update(['is_verified' => true]);
        
        // Update semua route dengan COD settings
        Route::query()->update([
            'cod_available' => true,
            'cod_min_deposit' => 500000,
        ]);

        $route1 = Route::where('route_name', 'Sumenep - Surabaya')->first();
        $route2 = Route::where('route_name', 'Sumenep - Malang')->first();
        
        $vehicle1 = Vehicle::where('plate_number', 'M 1234 AB')->first();
        $vehicle2 = Vehicle::where('plate_number', 'M 5678 CD')->first();
        $vehicle3 = Vehicle::where('plate_number', 'M 9999 XY')->first();
        
        $driver1 = User::where('email', 'supir1@test.com')->first();
        $driver2 = User::where('email', 'supir2@test.com')->first();
        
        $customer1 = User::where('email', 'budi@test.com')->first();
        $customer2 = User::where('email', 'ani@test.com')->first();

        if (!$route1 || !$vehicle1) {
            echo "⚠️ Route atau Vehicle tidak ditemukan.\n";
            return;
        }

        $today = Carbon::now();
        $tomorrow = Carbon::tomorrow();
        $dayAfter = Carbon::tomorrow()->addDay();

        // ============================================================
        // SCHEDULE 1: Hari Ini - Sumenep → Surabaya (Travel Jaya Abadi)
        // ============================================================
        echo "Membuat Schedule 1 (Hari Ini)...\n";
        
        $schedule1 = Schedule::create([
            'agency_id' => $agency1->id,
            'vehicle_id' => $vehicle1->id,
            'route_id' => $route1->id,
            'driver_id' => $driver1?->id,
            'departure_date' => $today->toDateString(),
            'departure_time' => '08:00',
            'travel_class' => 'economy',
            'max_overload' => 2,
            'price_per_seat' => 150000,
            'baggage_limit_kg' => 15.00,
            'is_active' => true,
            'allow_passenger_transfer' => true,
            'accept_external_transfer' => true,
            'transfer_fee_per_passenger' => 20000,
            'allow_cod' => true,
            'cod_min_balance' => 500000,
            'started_at' => null,
            'finished_at' => null,
        ]);

        $this->createStopsAndPricing($schedule1, $route1);

        // Booking di Schedule 1
        if ($customer1) {
            $this->createBooking($schedule1, $customer1, $route1, 0, 3, 2, 300000, 'paid', 'midtrans', 'Jl. Kartini No. 10, Sumenep', 'Jl. Pahlawan No. 20, Surabaya', 'Budi Prasetyo', '081555555555', 'Siti Aminah', '081555555556');
        }
        if ($customer2) {
            $this->createBooking($schedule1, $customer2, $route1, 1, 3, 1, 110000, 'paid', 'cash', 'Jl. Diponegoro No. 5, Pamekasan', 'Jl. Veteran No. 15, Surabaya', 'Ani Rahmawati', '081666666666');
        }

        echo "✅ Schedule 1: Hari ini, Sumenep→Surabaya, 3 pax, COD Available\n";

        // ============================================================
        // SCHEDULE 2: Besok - Sumenep → Malang (Travel Jaya Abadi)
        // ============================================================
        echo "Membuat Schedule 2 (Besok)...\n";
        
        $schedule2 = Schedule::create([
            'agency_id' => $agency1->id,
            'vehicle_id' => $vehicle2->id,
            'route_id' => $route2->id,
            'driver_id' => $driver2?->id,
            'departure_date' => $tomorrow->toDateString(),
            'departure_time' => '07:00',
            'travel_class' => 'economy',
            'max_overload' => 2,
            'price_per_seat' => 200000,
            'baggage_limit_kg' => 15.00,
            'is_active' => true,
            'allow_passenger_transfer' => true,
            'accept_external_transfer' => true,
            'transfer_fee_per_passenger' => 20000,
            'allow_cod' => true,
            'cod_min_balance' => 500000,
            'started_at' => null,
            'finished_at' => null,
        ]);

        $this->createStopsAndPricing($schedule2, $route2);
        echo "✅ Schedule 2: Besok, Sumenep→Malang, 0 pax, COD Available\n";

        // ============================================================
        // SCHEDULE 3: Lusa - Sumenep → Surabaya (Travel Jaya Abadi) - SEPI
        // ============================================================
        echo "Membuat Schedule 3 (Lusa - Transfer)...\n";
        
        $schedule3 = Schedule::create([
            'agency_id' => $agency1->id,
            'vehicle_id' => $vehicle1->id,
            'route_id' => $route1->id,
            'driver_id' => null,
            'departure_date' => $dayAfter->toDateString(),
            'departure_time' => '09:00',
            'travel_class' => 'economy',
            'max_overload' => 2,
            'price_per_seat' => 150000,
            'baggage_limit_kg' => 15.00,
            'is_active' => true,
            'allow_passenger_transfer' => true,
            'accept_external_transfer' => true,
            'transfer_fee_per_passenger' => 20000,
            'allow_cod' => true,
            'cod_min_balance' => 500000,
            'started_at' => null,
            'finished_at' => null,
        ]);

        $this->createStopsAndPricing($schedule3, $route1);

        if ($customer1) {
            $this->createBooking($schedule3, $customer1, $route1, 0, 3, 2, 300000, 'paid', 'midtrans', 'Jl. Trunojoyo No. 45, Sumenep', 'Jl. Basuki Rahmat No. 10, Surabaya', 'Rudi Hartono', '081555555557', 'Dewi Lestari', '081555555558');
        }

        echo "✅ Schedule 3: Lusa, Sumenep→Surabaya, 2 pax (sepi), Transfer Out\n";

        // ============================================================
        // SCHEDULE 4: Lusa - Sumenep → Surabaya (Makmur Travel)
        // ============================================================
        echo "Membuat Schedule 4 (Penerima Transfer)...\n";
        
        if ($vehicle3) {
            $schedule4 = Schedule::create([
                'agency_id' => $agency2->id,
                'vehicle_id' => $vehicle3->id,
                'route_id' => $route1->id,
                'driver_id' => null,
                'departure_date' => $dayAfter->toDateString(),
                'departure_time' => '08:00',
                'travel_class' => 'economy',
                'max_overload' => 2,
                'price_per_seat' => 160000,
                'baggage_limit_kg' => 15.00,
                'is_active' => true,
                'allow_passenger_transfer' => true,
                'accept_external_transfer' => true,
                'transfer_fee_per_passenger' => 25000,
                'allow_cod' => false,
                'cod_min_balance' => 500000,
                'started_at' => null,
                'finished_at' => null,
            ]);

            $this->createStopsAndPricing($schedule4, $route1, [55000, 105000, 160000, 65000, 115000, 65000]);

            if ($customer2) {
                $this->createBooking($schedule4, $customer2, $route1, 0, 3, 3, 480000, 'paid', 'midtrans', 'Jl. Raya Pamekasan No. 25', 'Jl. Ahmad Yani No. 30, Surabaya', 'Bambang', '081666666667', 'Citra', '081666666668', 'Dimas', '081666666669');
            }

            echo "✅ Schedule 4: Lusa, Sumenep→Surabaya, 3 pax, Makmur Travel, Penerima Transfer\n";
        }

        // ============================================================
        // RINGKASAN
        // ============================================================
        echo "\n📊 RINGKASAN:\n";
        echo "──────────────────────────────────────────────\n";
        echo "Schedule 1: HARI INI, Sumenep→Surabaya, 3 pax, COD ✅\n";
        echo "Schedule 2: BESOK, Sumenep→Malang, 0 pax, COD ✅\n";
        echo "Schedule 3: LUSA, Sumenep→Surabaya, 2 pax, Transfer Out 🔄\n";
        echo "Schedule 4: LUSA, Sumenep→Surabaya, 3 pax, Penerima Transfer 📥\n";
        echo "──────────────────────────────────────────────\n";
        echo "\n💡 TIPS DEMO COD:\n";
        echo "  1. Agency harus Top Up deposit dulu (min Rp 500K)\n";
        echo "  2. Buat jadwal dengan centang COD\n";
        echo "  3. Customer pilih Bayar ke Sopir\n";
        echo "  4. Driver konfirmasi pembayaran COD\n";
    }

    /**
     * Helper: Buat schedule stops dan pricing
     */
    private function createStopsAndPricing(Schedule $schedule, Route $route, ?array $customPrices = null): void
    {
        $routeStops = $route->stops()->orderBy('stop_order')->get();
        
        foreach ($routeStops as $index => $stop) {
            ScheduleStop::create([
                'schedule_id' => $schedule->id,
                'route_stop_id' => $stop->id,
                'is_pickup_available' => $index === 0 || $index < count($routeStops) - 1,
                'is_dropoff_available' => $index > 0,
                'estimated_time' => null,
            ]);
        }

        $pairs = $this->getPricingPairs(count($routeStops), $customPrices);
        
        foreach ($pairs as $pair) {
            RoutePricing::create([
                'schedule_id' => $schedule->id,
                'origin_stop_id' => $routeStops[$pair[0]]->id,
                'destination_stop_id' => $routeStops[$pair[1]]->id,
                'price' => $pair[2],
            ]);
        }
    }

    /**
     * Helper: Dapatkan pasangan pricing
     */
    private function getPricingPairs(int $stopCount, ?array $customPrices = null): array
    {
        if ($stopCount === 4) {
            $defaults = [[0,1,50000],[0,2,100000],[0,3,150000],[1,2,60000],[1,3,110000],[2,3,60000]];
            if ($customPrices) {
                foreach ($defaults as $i => $pair) {
                    if (isset($customPrices[$i])) $defaults[$i][2] = $customPrices[$i];
                }
            }
            return $defaults;
        }
        
        if ($stopCount === 5) {
            return [[0,1,50000],[0,2,100000],[0,3,150000],[0,4,200000],[1,2,60000],[1,3,110000],[1,4,160000],[2,3,60000],[2,4,110000],[3,4,60000]];
        }
        
        return [];
    }

    /**
     * Helper: Buat booking
     */
    private function createBooking(Schedule $schedule, User $customer, Route $route, int $originIdx, int $destIdx, int $passengerCount, float $totalPrice, string $status, string $paymentType, string $pickupAddr, string $destAddr, string $name1, string $phone1, ?string $name2 = null, ?string $phone2 = null, ?string $name3 = null, ?string $phone3 = null): void
    {
        $routeStops = $route->stops()->orderBy('stop_order')->get();
        
        $pricing = RoutePricing::where('schedule_id', $schedule->id)
            ->where('origin_stop_id', $routeStops[$originIdx]->id)
            ->where('destination_stop_id', $routeStops[$destIdx]->id)
            ->first();
            
        if (!$pricing) return;

        $booking = Booking::create([
            'booking_code' => 'GM-' . now()->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'schedule_id' => $schedule->id,
            'customer_id' => $customer->id,
            'origin_stop_id' => $routeStops[$originIdx]->id,
            'destination_stop_id' => $routeStops[$destIdx]->id,
            'route_pricing_id' => $pricing->id,
            'pickup_address' => $pickupAddr,
            'destination_address' => $destAddr,
            'total_passengers' => $passengerCount,
            'total_price' => $totalPrice,
            'status' => $status,
        ]);

        $passengers = [[$name1, $phone1]];
        if ($name2) $passengers[] = [$name2, $phone2];
        if ($name3) $passengers[] = [$name3, $phone3];

        foreach ($passengers as $i => $p) {
            BookingPassenger::create([
                'booking_id' => $booking->id,
                'passenger_name' => $p[0],
                'passenger_phone' => $p[1],
                'baggage_weight' => rand(5, 15),
                'seat_number' => $i + 1,
            ]);
        }

        if ($status === 'paid') {
            Payment::create([
                'booking_id' => $booking->id,
                'amount' => $totalPrice,
                'commission' => $totalPrice * 0.05,
                'agency_revenue' => $totalPrice * 0.95,
                'payment_type' => $paymentType,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }
    }
}

// End of file