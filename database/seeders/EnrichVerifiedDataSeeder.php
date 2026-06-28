<?php
// File: database/seeders/EnrichVerifiedDataSeeder.php
// Deskripsi: Memperkaya data yang SUDAH VERIFIED dengan relasi tambahan
// Yang disentuh: Travel Jaya Abadi (verified), Payment Agents (verified), Customers (active), Routes, Schedules

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\CashPayment;
use App\Models\Payment;
use App\Models\PaymentAgent;
use App\Models\Promo;
use App\Models\PromoUsage;
use App\Models\Route;
use App\Models\RoutePricing;
use App\Models\Schedule;
use App\Models\ScheduleStop;
use App\Models\Settlement;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EnrichVerifiedDataSeeder extends Seeder
{
    public function run(): void
    {
        echo "🔄 MEMPERKAYA DATA VERIFIED SAJA...\n";
        echo "═══════════════════════════════════════════\n\n";

        // ========================================
        // BAGIAN 1: PERKAYA ROUTE
        // ========================================
        $this->enrichRoutes();

        // ========================================
        // BAGIAN 2: TAMBAH KENDARAAN PREMIUM
        // ========================================
        $this->addPremiumVehicleToJayaAbadi();

        // ========================================
        // BAGIAN 3: SCHEDULE BARU
        // ========================================
        $this->addNewSchedules();

        // ========================================
        // BAGIAN 4: BOOKING BARU (BUDI & ANI)
        // ========================================
        $this->addNewBookingsForCustomers();

        // ========================================
        // BAGIAN 5: TRANSAKSI PAYMENT AGENT
        // ========================================
        $this->enrichPaymentAgents();

        // ========================================
        // BAGIAN 6: PROMO & USAGE
        // ========================================
        $this->addPromoAndUsage();

        echo "\n═══════════════════════════════════════════\n";
        echo "✅ SEMUA DATA VERIFIED BERHASIL DIPERKAYA!\n";
        echo "═══════════════════════════════════════════\n";
        
        echo "\n📊 RINGKASAN DATA BARU:\n";
        echo "──────────────────────────────────────────────\n";
        echo "🗺️  Routes: Update semua fitur (COD, transfer, dll)\n";
        echo "🚗 Vehicle: M 7777 XX (Premium) → Jaya Abadi\n";
        echo "📅 Schedule: +3 jadwal baru (Jember, Jakarta, SBY Premium)\n";
        echo "🎫 Booking: +3 booking (Jember paid, Jakarta cancelled, SBY Premium COD)\n";
        echo "💰 Payment Agents: +Cash payments & settlements\n";
        echo "🏷️  Promo: +2 promo baru, +2 usage\n";
        echo "──────────────────────────────────────────────\n";
        echo "\n⚠️  Yang TIDAK disentuh:\n";
        echo "  ❌ Makmur Travel (unverified)\n";
        echo "  ❌ Warung Barokah (pending)\n";
        echo "  ❌ Semua user, email, password (tetap)\n";
    }

    /**
     * BAGIAN 1: Perkaya Routes
     */
    private function enrichRoutes(): void
    {
        echo "🗺️  PERKAYA ROUTES...\n";

        // Update semua route dengan fitur lengkap
        $routes = Route::all();
        foreach ($routes as $route) {
            $route->update([
                'cod_available' => true,
                'cod_min_deposit' => in_array($route->destination_city, ['Jakarta']) ? 1000000 : 500000,
            ]);
        }

        // Route Jember: enable transfer
        $routeJember = Route::where('route_name', 'Sumenep - Jember')->first();
        if ($routeJember) {
            $routeJember->update([
                'cod_available' => true,
                'cod_min_deposit' => 500000,
            ]);
        }

        // Route Jakarta: special long distance settings
        $routeJakarta = Route::where('route_name', 'Sumenep - Jakarta')->first();
        if ($routeJakarta) {
            $routeJakarta->update([
                'cod_available' => true,
                'cod_min_deposit' => 1000000,
            ]);
        }

        echo "  ✅ Semua route diupdate (COD, transfer settings)\n";
    }

    /**
     * BAGIAN 2: Tambah Kendaraan Premium untuk Jaya Abadi
     */
    private function addPremiumVehicleToJayaAbadi(): void
    {
        echo "\n🚗 TAMBAH KENDARAAN PREMIUM...\n";

        $agency = Agency::where('slug', 'travel-jaya-abadi')->first();
        if (!$agency) {
            echo "  ⚠️ Travel Jaya Abadi tidak ditemukan\n";
            return;
        }

        $vehicle = Vehicle::firstOrCreate(
            ['plate_number' => 'M 7777 XX'],
            [
                'agency_id' => $agency->id,
                'brand' => 'Toyota',
                'model' => 'Hiace Premio',
                'year' => 2024,
                'capacity' => 6,
                'type' => 'premium',
                'is_active' => true,
            ]
        );

        echo "  ✅ Vehicle PREMIUM: M 7777 XX (Toyota Hiace Premio, 6 kursi)\n";
    }

    /**
     * BAGIAN 3: Schedule Baru
     */
    private function addNewSchedules(): void
    {
        echo "\n📅 TAMBAH SCHEDULE BARU...\n";

        $agencyJaya = Agency::where('slug', 'travel-jaya-abadi')->first();
        if (!$agencyJaya) {
            echo "  ⚠️ Travel Jaya Abadi tidak ditemukan\n";
            return;
        }

        $routeJember = Route::where('route_name', 'Sumenep - Jember')->first();
        $routeJakarta = Route::where('route_name', 'Sumenep - Jakarta')->first();
        $routeSby = Route::where('route_name', 'Sumenep - Surabaya')->first();

        $vehiclePremium = Vehicle::where('plate_number', 'M 7777 XX')->first();
        $vehicleEconomy = Vehicle::where('plate_number', 'M 1234 AB')->first();

        $driver1 = User::where('email', 'supir1@test.com')->first();
        $driver2 = User::where('email', 'supir2@test.com')->first();

        $tomorrow = Carbon::tomorrow();
        $dayAfter = Carbon::tomorrow()->addDay();
        $threeDays = Carbon::tomorrow()->addDays(3);

        // ===========================================
        // Schedule 5: Besok, Sumenep→Jember (PREMIUM)
        // ===========================================
        if ($routeJember && $vehiclePremium) {
            $schedule5 = Schedule::create([
                'agency_id' => $agencyJaya->id,
                'vehicle_id' => $vehiclePremium->id,
                'route_id' => $routeJember->id,
                'driver_id' => $driver1?->id,
                'departure_date' => $tomorrow->toDateString(),
                'departure_time' => '06:00',
                'travel_class' => 'premium',
                'max_overload' => 0,
                'price_per_seat' => 250000,
                'baggage_limit_kg' => 20.00,
                'is_active' => true,
                'allow_passenger_transfer' => true,
                'accept_external_transfer' => false,
                'transfer_fee_per_passenger' => 30000,
                'allow_cod' => false,
                'cod_min_balance' => 0,
                'started_at' => null,
                'finished_at' => null,
            ]);

            $this->createStopsAndPricing($schedule5, $routeJember, null, 5);
            echo "  ✅ Schedule 5: Besok, Sumenep→Jember (PREMIUM, Rp 250K)\n";

            // Pasang promo Jember Explorer nanti
            $this->schedule5 = $schedule5;
        } else {
            echo "  ⚠️ Gagal buat Schedule 5 (Jember)\n";
            $this->schedule5 = null;
        }

        // ===========================================
        // Schedule 6: Lusa, Sumenep→Jakarta (ECONOMY)
        // ===========================================
        if ($routeJakarta && $vehicleEconomy) {
            $schedule6 = Schedule::create([
                'agency_id' => $agencyJaya->id,
                'vehicle_id' => $vehicleEconomy->id,
                'route_id' => $routeJakarta->id,
                'driver_id' => $driver2?->id,
                'departure_date' => $dayAfter->toDateString(),
                'departure_time' => '05:00',
                'travel_class' => 'economy',
                'max_overload' => 2,
                'price_per_seat' => 350000,
                'baggage_limit_kg' => 25.00,
                'is_active' => true,
                'allow_passenger_transfer' => false,
                'accept_external_transfer' => false,
                'transfer_fee_per_passenger' => 0,
                'allow_cod' => true,
                'cod_min_balance' => 1000000,
                'started_at' => null,
                'finished_at' => null,
            ]);

            $this->createStopsAndPricing($schedule6, $routeJakarta, null, 5);
            echo "  ✅ Schedule 6: Lusa, Sumenep→Jakarta (ECONOMY, Rp 350K, COD)\n";
            $this->schedule6 = $schedule6;
        } else {
            echo "  ⚠️ Gagal buat Schedule 6 (Jakarta)\n";
            $this->schedule6 = null;
        }

        // ===========================================
        // Schedule 7: Lusa+3, Sumenep→Surabaya (PREMIUM)
        // ===========================================
        if ($routeSby && $vehiclePremium) {
            $schedule7 = Schedule::create([
                'agency_id' => $agencyJaya->id,
                'vehicle_id' => $vehiclePremium->id,
                'route_id' => $routeSby->id,
                'driver_id' => $driver1?->id,
                'departure_date' => $threeDays->toDateString(),
                'departure_time' => '09:00',
                'travel_class' => 'premium',
                'max_overload' => 0,
                'price_per_seat' => 200000,
                'baggage_limit_kg' => 20.00,
                'is_active' => true,
                'allow_passenger_transfer' => false,
                'accept_external_transfer' => false,
                'transfer_fee_per_passenger' => 0,
                'allow_cod' => true,
                'cod_min_balance' => 500000,
                'started_at' => null,
                'finished_at' => null,
            ]);

            $this->createStopsAndPricing($schedule7, $routeSby, [65000, 130000, 200000, 80000, 150000, 80000], 4);
            echo "  ✅ Schedule 7: Lusa+3, Sumenep→Surabaya (PREMIUM, Rp 200K, COD)\n";
            $this->schedule7 = $schedule7;
        } else {
            echo "  ⚠️ Gagal buat Schedule 7 (SBY Premium)\n";
            $this->schedule7 = null;
        }
    }

    /**
     * BAGIAN 4: Booking Baru untuk Budi & Ani
     */
    private function addNewBookingsForCustomers(): void
    {
        echo "\n🎫 TAMBAH BOOKING BARU...\n";

        $budi = User::where('email', 'budi@test.com')->first();
        $ani = User::where('email', 'ani@test.com')->first();

        if (!$budi || !$ani) {
            echo "  ⚠️ Customer tidak ditemukan\n";
            return;
        }

        // ===========================================
        // Booking 1: Budi → Jember (PAID, Midtrans)
        // ===========================================
        if ($this->schedule5) {
            $routeStops = $this->schedule5->route->stops()->orderBy('stop_order')->get();
            $pricing = RoutePricing::where('schedule_id', $this->schedule5->id)
                ->where('origin_stop_id', $routeStops[0]->id)
                ->where('destination_stop_id', $routeStops[4]->id)
                ->first();

            if ($pricing) {
                $booking1 = Booking::create([
                    'booking_code' => 'GM-' . now()->format('Ymd') . '-0500',
                    'schedule_id' => $this->schedule5->id,
                    'customer_id' => $budi->id,
                    'origin_stop_id' => $routeStops[0]->id,
                    'destination_stop_id' => $routeStops[4]->id,
                    'route_pricing_id' => $pricing->id,
                    'pickup_address' => 'Jl. Kartini No. 15, Sumenep',
                    'destination_address' => 'Jl. Gajah Mada No. 10, Jember',
                    'total_passengers' => 2,
                    'total_price' => 500000,
                    'status' => 'paid',
                ]);

                BookingPassenger::create([
                    'booking_id' => $booking1->id,
                    'passenger_name' => 'Budi Prasetyo',
                    'passenger_phone' => '081555555555',
                    'baggage_weight' => 15,
                    'seat_number' => 1,
                ]);
                BookingPassenger::create([
                    'booking_id' => $booking1->id,
                    'passenger_name' => 'Siti Aminah',
                    'passenger_phone' => '081555555556',
                    'baggage_weight' => 12,
                    'seat_number' => 2,
                ]);

                Payment::create([
                    'booking_id' => $booking1->id,
                    'amount' => 500000,
                    'commission' => 25000,
                    'agency_revenue' => 475000,
                    'payment_type' => 'midtrans',
                    'status' => 'paid',
                    'paid_at' => now()->subHours(3),
                ]);

                $this->bookingJember = $booking1;
                echo "  ✅ Booking Budi → Jember (PREMIUM, 2 pax, PAID, Midtrans)\n";
            }
        }

        // ===========================================
        // Booking 2: Budi → Jakarta (CANCELLED)
        // ===========================================
        if ($this->schedule6) {
            $routeStops = $this->schedule6->route->stops()->orderBy('stop_order')->get();
            $pricing = RoutePricing::where('schedule_id', $this->schedule6->id)
                ->where('origin_stop_id', $routeStops[0]->id)
                ->where('destination_stop_id', $routeStops[4]->id)
                ->first();

            if ($pricing) {
                $booking2 = Booking::create([
                    'booking_code' => 'GM-' . now()->format('Ymd') . '-0600',
                    'schedule_id' => $this->schedule6->id,
                    'customer_id' => $budi->id,
                    'origin_stop_id' => $routeStops[0]->id,
                    'destination_stop_id' => $routeStops[4]->id,
                    'route_pricing_id' => $pricing->id,
                    'pickup_address' => 'Jl. Kartini No. 15, Sumenep',
                    'destination_address' => 'Jl. Thamrin No. 1, Jakarta Pusat',
                    'total_passengers' => 1,
                    'total_price' => 350000,
                    'status' => 'cancelled',
                ]);

                BookingPassenger::create([
                    'booking_id' => $booking2->id,
                    'passenger_name' => 'Budi Prasetyo',
                    'passenger_phone' => '081555555555',
                    'baggage_weight' => 20,
                    'seat_number' => 1,
                ]);

                echo "  ✅ Booking Budi → Jakarta (ECONOMY, 1 pax, CANCELLED)\n";
            }
        }

        // ===========================================
        // Booking 3: Ani → Surabaya Premium (PAID, COD)
        // ===========================================
        if ($this->schedule7) {
            $routeStops = $this->schedule7->route->stops()->orderBy('stop_order')->get();
            $pricing = RoutePricing::where('schedule_id', $this->schedule7->id)
                ->where('origin_stop_id', $routeStops[0]->id)
                ->where('destination_stop_id', $routeStops[3]->id)
                ->first();

            if ($pricing) {
                $booking3 = Booking::create([
                    'booking_code' => 'GM-' . now()->format('Ymd') . '-0700',
                    'schedule_id' => $this->schedule7->id,
                    'customer_id' => $ani->id,
                    'origin_stop_id' => $routeStops[0]->id,
                    'destination_stop_id' => $routeStops[3]->id,
                    'route_pricing_id' => $pricing->id,
                    'pickup_address' => 'Jl. Diponegoro No. 5, Pamekasan',
                    'destination_address' => 'Jl. Raya Darmo No. 50, Surabaya',
                    'total_passengers' => 1,
                    'total_price' => 200000,
                    'status' => 'paid',
                ]);

                BookingPassenger::create([
                    'booking_id' => $booking3->id,
                    'passenger_name' => 'Ani Rahmawati',
                    'passenger_phone' => '081666666666',
                    'baggage_weight' => 18,
                    'seat_number' => 1,
                ]);

                Payment::create([
                    'booking_id' => $booking3->id,
                    'amount' => 200000,
                    'commission' => 10000,
                    'agency_revenue' => 190000,
                    'payment_type' => 'cod',
                    'status' => 'paid',
                    'paid_at' => now()->subHours(1),
                ]);

                $this->bookingSbyPremium = $booking3;
                echo "  ✅ Booking Ani → Surabaya (PREMIUM, 1 pax, PAID, COD)\n";
            }
        }
    }

    /**
     * BAGIAN 5: Perkaya Payment Agents (Verified Only)
     */
    private function enrichPaymentAgents(): void
    {
        echo "\n💰 PERKAYA PAYMENT AGENTS (VERIFIED)...\n";

        // Dapatkan semua payment agent VERIFIED
        $verifiedAgents = PaymentAgent::where('is_verified', true)->get();
        $bookingCount = 0;

        foreach ($verifiedAgents as $agent) {
            // Skip kalau udah banyak transaksi (Bu Sum & Pak Haji sudah punya)
            $existingCashCount = CashPayment::where('payment_agent_id', $agent->id)->count();
            
            if ($existingCashCount == 0 && $bookingCount < 3) {
                // Buat booking dummy untuk cash payment
                $schedule = Schedule::first();
                $customer = User::where('email', 'budi@test.com')->first();

                if ($schedule && $customer) {
                    $routeStops = $schedule->route->stops()->orderBy('stop_order')->get();
                    $pricing = RoutePricing::where('schedule_id', $schedule->id)
                        ->where('origin_stop_id', $routeStops[0]->id)
                        ->where('destination_stop_id', $routeStops->last()->id)
                        ->first();

                    if ($pricing) {
                        $booking = Booking::create([
                            'booking_code' => 'GM-' . now()->format('Ymd') . '-' . str_pad(9000 + $bookingCount, 4, '0', STR_PAD_LEFT),
                            'schedule_id' => $schedule->id,
                            'customer_id' => $customer->id,
                            'origin_stop_id' => $routeStops[0]->id,
                            'destination_stop_id' => $routeStops->last()->id,
                            'route_pricing_id' => $pricing->id,
                            'pickup_address' => 'Alamat Customer ' . ($bookingCount + 1),
                            'destination_address' => 'Tujuan Customer ' . ($bookingCount + 1),
                            'total_passengers' => 1,
                            'total_price' => 150000,
                            'status' => 'paid',
                        ]);

                        BookingPassenger::create([
                            'booking_id' => $booking->id,
                            'passenger_name' => 'Penumpang ' . ($bookingCount + 1),
                            'passenger_phone' => '08100000000' . $bookingCount,
                            'baggage_weight' => 10,
                            'seat_number' => 1,
                        ]);

                        // Cash payment confirmed
                        CashPayment::create([
                            'booking_id' => $booking->id,
                            'payment_agent_id' => $agent->id,
                            'payment_code' => 'WM-' . now()->format('Ymd') . '-' . strtoupper(substr(md5($agent->id . $bookingCount), 0, 6)),
                            'amount' => 150000,
                            'agent_commission' => 3000,
                            'platform_commission' => 7500,
                            'status' => 'confirmed',
                            'confirmed_at' => now()->subDays(rand(1, 5)),
                            'expired_at' => now()->subDays(rand(1, 5))->addHours(24),
                        ]);

                        Payment::create([
                            'booking_id' => $booking->id,
                            'amount' => 150000,
                            'commission' => 7500,
                            'agency_revenue' => 139500,
                            'payment_type' => 'cash',
                            'status' => 'paid',
                            'paid_at' => now()->subDays(rand(1, 5)),
                        ]);

                        // Update agent stats
                        $agent->update([
                            'total_transactions' => $agent->total_transactions + 1,
                            'total_commission' => $agent->total_commission + 3000,
                            'balance_to_settle' => $agent->balance_to_settle + 150000,
                        ]);

                        $bookingCount++;
                        echo "  ✅ {$agent->agent_name}: +1 Cash Payment (confirmed)\n";
                    }
                }
            }

            // Tambah settlement baru untuk agent yang belum punya settlement pending
            $existingSettlement = Settlement::where('payment_agent_id', $agent->id)
                ->where('status', 'pending')
                ->exists();

            if (!$existingSettlement && $bookingCount < 3) {
                Settlement::create([
                    'payment_agent_id' => $agent->id,
                    'period_start' => now()->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString(),
                    'period_end' => now()->subWeek()->startOfWeek(Carbon::MONDAY)->addDays(6)->toDateString(),
                    'total_transactions' => rand(3, 8),
                    'total_amount' => rand(500000, 1200000),
                    'total_commission' => rand(15000, 24000),
                    'amount_to_settle' => rand(485000, 1176000),
                    'status' => 'pending',
                ]);
                echo "  ✅ {$agent->agent_name}: +1 Settlement (pending)\n";
            }
        }
    }

    /**
     * BAGIAN 6: Tambah Promo & Usage
     */
    private function addPromoAndUsage(): void
    {
        echo "\n🏷️ TAMBAH PROMO & USAGE...\n";

        $admin = User::where('email', 'admin@gomad.id')->first();
        $budi = User::where('email', 'budi@test.com')->first();
        $ani = User::where('email', 'ani@test.com')->first();

        // ===========================================
        // Promo 1: Jember Explorer (Selective)
        // ===========================================
        $routeJember = Route::where('route_name', 'Sumenep - Jember')->first();
        
        $promoJember = Promo::firstOrCreate(
            ['name' => 'Jember Explorer'],
            [
                'type' => 'selective',
                'description' => 'Diskon 20% untuk rute Sumenep-Jember. Maksimal diskon Rp 50.000. Biaya ditanggung bersama platform & agency.',
                'discount_percent' => 20,
                'max_discount' => 50000,
                'min_purchase' => 200000,
                'route_id' => $routeJember?->id,
                'travel_class' => null,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(30)->toDateString(),
                'cost_bearer' => 'shared',
                'platform_share_percent' => 50,
                'agency_share_percent' => 50,
                'is_active' => true,
                'created_by' => $admin?->id ?? 1,
            ]
        );
        echo "  ✅ Promo: 'Jember Explorer' (Selective, 20%)\n";

        // Pasang di Schedule 5 (Jember)
        if ($this->schedule5 && $promoJember) {
            $this->schedule5->promos()->syncWithoutDetaching([$promoJember->id]);
            echo "  📎 Promo Jember Explorer dipasang di Schedule 5\n";
        }

        // ===========================================
        // Promo 2: Jakarta Long Haul (General)
        // ===========================================
        $promoJakarta = Promo::firstOrCreate(
            ['name' => 'Jakarta Long Haul'],
            [
                'type' => 'general',
                'description' => 'Diskon 15% untuk perjalanan jarak jauh ke Jakarta. Maksimal diskon Rp 100.000. Minimal pembelian Rp 300.000.',
                'discount_percent' => 15,
                'max_discount' => 100000,
                'min_purchase' => 300000,
                'route_id' => null,
                'travel_class' => null,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(14)->toDateString(),
                'cost_bearer' => 'platform',
                'platform_share_percent' => 100,
                'agency_share_percent' => 0,
                'is_active' => true,
                'created_by' => $admin?->id ?? 1,
            ]
        );
        echo "  ✅ Promo: 'Jakarta Long Haul' (General, 15%, max 100K)\n";

        // ===========================================
        // Promo Usage 1: Budi pakai Referral Reward
        // ===========================================
        if (isset($this->bookingJember) && $budi) {
            $referralPromo = Promo::where('name', 'Referral Reward - Budi')->first();
            if ($referralPromo) {
                PromoUsage::firstOrCreate(
                    [
                        'booking_id' => $this->bookingJember->id,
                        'promo_id' => $referralPromo->id,
                    ],
                    [
                        'user_id' => $budi->id,
                        'discount_amount' => 50000,
                    ]
                );
                echo "  ✅ Promo Usage: Budi pakai Referral Reward (diskon Rp 50K) di booking Jember\n";
            }
        }

        // ===========================================
        // Promo Usage 2: Ani pakai Diskon Natal
        // ===========================================
        if (isset($this->bookingSbyPremium) && $ani) {
            $promoNatal = Promo::where('name', 'Diskon Natal & Tahun Baru')->first();
            if ($promoNatal) {
                PromoUsage::firstOrCreate(
                    [
                        'booking_id' => $this->bookingSbyPremium->id,
                        'promo_id' => $promoNatal->id,
                    ],
                    [
                        'user_id' => $ani->id,
                        'discount_amount' => 30000,
                    ]
                );
                echo "  ✅ Promo Usage: Ani pakai Diskon Natal (diskon Rp 30K) di booking SBY Premium\n";
            }
        }
    }

    /**
     * HELPER: Buat schedule stops dan pricing
     */
    private function createStopsAndPricing(Schedule $schedule, Route $route, ?array $customPrices = null, int $expectedStops = 4): void
    {
        $routeStops = $route->stops()->orderBy('stop_order')->get();

        foreach ($routeStops as $index => $stop) {
            ScheduleStop::create([
                'schedule_id' => $schedule->id,
                'route_stop_id' => $stop->id,
                'is_pickup_available' => $index < count($routeStops) - 1,
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
     * HELPER: Dapatkan pasangan pricing
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
            // Route 5 stops: Sumenep, Pamekasan, Bangkalan, Probolinggo, Jember
            // ATAU Sumenep, Pamekasan, Surabaya, Semarang, Jakarta
            $defaults = [
                [0,1,50000],  // Sumenep → Pamekasan
                [0,2,100000], // Sumenep → Bangkalan/Surabaya
                [0,3,150000], // Sumenep → Probolinggo/Semarang
                [0,4,200000], // Sumenep → Jember/Jakarta
                [1,2,60000],  // Pamekasan → Bangkalan/Surabaya
                [1,3,110000], // Pamekasan → Probolinggo/Semarang
                [1,4,160000], // Pamekasan → Jember/Jakarta
                [2,3,60000],  // Bangkalan/Surabaya → Probolinggo/Semarang
                [2,4,110000], // Bangkalan/Surabaya → Jember/Jakarta
                [3,4,60000],  // Probolinggo/Semarang → Jember/Jakarta
            ];

            if ($customPrices) {
                foreach ($defaults as $i => $pair) {
                    if (isset($customPrices[$i])) $defaults[$i][2] = $customPrices[$i];
                }
            }
            return $defaults;
        }

        return [];
    }
}