<?php
// File: database/seeders/DemoDataSeeder.php
// Deskripsi: HYBRID DEMO DATA - Hardcoded (dikenal) + Factory (volume besar)

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\AgencyVerification;
use App\Models\AgencyWallet;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\CashPayment;
use App\Models\Payment;
use App\Models\PaymentAgent;
use App\Models\Promo;
use App\Models\PromoUsage;
use App\Models\ReferralCode;
use App\Models\Route;
use App\Models\RoutePricing;
use App\Models\Schedule;
use App\Models\ScheduleStop;
use App\Models\Settlement;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private $verifiedAgencies = [];
    private $unverifiedAgencies = [];
    private $verifiedAgents = [];
    private $pendingAgents = [];
    private $allCustomers = [];
    private $allSchedules = [];
    private $allPromos = [];

    public function run(): void
    {
        if (!app()->environment('local', 'staging')) {
            echo "⚠️  DemoDataSeeder hanya bisa dijalankan di local/staging!\n";
            return;
        }

        $startTime = microtime(true);

        echo "🚀 GENERATING HYBRID DEMO DATA...\n";
        echo "═══════════════════════════════════════════\n\n";

        $this->generateHardcodedAgencies();
        $this->generateHardcodedCustomers();
        $this->generateHardcodedPaymentAgents();
        $this->generateHardcodedSchedules();
        $this->generateHardcodedPromos();

        $this->generateFactoryCustomers();
        $this->generateFactoryAgencies();
        $this->generateFactoryPaymentAgents();
        $this->generateFactorySchedules();
        $this->generateFactoryBookings();
        $this->generateFactoryPromos();
        $this->generateFactoryTransactions();

        $duration = round(microtime(true) - $startTime, 2);

        echo "\n═══════════════════════════════════════════\n";
        echo "✅ DEMO DATA GENERATED SUCCESSFULLY!\n";
        echo "═══════════════════════════════════════════\n";
        echo "⏱️  Duration: {$duration} detik\n\n";

        echo "📊 TOTAL DATA GENERATED:\n";
        echo "──────────────────────────────────────────────\n";
        echo "👥 Customers: " . User::where('role', 'customer')->count() . "\n";
        echo "🏢 Agencies: " . Agency::count() . " (" . Agency::where('is_verified', true)->count() . " verified, " . Agency::where('is_verified', false)->count() . " unverified)\n";
        echo "🚗 Vehicles: " . Vehicle::count() . "\n";
        echo "🚗 Drivers: " . User::where('role', 'driver')->count() . "\n";
        echo "🏪 Payment Agents: " . PaymentAgent::count() . " (" . PaymentAgent::where('is_verified', true)->count() . " verified, " . PaymentAgent::where('is_verified', false)->count() . " pending)\n";
        echo "📅 Schedules: " . Schedule::count() . "\n";
        echo "🎫 Bookings: " . Booking::count() . "\n";
        echo "💳 Payments: " . Payment::count() . "\n";
        echo "🏷️  Promos: " . Promo::count() . "\n";
        echo "💰 Cash Payments: " . CashPayment::count() . "\n";
        echo "💵 Settlements: " . Settlement::count() . "\n";
        echo "──────────────────────────────────────────────\n\n";

        echo "🔑 LOGIN DEMO (DATA HARDCODED):\n";
        echo "──────────────────────────────────────────────\n";
        echo "Admin: admin@gomad.id / password\n";
        echo "Customer VIP: vip.citra@demo.id / password\n";
        echo "Customer VIP: vip.rafi@demo.id / password\n";
        echo "Agency: jayaabadi@test.com / password (Jaya Abadi)\n";
        echo "Agency: prima@demo.id / password (Prima Travel)\n";
        echo "Agency: elang@demo.id / password (Elang Express)\n";
        echo "Payment Agent: warungbusum@gomad.id / password / PIN: 123456\n";
        echo "Payment Agent: bukartini@demo.id / password / PIN: 111222\n";
        echo "──────────────────────────────────────────────\n";
    }

    // ╔══════════════════════════════════════════╗
    // ║     DATA HARDCODED (DIKENAL)            ║
    // ╚══════════════════════════════════════════╝

    private function generateHardcodedAgencies(): void
    {
        echo "🏢 GENERATING 5 HARDCODED AGENCIES...\n";

        $agencies = [
            [
                'user' => ['name' => 'Dirut Prima', 'email' => 'prima@demo.id', 'phone' => '081111000001'],
                'agency' => ['agency_name' => 'Prima Travel Indonesia', 'slug' => 'prima-travel', 'address' => 'Jl. Ahmad Yani No. 1, Sumenep', 'description' => 'Prima Travel Indonesia melayani perjalanan antar kota.', 'founded_year' => 2015, 'fleet_size' => 8, 'contact_person' => 'Dirut Prima', 'zone_coverage' => json_encode(['Sumenep', 'Surabaya', 'Malang', 'Jember']), 'is_verified' => true, 'rating' => 4.8, 'total_bookings' => 500],
            ],
            [
                'user' => ['name' => 'CEO Elang', 'email' => 'elang@demo.id', 'phone' => '081111000002'],
                'agency' => ['agency_name' => 'Elang Express', 'slug' => 'elang-express', 'address' => 'Jl. Diponegoro No. 25, Pamekasan', 'description' => 'Elang Express melayani perjalanan antar kota.', 'founded_year' => 2018, 'fleet_size' => 5, 'contact_person' => 'CEO Elang', 'zone_coverage' => json_encode(['Pamekasan', 'Surabaya', 'Jakarta']), 'is_verified' => true, 'rating' => 4.6, 'total_bookings' => 320],
            ],
            [
                'user' => ['name' => 'Owner Sentosa', 'email' => 'sentosa@demo.id', 'phone' => '081111000003'],
                'agency' => ['agency_name' => 'Sentosa Transport', 'slug' => 'sentosa-transport', 'address' => 'Jl. Trunojoyo No. 15, Bangkalan', 'description' => 'Sentosa Transport melayani perjalanan antar kota.', 'founded_year' => 2020, 'fleet_size' => 3, 'contact_person' => 'Owner Sentosa', 'zone_coverage' => json_encode(['Bangkalan', 'Surabaya', 'Malang']), 'is_verified' => true, 'rating' => 4.3, 'total_bookings' => 150],
            ],
            [
                'user' => ['name' => 'Direktur Cepat', 'email' => 'cepat@demo.id', 'phone' => '081111000004'],
                'agency' => ['agency_name' => 'Cepat Sampai Travel', 'slug' => 'cepat-sampai', 'address' => 'Jl. Raya Situbondo No. 8, Probolinggo', 'description' => 'Cepat Sampai Travel melayani perjalanan antar kota.', 'founded_year' => 2021, 'fleet_size' => 4, 'contact_person' => 'Direktur Cepat', 'zone_coverage' => json_encode(['Probolinggo', 'Jember', 'Surabaya']), 'is_verified' => true, 'rating' => 4.5, 'total_bookings' => 200],
            ],
            [
                'user' => ['name' => 'Bos Barokah', 'email' => 'barokah@demo.id', 'phone' => '081111000005'],
                'agency' => ['agency_name' => 'Barokah Group', 'slug' => 'barokah-group', 'address' => 'Jl. KH Mansyur No. 3, Sumenep', 'description' => 'Barokah Group melayani perjalanan antar kota.', 'founded_year' => 2023, 'fleet_size' => 2, 'contact_person' => 'Bos Barokah', 'zone_coverage' => json_encode(['Sumenep', 'Pamekasan']), 'is_verified' => false, 'rating' => 0, 'total_bookings' => 0],
            ],
        ];

        $vehicleBrands = [
            ['Toyota', 'Hiace Commuter', 8],
            ['Isuzu', 'ELF', 8],
            ['Suzuki', 'APV', 7],
            ['Toyota', 'Hiace Premio', 6],
        ];
        $vehicleTypes = ['economy', 'economy', 'economy', 'premium'];

        foreach ($agencies as $data) {
            $user = User::create([
                'name' => $data['user']['name'], 'email' => $data['user']['email'],
                'phone' => $data['user']['phone'], 'password' => Hash::make('password'),
                'role' => 'agency', 'is_active' => true, 'email_verified_at' => now(),
            ]);

            $agency = Agency::create(array_merge($data['agency'], ['user_id' => $user->id]));

            AgencyWallet::create([
                'agency_id' => $agency->id,
                'available_balance' => $data['agency']['is_verified'] ? rand(500000, 5000000) : 0,
                'pending_balance' => $data['agency']['is_verified'] ? rand(100000, 1000000) : 0,
                'total_earned' => $data['agency']['is_verified'] ? rand(5000000, 20000000) : 0,
                'total_withdrawn' => $data['agency']['is_verified'] ? rand(3000000, 15000000) : 0,
            ]);

            AgencyVerification::create([
                'agency_id' => $agency->id,
                'status' => $data['agency']['is_verified'] ? 'approved' : 'pending',
                'verified_at' => $data['agency']['is_verified'] ? now()->subDays(rand(30, 365)) : null,
                'verified_by' => $data['agency']['is_verified'] ? 1 : null,
            ]);

            for ($vi = 0; $vi < 4; $vi++) {
                $plateNumber = 'M ' . rand(1000, 9999) . ' ' . chr(rand(65, 90)) . chr(rand(65, 90));
                $attempt = 0;
                while (Vehicle::where('plate_number', $plateNumber)->exists() && $attempt < 100) {
                    $plateNumber = 'M ' . rand(1000, 9999) . ' ' . chr(rand(65, 90)) . chr(rand(65, 90));
                    $attempt++;
                }
                Vehicle::create([
                    'agency_id' => $agency->id, 'plate_number' => $plateNumber,
                    'brand' => $vehicleBrands[$vi][0], 'model' => $vehicleBrands[$vi][1],
                    'year' => rand(2020, 2024), 'capacity' => $vehicleBrands[$vi][2],
                    'type' => $vehicleTypes[$vi], 'is_active' => true,
                ]);
            }

            for ($d = 1; $d <= 2; $d++) {
                User::create([
                    'name' => fake('id_ID')->name('male'),
                    'email' => strtolower(explode('@', $data['user']['email'])[0]) . ".driver{$d}@demo.id",
                    'phone' => '08' . rand(100000000, 999999999), 'password' => Hash::make('password'),
                    'role' => 'driver', 'agency_id' => $agency->id, 'is_active' => true, 'email_verified_at' => now(),
                ]);
            }

            if ($data['agency']['is_verified']) {
                $this->verifiedAgencies[] = $agency->id;
            } else {
                $this->unverifiedAgencies[] = $agency->id;
            }

            echo "  ✅ {$data['agency']['agency_name']} (" . ($data['agency']['is_verified'] ? 'verified' : 'unverified') . ")\n";
        }
    }

    private function generateHardcodedCustomers(): void
    {
        echo "\n👥 GENERATING 10 HARDCODED CUSTOMERS...\n";

        $vipCustomers = [
            ['name' => 'Citra Dewi', 'email' => 'vip.citra@demo.id', 'phone' => '082111000001'],
            ['name' => 'Rafi Ahmad', 'email' => 'vip.rafi@demo.id', 'phone' => '082111000002'],
            ['name' => 'Sari Indah', 'email' => 'vip.sari@demo.id', 'phone' => '082111000003'],
            ['name' => 'Bambang Hartono', 'email' => 'vip.bambang@demo.id', 'phone' => '082111000004'],
            ['name' => 'Linda Kusuma', 'email' => 'vip.linda@demo.id', 'phone' => '082111000005'],
            ['name' => 'Dimas Arya', 'email' => 'vip.dimas@demo.id', 'phone' => '082111000006'],
            ['name' => 'Rina Marlina', 'email' => 'vip.rina@demo.id', 'phone' => '082111000007'],
            ['name' => 'Hendra Gunawan', 'email' => 'vip.hendra@demo.id', 'phone' => '082111000008'],
            ['name' => 'Sinta Nuria', 'email' => 'vip.sinta@demo.id', 'phone' => '082111000009'],
            ['name' => 'Andi Pratama', 'email' => 'vip.andi@demo.id', 'phone' => '082111000010'],
        ];

        foreach ($vipCustomers as $customer) {
            $user = User::create([
                'name' => $customer['name'], 'email' => $customer['email'],
                'phone' => $customer['phone'], 'password' => Hash::make('password'),
                'role' => 'customer', 'is_active' => true, 'email_verified_at' => now(),
            ]);
            $this->allCustomers[] = $user->id;

            if (count($this->allCustomers) <= 3) {
                ReferralCode::create([
                    'user_id' => $user->id,
                    'code' => strtoupper(explode(' ', $customer['name'])[0]) . rand(100, 999),
                    'total_referred' => rand(0, 5), 'successful_referrals' => rand(0, 3),
                ]);
            }
        }

        echo "  ✅ 10 VIP Customers + 3 Referral Codes\n";
    }

    private function generateHardcodedPaymentAgents(): void
    {
        echo "\n🏪 GENERATING 5 HARDCODED PAYMENT AGENTS...\n";

        $agents = [
            ['user' => ['name' => 'Bu Kartini', 'email' => 'bukartini@demo.id', 'phone' => '083111000001'], 'agent_name' => 'Toko Kartini', 'owner_name' => 'Bu Kartini', 'owner_phone' => '083111000001', 'address' => 'Jl. Pasar Baru No. 1, Sumenep', 'kecamatan' => 'Kota Sumenep', 'maps_link' => 'https://maps.google.com/?q=-7.0051,113.8586', 'latitude' => -7.0051, 'longitude' => 113.8586, 'pin' => '111222', 'is_verified' => true],
            ['user' => ['name' => 'Pak Sugeng', 'email' => 'paksugeng@demo.id', 'phone' => '083111000002'], 'agent_name' => 'Warung Sugeng', 'owner_name' => 'Pak Sugeng', 'owner_phone' => '083111000002', 'address' => 'Jl. Raya Pamekasan No. 45, Pamekasan', 'kecamatan' => 'Pamekasan', 'maps_link' => 'https://maps.google.com/?q=-7.1613,113.4825', 'latitude' => -7.1613, 'longitude' => 113.4825, 'pin' => '222333', 'is_verified' => true],
            ['user' => ['name' => 'Mbak Dewi', 'email' => 'dewi@demo.id', 'phone' => '083111000003'], 'agent_name' => 'Toko Dewi', 'owner_name' => 'Mbak Dewi', 'owner_phone' => '083111000003', 'address' => 'Jl. Merdeka No. 10, Bangkalan', 'kecamatan' => 'Bangkalan', 'maps_link' => 'https://maps.google.com/?q=-7.0307,112.7450', 'latitude' => -7.0307, 'longitude' => 112.7450, 'pin' => '333444', 'is_verified' => true],
            ['user' => ['name' => 'Pak Rahman', 'email' => 'rahman@demo.id', 'phone' => '083111000004'], 'agent_name' => 'Toko Rahman Jaya', 'owner_name' => 'Pak Rahman', 'owner_phone' => '083111000004', 'address' => 'Jl. Gajah Mada No. 20, Probolinggo', 'kecamatan' => 'Probolinggo', 'maps_link' => 'https://maps.google.com/?q=-7.7535,113.2160', 'latitude' => -7.7535, 'longitude' => 113.2160, 'pin' => '444555', 'is_verified' => true],
            ['user' => ['name' => 'Ibu Yanti', 'email' => 'yanti@demo.id', 'phone' => '083111000005'], 'agent_name' => 'Warung Yanti', 'owner_name' => 'Ibu Yanti', 'owner_phone' => '083111000005', 'address' => 'Jl. Kenanga No. 5, Surabaya', 'kecamatan' => 'Surabaya Pusat', 'maps_link' => 'https://maps.google.com/?q=-7.2575,112.7521', 'latitude' => -7.2575, 'longitude' => 112.7521, 'pin' => '555666', 'is_verified' => false],
        ];

        foreach ($agents as $data) {
            $user = User::create([
                'name' => $data['user']['name'], 'email' => $data['user']['email'],
                'phone' => $data['user']['phone'], 'password' => Hash::make('password'),
                'role' => 'payment_agent', 'is_active' => true, 'email_verified_at' => now(),
            ]);

            $agent = PaymentAgent::create([
                'user_id' => $user->id, 'agent_name' => $data['agent_name'],
                'owner_name' => $data['owner_name'], 'owner_phone' => $data['owner_phone'],
                'address' => $data['address'], 'kecamatan' => $data['kecamatan'],
                'maps_link' => $data['maps_link'], 'latitude' => $data['latitude'],
                'longitude' => $data['longitude'], 'pin' => Hash::make($data['pin']),
                'is_active' => true, 'is_verified' => $data['is_verified'],
                'guard_name' => fake()->name(), 'guard_phone' => '08' . rand(100000000, 999999999),
                'commission_rate' => 2.00,
                'total_transactions' => $data['is_verified'] ? rand(10, 100) : 0,
                'total_commission' => $data['is_verified'] ? rand(50000, 500000) : 0,
                'balance_to_settle' => $data['is_verified'] ? rand(100000, 2000000) : 0,
            ]);

            if ($data['is_verified']) {
                $this->verifiedAgents[] = $agent->id;
            } else {
                $this->pendingAgents[] = $agent->id;
            }

            echo "  ✅ {$data['agent_name']} (" . ($data['is_verified'] ? 'verified' : 'pending') . ") - PIN: {$data['pin']} | Login: {$data['user']['email']} / password\n";
        }
    }

    private function generateHardcodedSchedules(): void
    {
        echo "\n📅 GENERATING 5 HARDCODED SCHEDULES...\n";
        if (empty($this->verifiedAgencies)) { echo "  ⚠️ No verified agencies\n"; return; }

        $routes = Route::all();
        $flagshipSchedules = [
            ['route' => 'Sumenep - Surabaya', 'time' => '08:00', 'class' => 'economy', 'price' => 150000],
            ['route' => 'Sumenep - Surabaya', 'time' => '09:00', 'class' => 'premium', 'price' => 200000],
            ['route' => 'Sumenep - Malang', 'time' => '07:00', 'class' => 'economy', 'price' => 200000],
            ['route' => 'Sumenep - Jember', 'time' => '06:00', 'class' => 'premium', 'price' => 250000],
            ['route' => 'Sumenep - Jakarta', 'time' => '05:00', 'class' => 'economy', 'price' => 350000],
        ];

        foreach ($flagshipSchedules as $data) {
            $route = $routes->where('route_name', $data['route'])->first();
            if (!$route) continue;

            $agencyId = $this->verifiedAgencies[array_rand($this->verifiedAgencies)];
            $vehicle = Vehicle::where('agency_id', $agencyId)->where('type', $data['class'])->first() ?? Vehicle::where('agency_id', $agencyId)->first();
            if (!$vehicle) continue;

            $schedule = Schedule::create([
                'agency_id' => $agencyId, 'vehicle_id' => $vehicle->id, 'route_id' => $route->id,
                'driver_id' => User::where('agency_id', $agencyId)->where('role', 'driver')->first()?->id,
                'departure_date' => now()->addDays(rand(1, 7))->toDateString(),
                'departure_time' => $data['time'], 'travel_class' => $data['class'],
                'max_overload' => $data['class'] === 'premium' ? 0 : 2,
                'price_per_seat' => $data['price'],
                'baggage_limit_kg' => $data['class'] === 'premium' ? 20.00 : 15.00,
                'is_active' => true, 'allow_passenger_transfer' => true,
                'accept_external_transfer' => true, 'transfer_fee_per_passenger' => 20000,
                'allow_cod' => $data['class'] === 'economy', 'cod_min_balance' => 500000,
            ]);

            $this->createStopsAndPricing($schedule, $route);
            $this->allSchedules[] = $schedule->id;
            echo "  ✅ {$data['route']} ({$data['class']}, Rp {$data['price']})\n";
        }
    }

    private function generateHardcodedPromos(): void
    {
        echo "\n🏷️ GENERATING 5 HARDCODED PROMOS...\n";
        $adminId = User::where('email', 'admin@gomad.id')->first()?->id ?? 1;

        $promos = [
            ['name' => 'Diskon Lebaran 2026', 'type' => 'general', 'description' => 'Diskon spesial Lebaran 2026.', 'discount_percent' => 25, 'max_discount' => 75000, 'min_purchase' => 200000, 'cost_bearer' => 'platform', 'platform_share_percent' => 100, 'agency_share_percent' => 0, 'is_active' => true, 'start_date' => now()->subDays(5)->toDateString(), 'end_date' => now()->addDays(30)->toDateString()],
            ['name' => 'Flash Sale Jember', 'type' => 'selective', 'description' => 'Flash sale rute Sumenep-Jember.', 'discount_percent' => 30, 'max_discount' => 60000, 'min_purchase' => 150000, 'route_id' => Route::where('route_name', 'Sumenep - Jember')->first()?->id, 'travel_class' => null, 'cost_bearer' => 'shared', 'platform_share_percent' => 60, 'agency_share_percent' => 40, 'is_active' => true, 'start_date' => now()->toDateString(), 'end_date' => now()->addDays(14)->toDateString()],
            ['name' => 'Promo New Year', 'type' => 'general', 'description' => 'Sambut tahun baru dengan diskon 20%.', 'discount_percent' => 20, 'max_discount' => 50000, 'min_purchase' => 100000, 'cost_bearer' => 'platform', 'platform_share_percent' => 100, 'agency_share_percent' => 0, 'is_active' => true, 'start_date' => now()->addDays(15)->toDateString(), 'end_date' => now()->addDays(45)->toDateString()],
            ['name' => 'Weekend Special', 'type' => 'general', 'description' => 'Diskon 10% untuk weekend.', 'discount_percent' => 10, 'max_discount' => 30000, 'min_purchase' => 100000, 'cost_bearer' => 'platform', 'platform_share_percent' => 100, 'agency_share_percent' => 0, 'is_active' => true, 'start_date' => now()->toDateString(), 'end_date' => now()->addDays(90)->toDateString()],
            ['name' => 'Promo Premium Experience', 'type' => 'selective', 'description' => 'Diskon 15% untuk kelas premium.', 'discount_percent' => 15, 'max_discount' => 40000, 'min_purchase' => 150000, 'route_id' => Route::where('route_name', 'Sumenep - Malang')->first()?->id, 'travel_class' => 'premium', 'cost_bearer' => 'agency', 'platform_share_percent' => 0, 'agency_share_percent' => 100, 'is_active' => true, 'start_date' => now()->toDateString(), 'end_date' => now()->addDays(30)->toDateString()],
        ];

        foreach ($promos as $data) {
            $promo = Promo::create(array_merge($data, ['created_by' => $adminId]));
            $this->allPromos[] = $promo->id;
            echo "  ✅ {$data['name']} ({$data['type']}, {$data['discount_percent']}%)\n";
        }
    }

    // ╔══════════════════════════════════════════╗
    // ║     DATA FACTORY (VOLUME BESAR)          ║
    // ╚══════════════════════════════════════════╝

    private function generateFactoryCustomers(): void
    {
        echo "\n👥 GENERATING 100+ FACTORY CUSTOMERS...\n";
        $customers = [];
        for ($i = 0; $i < 100; $i++) {
            $customers[] = [
                'name' => fake('id_ID')->name(), 'email' => 'cust' . ($i + 1) . '@demo.id',
                'phone' => '08' . fake()->numerify('##########'), 'password' => Hash::make('password'),
                'role' => 'customer', 'is_active' => fake()->boolean(90),
                'email_verified_at' => fake()->boolean(80) ? now()->subDays(rand(1, 365)) : null,
                'created_at' => now()->subDays(rand(1, 365)), 'updated_at' => now(),
            ];
        }
        foreach (array_chunk($customers, 50) as $chunk) {
            User::insert($chunk);
        }
        $newCustomers = User::where('role', 'customer')->where('email', 'like', 'cust%@demo.id')->pluck('id')->toArray();
        $this->allCustomers = array_merge($this->allCustomers, $newCustomers);
        echo "  ✅ 100 Random Customers\n";
    }

    private function generateFactoryAgencies(): void
    {
        echo "\n🏢 GENERATING 45 FACTORY AGENCIES...\n";
        $agencyCount = 0;
        for ($i = 1; $i <= 45; $i++) {
            $isVerified = $i <= 35;
            $user = User::create([
                'name' => fake('id_ID')->name(), 'email' => "agency{$i}@demo.id",
                'phone' => '08' . fake()->numerify('##########'), 'password' => Hash::make('password'),
                'role' => 'agency', 'is_active' => true, 'email_verified_at' => now(),
            ]);

            $cities = ['Sumenep', 'Pamekasan', 'Bangkalan', 'Surabaya', 'Malang', 'Jember', 'Probolinggo', 'Jakarta'];
            $agency = Agency::create([
                'user_id' => $user->id, 'agency_name' => fake('id_ID')->company() . ' Travel',
                'slug' => 'agency-' . $i . '-' . fake()->slug(), 'address' => fake('id_ID')->address(),
                'description' => fake('id_ID')->paragraph(), 'founded_year' => rand(2015, 2024),
                'fleet_size' => rand(2, 10), 'contact_person' => $user->name,
                'zone_coverage' => json_encode(fake()->randomElements($cities, rand(2, 4))),
                'is_verified' => $isVerified,
                'rating' => $isVerified ? fake()->randomFloat(1, 3.5, 5.0) : 0,
                'total_bookings' => $isVerified ? rand(10, 500) : 0,
            ]);

            AgencyWallet::create([
                'agency_id' => $agency->id,
                'available_balance' => $isVerified ? rand(500000, 5000000) : 0,
                'pending_balance' => $isVerified ? rand(100000, 1000000) : 0,
                'total_earned' => $isVerified ? rand(5000000, 20000000) : 0,
                'total_withdrawn' => $isVerified ? rand(3000000, 15000000) : 0,
            ]);

            AgencyVerification::create([
                'agency_id' => $agency->id,
                'status' => $isVerified ? 'approved' : 'pending',
                'verified_at' => $isVerified ? now()->subDays(rand(30, 365)) : null,
                'verified_by' => $isVerified ? 1 : null,
            ]);

            for ($v = 0; $v < 4; $v++) {
                $plateNumber = 'M ' . rand(1000, 9999) . ' ' . chr(rand(65, 90)) . chr(rand(65, 90));
                $attempt = 0;
                while (Vehicle::where('plate_number', $plateNumber)->exists() && $attempt < 100) {
                    $plateNumber = 'M ' . rand(1000, 9999) . ' ' . chr(rand(65, 90)) . chr(rand(65, 90));
                    $attempt++;
                }
                Vehicle::create([
                    'agency_id' => $agency->id, 'plate_number' => $plateNumber,
                    'brand' => fake()->randomElement(['Toyota', 'Isuzu', 'Suzuki', 'Mitsubishi']),
                    'model' => fake()->randomElement(['Hiace', 'ELF', 'APV', 'Xpander']),
                    'year' => rand(2020, 2024), 'capacity' => rand(6, 8),
                    'type' => $v < 3 ? 'economy' : 'premium', 'is_active' => true,
                ]);
            }

            for ($d = 1; $d <= 2; $d++) {
                User::create([
                    'name' => fake('id_ID')->name('male'), 'email' => "agency{$i}.driver{$d}@demo.id",
                    'phone' => '08' . fake()->numerify('##########'), 'password' => Hash::make('password'),
                    'role' => 'driver', 'agency_id' => $agency->id, 'is_active' => true, 'email_verified_at' => now(),
                ]);
            }

            if ($isVerified) { $this->verifiedAgencies[] = $agency->id; }
            else { $this->unverifiedAgencies[] = $agency->id; }

            $agencyCount++;
            if ($i % 10 == 0) echo "  ✅ {$agencyCount}/45 agencies created\n";
        }
        echo "  ✅ 45 Random Agencies (35 verified, 10 unverified)\n";
    }

    private function generateFactoryPaymentAgents(): void
    {
        echo "\n🏪 GENERATING 45 FACTORY PAYMENT AGENTS...\n";
        for ($i = 1; $i <= 45; $i++) {
            $isVerified = $i <= 35;
            $user = User::create([
                'name' => fake('id_ID')->name(), 'email' => "agent{$i}@demo.id",
                'phone' => '08' . fake()->numerify('##########'), 'password' => Hash::make('password'),
                'role' => 'payment_agent', 'is_active' => true, 'email_verified_at' => now(),
            ]);

            $cities = ['Sumenep', 'Pamekasan', 'Bangkalan', 'Surabaya', 'Probolinggo', 'Jember', 'Malang'];
            $agent = PaymentAgent::create([
                'user_id' => $user->id, 'agent_name' => fake('id_ID')->company() . ' Payment',
                'owner_name' => $user->name, 'owner_phone' => $user->phone,
                'guard_name' => fake()->boolean(70) ? fake('id_ID')->name() : null,
                'guard_phone' => fake()->boolean(70) ? '08' . fake()->numerify('##########') : null,
                'address' => fake('id_ID')->address(), 'kecamatan' => fake()->randomElement($cities),
                'maps_link' => 'https://maps.google.com/?q=' . fake()->latitude(-8, -7) . ',' . fake()->longitude(112, 114),
                'latitude' => fake()->latitude(-8, -7), 'longitude' => fake()->longitude(112, 114),
                'pin' => Hash::make(rand(100000, 999999)), 'is_active' => true, 'is_verified' => $isVerified,
                'commission_rate' => 2.00,
                'total_transactions' => $isVerified ? rand(5, 50) : 0,
                'total_commission' => $isVerified ? rand(30000, 300000) : 0,
                'balance_to_settle' => $isVerified ? rand(50000, 1500000) : 0,
            ]);

            if ($isVerified) { $this->verifiedAgents[] = $agent->id; }
            else { $this->pendingAgents[] = $agent->id; }
        }
        echo "  ✅ 45 Random Payment Agents (35 verified, 10 pending)\n";
    }

    private function generateFactorySchedules(): void
    {
        echo "\n📅 GENERATING 900+ FACTORY SCHEDULES...\n";
        if (empty($this->verifiedAgencies)) { echo "  ⚠️ No verified agencies\n"; return; }

        $routes = Route::all();
        $scheduleCount = 0;

        for ($day = 0; $day < 30; $day++) {
            $date = now()->addDays($day)->toDateString();
            for ($i = 0; $i < 30; $i++) {
                $agencyId = $this->verifiedAgencies[array_rand($this->verifiedAgencies)];
                $route = $routes->random();
                $vehicle = Vehicle::where('agency_id', $agencyId)->inRandomOrder()->first();
                if (!$vehicle) continue;

                $class = $vehicle->type;
                $basePrice = $route->distance_km * 800;
                $priceMultiplier = $class === 'premium' ? 1.5 : 1.0;

                $schedule = Schedule::create([
                    'agency_id' => $agencyId, 'vehicle_id' => $vehicle->id, 'route_id' => $route->id,
                    'driver_id' => User::where('agency_id', $agencyId)->where('role', 'driver')->inRandomOrder()->first()?->id,
                    'departure_date' => $date,
                    'departure_time' => sprintf('%02d:%02d', rand(5, 20), rand(0, 11) * 5),
                    'travel_class' => $class,
                    'max_overload' => $class === 'premium' ? 0 : rand(0, 3),
                    'price_per_seat' => round($basePrice * $priceMultiplier, -3),
                    'baggage_limit_kg' => $class === 'premium' ? 20.00 : 15.00,
                    'is_active' => true,
                    'allow_passenger_transfer' => fake()->boolean(70),
                    'accept_external_transfer' => fake()->boolean(50),
                    'transfer_fee_per_passenger' => rand(15000, 30000),
                    'allow_cod' => $class === 'economy' && fake()->boolean(60),
                    'cod_min_balance' => 500000,
                ]);

                $this->createStopsAndPricing($schedule, $route);
                $this->allSchedules[] = $schedule->id;
                $scheduleCount++;
            }
            if ($day % 5 == 0) echo "  ✅ Day {$day}/30: {$scheduleCount} schedules created\n";
        }
        echo "  ✅ Total {$scheduleCount} Schedules (30 hari)\n";
    }

    private function generateFactoryBookings(): void
    {
        echo "\n🎫 GENERATING 500+ FACTORY BOOKINGS...\n";
        if (empty($this->allSchedules) || empty($this->allCustomers)) {
            echo "  ⚠️ No schedules or customers\n"; return;
        }

        $bookingCount = 0;
        $statuses = ['paid', 'paid', 'paid', 'paid', 'pending', 'pending', 'cancelled'];

        for ($i = 0; $i < 500; $i++) {
            $scheduleId = $this->allSchedules[array_rand($this->allSchedules)];
            $schedule = Schedule::find($scheduleId);
            if (!$schedule) continue;

            $customerId = $this->allCustomers[array_rand($this->allCustomers)];
            $routeStops = $schedule->route->stops()->orderBy('stop_order')->get();
            $originIdx = rand(0, count($routeStops) - 2);
            $destIdx = rand($originIdx + 1, count($routeStops) - 1);

            $pricing = RoutePricing::where('schedule_id', $scheduleId)
                ->where('origin_stop_id', $routeStops[$originIdx]->id)
                ->where('destination_stop_id', $routeStops[$destIdx]->id)->first();
            if (!$pricing) continue;

            $passengerCount = rand(1, 3);
            $status = fake()->randomElement($statuses);

            $booking = Booking::create([
                'booking_code' => 'GM-' . now()->format('Ymd') . '-' . str_pad($i + 1000, 4, '0', STR_PAD_LEFT),
                'schedule_id' => $scheduleId, 'customer_id' => $customerId,
                'origin_stop_id' => $routeStops[$originIdx]->id,
                'destination_stop_id' => $routeStops[$destIdx]->id,
                'route_pricing_id' => $pricing->id,
                'pickup_address' => fake('id_ID')->address(),
                'destination_address' => fake('id_ID')->address(),
                'total_passengers' => $passengerCount,
                'total_price' => $pricing->price * $passengerCount,
                'status' => $status,
            ]);

            for ($p = 1; $p <= $passengerCount; $p++) {
                BookingPassenger::create([
                    'booking_id' => $booking->id, 'passenger_name' => fake('id_ID')->name(),
                    'passenger_phone' => '08' . fake()->numerify('##########'),
                    'baggage_weight' => rand(5, 20), 'seat_number' => $p,
                ]);
            }

            if ($status === 'paid') {
                $paymentType = fake()->randomElement(['midtrans', 'midtrans', 'midtrans', 'cod', 'cash']);
                Payment::create([
                    'booking_id' => $booking->id, 'amount' => $booking->total_price,
                    'commission' => $booking->total_price * 0.05,
                    'agency_revenue' => $booking->total_price * 0.95,
                    'payment_type' => $paymentType, 'status' => 'paid',
                    'paid_at' => now()->subDays(rand(0, 30)),
                ]);

                if ($paymentType === 'cash' && !empty($this->verifiedAgents)) {
                    $agentId = $this->verifiedAgents[array_rand($this->verifiedAgents)];
                    $paymentCode = 'WM-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
                    $attempt = 0;
                    while (CashPayment::where('payment_code', $paymentCode)->exists() && $attempt < 100) {
                        $paymentCode = 'WM-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
                        $attempt++;
                    }
                    CashPayment::create([
                        'booking_id' => $booking->id, 'payment_agent_id' => $agentId,
                        'payment_code' => $paymentCode, 'amount' => $booking->total_price,
                        'agent_commission' => $booking->total_price * 0.02,
                        'platform_commission' => $booking->total_price * 0.05,
                        'status' => 'confirmed', 'confirmed_at' => now()->subDays(rand(0, 10)),
                        'expired_at' => now()->subDays(rand(0, 10))->addHours(24),
                    ]);
                }
            }

            $bookingCount++;
            if ($i % 100 == 0) echo "  ✅ {$bookingCount}/500 bookings created\n";
        }
        echo "  ✅ Total {$bookingCount} Bookings\n";
    }

    private function generateFactoryPromos(): void
    {
        echo "\n🏷️ GENERATING 25 FACTORY PROMOS...\n";
        $adminId = User::where('email', 'admin@gomad.id')->first()?->id ?? 1;
        $routes = Route::all();

        $promoNames = [
            'Flash Sale', 'Early Bird', 'Last Minute', 'Weekend Fun', 'Holiday Special',
            'Midnight Deal', 'Sunrise Promo', 'Travel Fair', 'Loyalty Reward', 'Group Discount',
            'Student Special', 'Family Package', 'Return Trip', 'Birthday Bonus', 'New Route Launch',
            'Rainy Season', 'Summer Vibes', 'Autumn Deal', 'Spring Travel', 'Winter Escape',
            'Island Hopper', 'City Explorer', 'Mountain Trip', 'Beach Getaway', 'Cultural Tour',
        ];

        foreach ($promoNames as $name) {
            $type = fake()->randomElement(['general', 'general', 'selective', 'selective', 'referral']);
            $routeId = $type === 'selective' ? $routes->random()->id : null;
            $costBearer = fake()->randomElement(['platform', 'shared', 'agency']);
            $promo = Promo::create([
                'name' => $name . ' ' . date('F Y'), 'type' => $type,
                'description' => "Promo {$name} untuk perjalanan GoMad.",
                'discount_percent' => rand(5, 35), 'max_discount' => rand(20000, 100000),
                'min_purchase' => rand(50000, 300000), 'route_id' => $routeId,
                'travel_class' => fake()->boolean(30) ? fake()->randomElement(['economy', 'premium']) : null,
                'start_date' => now()->subDays(rand(1, 10))->toDateString(),
                'end_date' => now()->addDays(rand(14, 60))->toDateString(),
                'cost_bearer' => $costBearer,
                'platform_share_percent' => $costBearer === 'platform' ? 100 : ($costBearer === 'shared' ? rand(40, 60) : 0),
                'agency_share_percent' => $costBearer === 'agency' ? 100 : ($costBearer === 'shared' ? rand(40, 60) : 0),
                'is_active' => fake()->boolean(80), 'created_by' => $adminId,
            ]);
            $this->allPromos[] = $promo->id;
        }
        echo "  ✅ 25 Random Promos\n";
    }

    private function generateFactoryTransactions(): void
    {
        echo "\n💰 GENERATING FACTORY TRANSACTIONS...\n";

        if (!empty($this->verifiedAgents)) {
            $settlementCount = 0;
            foreach ($this->verifiedAgents as $agentId) {
                for ($s = 0; $s < 2; $s++) {
                    $periodStart = now()->subWeeks($s + 1)->startOfWeek(Carbon::MONDAY)->toDateString();
                    $periodEnd = now()->subWeeks($s + 1)->startOfWeek(Carbon::MONDAY)->addDays(6)->toDateString();
                    $statuses = ['pending', 'paid', 'paid', 'verified'];
                    $status = fake()->randomElement($statuses);
                    Settlement::create([
                        'payment_agent_id' => $agentId, 'period_start' => $periodStart,
                        'period_end' => $periodEnd, 'total_transactions' => rand(3, 15),
                        'total_amount' => rand(500000, 3000000),
                        'total_commission' => rand(15000, 60000),
                        'amount_to_settle' => rand(485000, 2940000),
                        'status' => $status,
                        'payment_method' => $status !== 'pending' ? 'bank_transfer' : null,
                        'transaction_id' => $status !== 'pending' ? 'STL-' . strtoupper(Str::random(10)) : null,
                        'paid_at' => in_array($status, ['paid', 'verified']) ? now()->subDays(rand(1, 7)) : null,
                        'verified_at' => $status === 'verified' ? now()->subDays(rand(1, 3)) : null,
                        'verified_by' => $status === 'verified' ? 1 : null,
                    ]);
                    $settlementCount++;
                }
            }
            echo "  ✅ {$settlementCount} Settlements\n";
        }

        if (!empty($this->allPromos)) {
            $usageCount = 0;
            $paidBookings = Booking::where('status', 'paid')->inRandomOrder()->take(50)->get();
            foreach ($paidBookings as $booking) {
                if (fake()->boolean(30)) {
                    $promoId = $this->allPromos[array_rand($this->allPromos)];
                    $promo = Promo::find($promoId);
                    if ($promo) {
                        PromoUsage::create([
                            'promo_id' => $promoId, 'user_id' => $booking->customer_id,
                            'booking_id' => $booking->id,
                            'discount_amount' => min($promo->max_discount, $booking->total_price * $promo->discount_percent / 100),
                        ]);
                        $usageCount++;
                    }
                }
            }
            echo "  ✅ {$usageCount} Promo Usages\n";
        }
    }

    // ╔══════════════════════════════════════════╗
    // ║     HELPER METHODS                       ║
    // ╚══════════════════════════════════════════╝

    private function createStopsAndPricing(Schedule $schedule, Route $route): void
    {
        $routeStops = $route->stops()->orderBy('stop_order')->get();
        $stopCount = count($routeStops);

        foreach ($routeStops as $index => $stop) {
            ScheduleStop::create([
                'schedule_id' => $schedule->id, 'route_stop_id' => $stop->id,
                'is_pickup_available' => $index < $stopCount - 1,
                'is_dropoff_available' => $index > 0, 'estimated_time' => null,
            ]);
        }

        for ($i = 0; $i < $stopCount - 1; $i++) {
            for ($j = $i + 1; $j < $stopCount; $j++) {
                $distance = $routeStops[$j]->distance_from_origin - $routeStops[$i]->distance_from_origin;
                $price = round($distance * 800, -3);
                RoutePricing::create([
                    'schedule_id' => $schedule->id,
                    'origin_stop_id' => $routeStops[$i]->id,
                    'destination_stop_id' => $routeStops[$j]->id,
                    'price' => max($price, 50000),
                ]);
            }
        }
    }
}