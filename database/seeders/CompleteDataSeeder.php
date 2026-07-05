<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CompleteDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Starting Complete Data Seeder...');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $this->truncateAll();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        
        $this->seedPlatformSettings();
        $this->command->info('✅ Platform Settings');
        
        $admin = $this->seedAdmin();
        $this->command->info('✅ Admin');
        
        $agencies = $this->seedAgencies();
        $this->command->info('✅ Agencies (3)');
        
        $drivers = $this->seedDrivers($agencies);
        $this->command->info('✅ Drivers (6)');
        
        $vehicles = $this->seedVehicles($agencies);
        $this->command->info('✅ Vehicles (6)');
        
        $customers = $this->seedCustomers();
        $this->command->info('✅ Customers (10)');
        
        $paymentAgents = $this->seedPaymentAgents();
        $this->command->info('✅ Payment Agents (5)');
        
        $routes = $this->seedRoutes();
        $this->command->info('✅ Routes (3) with Stops');
        
        $schedules = $this->seedSchedules($agencies, $routes, $vehicles, $drivers);
        $this->command->info('✅ Schedules (6) with Pricing');
        
        $bookings = $this->seedBookings($schedules, $customers);
        $this->command->info('✅ Bookings (15) with Passengers');
        
        $payments = $this->seedPayments($bookings, $paymentAgents);
        $this->command->info('✅ Payments (15)');
        
        $settlements = $this->seedSettlements($paymentAgents);
        $this->command->info('✅ Settlements (3)');
        
        $withdrawals = $this->seedWithdrawals($agencies);
        $this->command->info('✅ Withdrawals (2)');
        
        $promos = $this->seedPromos($admin, $routes, $customers);
        $this->command->info('✅ Promos (4)');
        
        $reviews = $this->seedReviews($bookings, $agencies, $customers);
        $this->command->info('✅ Reviews (5)');
        
        $transfers = $this->seedPassengerTransfers($schedules, $bookings);
        $this->command->info('✅ Passenger Transfers (2)');
        
        $wallets = $this->seedWallets($agencies, $bookings);
        $this->command->info('✅ Wallets (3) with Transactions');
        
        $this->seedReferralCodes($customers);
        $this->command->info('✅ Referral Codes & Tracking');
        
        $this->seedNotifications($admin, $agencies, $customers, $bookings);
        $this->command->info('✅ Notifications');
        
        $this->command->info('');
        $this->command->info('🎉 COMPLETE DATA SEEDER FINISHED!');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('📧 Admin: admin@gomad.id / password');
        $this->command->info('🏢 Agency 1: jayaabadi@gomad.id / password');
        $this->command->info('🏢 Agency 2: maduratrans@gomad.id / password');
        $this->command->info('🏢 Agency 3: suroboyoshuttle@gomad.id / password');
        $this->command->info('👨‍✈️ Driver 1: driver1@jayaabadi.com / password');
        $this->command->info('🧑 Customer: budi@test.com / password');
        $this->command->info('🧑 Customer: ani@test.com / password');
        $this->command->info('🏪 Warung: warung_berkah@gomad.id / password (PIN: 123456)');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    }

    private function truncateAll(): void
    {
        $tables = [
            'wallet_transactions', 'withdrawals', 'notifications',
            'passenger_transfer_bookings', 'passenger_transfers',
            'promo_usages', 'referral_trackings', 'referral_codes',
            'promo_schedule', 'promos', 'reviews',
            'cash_payments', 'payments', 'booking_passengers', 'bookings',
            'settlements', 'driver_locations', 'payment_agents',
            'route_pricing', 'schedule_stops', 'schedules',
            'user_devices', 'platform_settings', 'pickup_zones',
            'agency_wallets', 'agency_verifications', 'vehicles',
            'agencies', 'personal_access_tokens',
            'failed_jobs', 'job_batches', 'jobs',
            'cache_locks', 'cache', 'sessions', 'password_reset_tokens',
            'users', 'route_stops', 'routes',
        ];
        
        foreach ($tables as $table) {
            try { DB::table($table)->truncate(); } catch (\Exception $e) {}
        }
    }

    // ═══════════════════════════════════════════════════════
    // PLATFORM SETTINGS
    // ═══════════════════════════════════════════════════════
    
    private function seedPlatformSettings(): void
    {
        $settings = [
            ['key' => 'commission_rate', 'value' => '5', 'description' => 'Komisi platform (%)'],
            ['key' => 'warung_commission_rate', 'value' => '2', 'description' => 'Komisi warung (%)'],
            ['key' => 'payment_timeout', 'value' => '30', 'description' => 'Timeout pembayaran (menit)'],
            ['key' => 'schedule_min_days', 'value' => '1', 'description' => 'Minimal hari sebelum keberangkatan'],
            ['key' => 'minimal_withdrawal', 'value' => '100000', 'description' => 'Minimal penarikan (Rp)'],
            ['key' => 'withdrawal_admin_fee', 'value' => '5000', 'description' => 'Biaya admin penarikan (Rp)'],
            ['key' => 'auto_approve_limit', 'value' => '5000000', 'description' => 'Batas auto-approve withdrawal (Rp)'],
            ['key' => 'topup_admin_fee', 'value' => '3500', 'description' => 'Biaya admin top up (Rp)'],
            ['key' => 'service_fee', 'value' => '5000', 'description' => 'Biaya layanan per booking (Rp)'],
            ['key' => 'platform_fee_percent', 'value' => '3', 'description' => 'Biaya platform (%)'],
            ['key' => 'payment_code_expiry_hours', 'value' => '24', 'description' => 'Expiry kode bayar (jam)'],
            ['key' => 'support_phone', 'value' => '081234567890', 'description' => 'Nomor support'],
            ['key' => 'support_email', 'value' => 'support@gomad.id', 'description' => 'Email support'],
        ];
        
        foreach ($settings as $s) {
            DB::table('platform_settings')->insert(array_merge($s, [
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    // ═══════════════════════════════════════════════════════
    // ADMIN
    // ═══════════════════════════════════════════════════════
    
    private function seedAdmin(): object
    {
        $adminId = DB::table('users')->insertGetId([
            'name' => 'Admin GoMad',
            'email' => 'admin1@gomad.id',
            'phone' => '081111111111',
            'password' => Hash::make('sandiagomad'),
            'role' => 'admin',
            'is_active' => true,
            'created_at' => now()->subMonths(6),
            'updated_at' => now(),
        ]);
        
        return (object) ['id' => $adminId, 'name' => 'Admin GoMad'];
    }

    // ═══════════════════════════════════════════════════════
    // AGENCIES
    // ═══════════════════════════════════════════════════════
    
    private function seedAgencies(): array
    {
        $agencyData = [
            [
                'user_name' => 'H. Ahmad Jaya',
                'user_email' => 'jayaabadi@gomad.id',
                'user_phone' => '087877777777',
                'agency_name' => 'Travel Jaya Abadi',
                'slug' => 'travel-jaya-abadi',
                'address' => 'Jl. Trunojoyo No. 45, Sumenep',
                'description' => 'Travel Jaya Abadi melayani transportasi antar kota di Madura dengan pengalaman 10+ tahun. Armada bersih, driver profesional, tepat waktu.',
                'founded_year' => 2014,
                'contact_person' => 'H. Ahmad Jaya',
                'contact_alternate' => '087877777777',
                'email_alternate' => 'cs@jayaabadi.com',
                'rating' => 4.50,
                'total_bookings' => 150,
                'fleet_size' => 5,
            ],
            [
                'user_name' => 'Ibu Siti Rahayu',
                'user_email' => 'maduratrans@gomad.id',
                'user_phone' => '087866666666',
                'agency_name' => 'Madura Trans',
                'slug' => 'madura-trans',
                'address' => 'Jl. Raya Pamekasan No. 88, Pamekasan',
                'description' => 'Madura Trans melayani perjalanan antar kota dengan armada terbaru. Kenyamanan dan keamanan penumpang adalah prioritas kami.',
                'founded_year' => 2018,
                'contact_person' => 'Ibu Siti Rahayu',
                'contact_alternate' => '087866666666',
                'email_alternate' => 'info@maduratrans.com',
                'rating' => 4.20,
                'total_bookings' => 89,
                'fleet_size' => 3,
            ],
            [
                'user_name' => 'Bpk. Budi Santoso',
                'user_email' => 'suroboyoshuttle@gomad.id',
                'user_phone' => '087855555555',
                'agency_name' => 'Suroboyo Shuttle',
                'slug' => 'suroboyo-shuttle',
                'address' => 'Jl. Ahmad Yani No. 12, Surabaya',
                'description' => 'Suroboyo Shuttle adalah layanan shuttle premium yang menghubungkan Surabaya dengan kota-kota di Madura dan sekitarnya.',
                'founded_year' => 2020,
                'contact_person' => 'Bpk. Budi Santoso',
                'contact_alternate' => '087855555555',
                'email_alternate' => 'cs@suroboyoshuttle.com',
                'rating' => 4.80,
                'total_bookings' => 200,
                'fleet_size' => 6,
            ],
        ];

        $agencies = [];
        
        foreach ($agencyData as $data) {
            $userId = DB::table('users')->insertGetId([
                'name' => $data['user_name'],
                'email' => $data['user_email'],
                'phone' => $data['user_phone'],
                'password' => Hash::make('sandiagomad'),
                'role' => 'agency',
                'is_active' => true,
                'created_at' => now()->subMonths(6),
                'updated_at' => now(),
            ]);

            $agencyId = DB::table('agencies')->insertGetId([
                'user_id' => $userId,
                'agency_name' => $data['agency_name'],
                'slug' => $data['slug'],
                'address' => $data['address'],
                'description' => $data['description'],
                'founded_year' => $data['founded_year'],
                'fleet_size' => $data['fleet_size'],
                'contact_person' => $data['contact_person'],
                'contact_alternate' => $data['contact_alternate'],
                'email_alternate' => $data['email_alternate'],
                'is_verified' => true,
                'rating' => $data['rating'],
                'total_bookings' => $data['total_bookings'],
                'services' => json_encode(['bagasi_ekstra' => true, 'charger' => true, 'air_mineral' => true, 'wifi' => true]),
                'social_media' => json_encode(['facebook' => 'https://facebook.com/' . $data['slug'], 'instagram' => 'https://instagram.com/' . $data['slug']]),
                'business_hours' => json_encode(['senin' => '06:00-20:00', 'selasa' => '06:00-20:00', 'rabu' => '06:00-20:00', 'kamis' => '06:00-20:00', 'jumat' => '06:00-18:00', 'sabtu' => '06:00-20:00', 'minggu' => '07:00-18:00']),
                'zone_coverage' => json_encode(['Sumenep', 'Pamekasan', 'Bangkalan', 'Surabaya']),
                'created_at' => now()->subMonths(6),
                'updated_at' => now(),
            ]);

            DB::table('agency_verifications')->insert([
                'agency_id' => $agencyId,
                'verified_by' => 1,
                'status' => 'approved',
                'verified_at' => now()->subMonths(6),
                'created_at' => now()->subMonths(6),
                'updated_at' => now(),
            ]);

            $agencies[] = (object) [
                'id' => $agencyId, 'user_id' => $userId,
                'agency_name' => $data['agency_name'], 'rating' => $data['rating'],
            ];
        }

        return $agencies;
    }

    // ═══════════════════════════════════════════════════════
    // DRIVERS
    // ═══════════════════════════════════════════════════════
    
    private function seedDrivers(array $agencies): array
    {
        $driverData = [
            ['name' => 'Supriyadi', 'phone' => '081111111112'],
            ['name' => 'Herman', 'phone' => '081111111113'],
            ['name' => 'Rudi Hartono', 'phone' => '081111111114'],
            ['name' => 'Agus Salim', 'phone' => '081111111115'],
            ['name' => 'Bambang Sutejo', 'phone' => '081111111116'],
            ['name' => 'Dedi Kurniawan', 'phone' => '081111111117'],
        ];

        $drivers = [];
        $emailDomains = ['@jayaabadi.com', '@jayaabadi.com', '@maduratrans.com', '@maduratrans.com', '@suroboyoshuttle.com', '@suroboyoshuttle.com'];
        
        for ($i = 0; $i < 6; $i++) {
            $agencyIdx = intdiv($i, 2);
            $agency = $agencies[$agencyIdx];
            
            $driverId = DB::table('users')->insertGetId([
                'name' => $driverData[$i]['name'],
                'email' => 'driver' . ($i + 1) . $emailDomains[$i],
                'phone' => $driverData[$i]['phone'],
                'password' => Hash::make('sandiagomad'),
                'role' => 'driver',
                'agency_id' => $agency->id,
                'is_active' => true,
                'created_at' => now()->subMonths(3),
                'updated_at' => now(),
            ]);

            $drivers[] = (object) ['id' => $driverId, 'agency_id' => $agency->id, 'name' => $driverData[$i]['name']];
        }

        return $drivers;
    }

    // ═══════════════════════════════════════════════════════
    // VEHICLES
    // ═══════════════════════════════════════════════════════
    
    private function seedVehicles(array $agencies): array
    {
        $vehicleData = [
            ['plate' => 'M 1234 AB', 'brand' => 'Toyota', 'model' => 'Hiace Commuter', 'year' => 2022, 'capacity' => 8, 'type' => 'economy'],
            ['plate' => 'M 5678 CD', 'brand' => 'Isuzu', 'model' => 'Elf', 'year' => 2023, 'capacity' => 8, 'type' => 'economy'],
            ['plate' => 'M 9012 EF', 'brand' => 'Toyota', 'model' => 'Hiace Premio', 'year' => 2023, 'capacity' => 8, 'type' => 'premium'],
            ['plate' => 'M 3456 GH', 'brand' => 'Mitsubishi', 'model' => 'L300', 'year' => 2021, 'capacity' => 8, 'type' => 'economy'],
            ['plate' => 'L 7890 IJ', 'brand' => 'Toyota', 'model' => 'Alphard', 'year' => 2024, 'capacity' => 7, 'type' => 'premium'],
            ['plate' => 'L 1121 KL', 'brand' => 'Mercedes', 'model' => 'Sprinter', 'year' => 2023, 'capacity' => 10, 'type' => 'premium'],
        ];

        $vehicles = [];
        
        for ($i = 0; $i < 6; $i++) {
            $agency = $agencies[intdiv($i, 2)];
            $vData = $vehicleData[$i];
            
            $vehicleId = DB::table('vehicles')->insertGetId([
                'agency_id' => $agency->id,
                'plate_number' => $vData['plate'],
                'brand' => $vData['brand'],
                'model' => $vData['model'],
                'year' => $vData['year'],
                'capacity' => $vData['capacity'],
                'type' => $vData['type'],
                'is_active' => true,
                'created_at' => now()->subMonths(4),
                'updated_at' => now(),
            ]);

            $vehicles[] = (object) array_merge(['id' => $vehicleId, 'agency_id' => $agency->id], $vData);
        }

        return $vehicles;
    }

    // ═══════════════════════════════════════════════════════
    // CUSTOMERS
    // ═══════════════════════════════════════════════════════
    
    private function seedCustomers(): array
    {
        $customerData = [
            ['name' => 'Budi Santoso', 'email' => 'budi@test.com', 'phone' => '081200000001', 'type' => 'referrer'],
            ['name' => 'Ani Rahmawati', 'email' => 'ani@test.com', 'phone' => '081200000002', 'type' => 'referrer'],
            ['name' => 'Citra Dewi', 'email' => 'citra@test.com', 'phone' => '081200000003', 'type' => 'referred_by_budi'],
            ['name' => 'Dodi Prasetyo', 'email' => 'dodi@test.com', 'phone' => '081200000004', 'type' => 'referred_by_ani'],
            ['name' => 'Eka Putri', 'email' => 'eka@test.com', 'phone' => '081200000005', 'type' => 'regular'],
            ['name' => 'Faisal Rahman', 'email' => 'faisal@test.com', 'phone' => '081200000006', 'type' => 'regular'],
            ['name' => 'Gina Amelia', 'email' => 'gina@test.com', 'phone' => '081200000007', 'type' => 'regular'],
            ['name' => 'Hendra Gunawan', 'email' => 'hendra@test.com', 'phone' => '081200000008', 'type' => 'regular'],
            ['name' => 'Indah Sari', 'email' => 'indah@test.com', 'phone' => '081200000009', 'type' => 'regular'],
            ['name' => 'Joko Widodo', 'email' => 'joko@test.com', 'phone' => '081200000010', 'type' => 'regular'],
        ];

        $customers = [];
        
        foreach ($customerData as $data) {
            $customerId = DB::table('users')->insertGetId([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make('sandiagomad'),
                'role' => 'customer',
                'is_active' => true,
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now(),
            ]);

            $customers[] = (object) [
                'id' => $customerId, 'name' => $data['name'],
                'email' => $data['email'], 'phone' => $data['phone'],
                'type' => $data['type'],
            ];
        }

        return $customers;
    }

    // ═══════════════════════════════════════════════════════
    // PAYMENT AGENTS (Warung GoMad)
    // ═══════════════════════════════════════════════════════
    
    private function seedPaymentAgents(): array
    {
        $agentData = [
            ['name' => 'Warung Berkah', 'owner' => 'Pak Rahmat', 'phone' => '081300000001', 'address' => 'Jl. Diponegoro No. 10, Sumenep', 'kec' => 'Kota Sumenep', 'lat' => -7.0089, 'lng' => 113.8586],
            ['name' => 'Toko Makmur', 'owner' => 'Bu Fatimah', 'phone' => '081300000002', 'address' => 'Jl. Panglima Sudirman No. 25, Pamekasan', 'kec' => 'Kota Pamekasan', 'lat' => -7.1565, 'lng' => 113.4747],
            ['name' => 'Warung Sumber Rejeki', 'owner' => 'Pak Hadi', 'phone' => '081300000003', 'address' => 'Jl. KH. Moh. Kholil No. 5, Bangkalan', 'kec' => 'Kota Bangkalan', 'lat' => -7.0303, 'lng' => 112.7465],
            ['name' => 'Toko Kelontong Jaya', 'owner' => 'Ibu Rina', 'phone' => '081300000004', 'address' => 'Jl. Raya Dukuh Kupang No. 30, Surabaya', 'kec' => 'Dukuh Pakis', 'lat' => -7.2845, 'lng' => 112.7142],
            ['name' => 'Warung Madura Asli', 'owner' => 'H. Syamsul', 'phone' => '081300000005', 'address' => 'Jl. Trunojoyo No. 99, Sumenep', 'kec' => 'Kota Sumenep', 'lat' => -7.0100, 'lng' => 113.8600],
        ];

        $agents = [];
        
        foreach ($agentData as $data) {
            $userId = DB::table('users')->insertGetId([
                'name' => $data['owner'],
                'email' => strtolower(str_replace(' ', '_', $data['name'])) . '@gomad.id',
                'phone' => $data['phone'],
                'password' => Hash::make('sandiagomad'),
                'role' => 'payment_agent',
                'is_active' => true,
                'created_at' => now()->subMonths(2),
                'updated_at' => now(),
            ]);

            $agentId = DB::table('payment_agents')->insertGetId([
                'user_id' => $userId,
                'agent_name' => $data['name'],
                'owner_name' => $data['owner'],
                'owner_phone' => $data['phone'],
                'address' => $data['address'],
                'kecamatan' => $data['kec'],
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'pin' => Hash::make('123456'),
                'is_active' => true,
                'is_verified' => true,
                'commission_rate' => 2.00,
                'total_transactions' => rand(10, 50),
                'total_commission' => rand(50000, 200000),
                'balance_to_settle' => rand(0, 100000),
                'created_at' => now()->subMonths(2),
                'updated_at' => now(),
            ]);

            $agents[] = (object) ['id' => $agentId, 'name' => $data['name']];
        }

        return $agents;
    }

    // ═══════════════════════════════════════════════════════
    // ROUTES
    // ═══════════════════════════════════════════════════════
    
    private function seedRoutes(): array
    {
        $routeData = [
            [
                'route_name' => 'Sumenep - Surabaya',
                'origin_city' => 'Sumenep', 'destination_city' => 'Surabaya',
                'distance_km' => 175, 'estimated_duration' => 300,
                'max_price' => 250000, 'cod_available' => true, 'cod_min_deposit' => 500000,
                'payment_methods' => 'midtrans,cash,cod',
                'stops' => [
                    ['Sumenep', 1], ['Pamekasan', 2], ['Bangkalan', 3], ['Surabaya', 4],
                ],
            ],
            [
                'route_name' => 'Pamekasan - Malang',
                'origin_city' => 'Pamekasan', 'destination_city' => 'Malang',
                'distance_km' => 200, 'estimated_duration' => 360,
                'max_price' => 300000, 'cod_available' => true, 'cod_min_deposit' => 750000,
                'payment_methods' => 'midtrans,cash',
                'stops' => [
                    ['Pamekasan', 1], ['Surabaya', 2], ['Sidoarjo', 3], ['Malang', 4],
                ],
            ],
            [
                'route_name' => 'Bangkalan - Jember',
                'origin_city' => 'Bangkalan', 'destination_city' => 'Jember',
                'distance_km' => 250, 'estimated_duration' => 420,
                'max_price' => 350000, 'cod_available' => false, 'cod_min_deposit' => 0,
                'payment_methods' => 'midtrans,cash',
                'stops' => [
                    ['Bangkalan', 1], ['Surabaya', 2], ['Probolinggo', 3], ['Jember', 4],
                ],
            ],
        ];

        $coords = [
            'Sumenep' => [-7.0089, 113.8586], 'Pamekasan' => [-7.1565, 113.4747],
            'Bangkalan' => [-7.0303, 112.7465], 'Surabaya' => [-7.2575, 112.7521],
            'Sidoarjo' => [-7.4478, 112.7183], 'Malang' => [-7.9839, 112.6214],
            'Probolinggo' => [-7.7547, 113.2155], 'Jember' => [-8.1683, 113.7022],
        ];

        $routes = [];

        foreach ($routeData as $rData) {
            $routeId = DB::table('routes')->insertGetId([
                'route_name' => $rData['route_name'],
                'origin_city' => $rData['origin_city'],
                'destination_city' => $rData['destination_city'],
                'distance_km' => $rData['distance_km'],
                'estimated_duration' => $rData['estimated_duration'],
                'max_price' => $rData['max_price'],
                'cod_available' => $rData['cod_available'],
                'cod_min_deposit' => $rData['cod_min_deposit'],
                'payment_methods' => $rData['payment_methods'],
                'is_active' => true,
                'created_at' => now()->subMonths(5),
                'updated_at' => now(),
            ]);

            $stops = [];
            foreach ($rData['stops'] as $idx => $stop) {
                $cityName = $stop[0];
                $stopOrder = $stop[1];
                $coord = $coords[$cityName] ?? [0, 0];
                
                $stopId = DB::table('route_stops')->insertGetId([
                    'route_id' => $routeId,
                    'city_name' => $cityName,
                    'stop_order' => $stopOrder,
                    'latitude' => $coord[0],
                    'longitude' => $coord[1],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $stops[] = (object) ['id' => $stopId, 'route_id' => $routeId, 'city_name' => $cityName, 'stop_order' => $stopOrder];
            }

            $routes[] = (object) [
                'id' => $routeId,
                'route_name' => $rData['route_name'],
                'origin_city' => $rData['origin_city'],
                'destination_city' => $rData['destination_city'],
                'cod_available' => $rData['cod_available'],
                'cod_min_deposit' => $rData['cod_min_deposit'],
                'payment_methods' => $rData['payment_methods'],
                'stops' => $stops,
            ];
        }

        return $routes;
    }

    // ═══════════════════════════════════════════════════════
    // SCHEDULES
    // ═══════════════════════════════════════════════════════
    
    private function seedSchedules(array $agencies, array $routes, array $vehicles, array $drivers): array
    {
        $scheduleData = [
            ['route' => 0, 'agency' => 0, 'vehicle' => 0, 'driver' => 0, 'days' => 0, 'time' => '08:00', 'class' => 'economy', 'price' => 130000, 'cod' => true],
            ['route' => 0, 'agency' => 0, 'vehicle' => 1, 'driver' => 1, 'days' => 2, 'time' => '10:00', 'class' => 'economy', 'price' => 130000, 'cod' => true],
            ['route' => 0, 'agency' => 2, 'vehicle' => 4, 'driver' => 4, 'days' => 1, 'time' => '13:00', 'class' => 'premium', 'price' => 200000, 'cod' => false],
            ['route' => 1, 'agency' => 1, 'vehicle' => 2, 'driver' => 2, 'days' => 0, 'time' => '07:00', 'class' => 'premium', 'price' => 180000, 'cod' => false],
            ['route' => 1, 'agency' => 1, 'vehicle' => 3, 'driver' => 3, 'days' => 3, 'time' => '14:00', 'class' => 'economy', 'price' => 160000, 'cod' => false],
            ['route' => 2, 'agency' => 2, 'vehicle' => 5, 'driver' => 5, 'days' => 1, 'time' => '09:00', 'class' => 'premium', 'price' => 250000, 'cod' => false],
        ];

        $schedules = [];

        foreach ($scheduleData as $sData) {
            $route = $routes[$sData['route']];
            $agency = $agencies[$sData['agency']];
            $vehicle = $vehicles[$sData['vehicle']];
            $driver = $drivers[$sData['driver']];
            $departureDate = now()->addDays($sData['days']);
            
            $scheduleId = DB::table('schedules')->insertGetId([
                'agency_id' => $agency->id, 'vehicle_id' => $vehicle->id,
                'route_id' => $route->id, 'driver_id' => $driver->id,
                'departure_date' => $departureDate->toDateString(),
                'departure_time' => $sData['time'],
                'travel_class' => $sData['class'],
                'max_overload' => $sData['class'] === 'economy' ? 2 : 0,
                'price_per_seat' => $sData['price'],
                'baggage_limit_kg' => $sData['class'] === 'premium' ? 20.00 : 15.00,
                'is_active' => true,
                'allow_passenger_transfer' => true,
                'accept_external_transfer' => true,
                'transfer_fee_per_passenger' => 20000,
                'max_transfer_fee_percent' => 20,
                'allow_cod' => $sData['cod'],
                'cod_min_balance' => $route->cod_available ? $route->cod_min_deposit : 0,
                'created_at' => now()->subDays(rand(5, 10)),
                'updated_at' => now(),
            ]);

            foreach ($route->stops as $idx => $stop) {
                DB::table('schedule_stops')->insert([
                    'schedule_id' => $scheduleId,
                    'route_stop_id' => $stop->id,
                    'is_pickup_available' => $idx === 0,
                    'is_dropoff_available' => $idx === count($route->stops) - 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $firstStop = $route->stops[0];
            $lastStop = end($route->stops);
            
            DB::table('route_pricing')->insert([
                'schedule_id' => $scheduleId,
                'origin_stop_id' => $firstStop->id,
                'destination_stop_id' => $lastStop->id,
                'price' => $sData['price'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            
            if (count($route->stops) >= 3) {
                DB::table('route_pricing')->insert([
                    'schedule_id' => $scheduleId,
                    'origin_stop_id' => $route->stops[0]->id,
                    'destination_stop_id' => $route->stops[1]->id,
                    'price' => (int)($sData['price'] * 0.5),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                
                DB::table('route_pricing')->insert([
                    'schedule_id' => $scheduleId,
                    'origin_stop_id' => $route->stops[1]->id,
                    'destination_stop_id' => $lastStop->id,
                    'price' => (int)($sData['price'] * 0.7),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $schedules[] = (object) [
                'id' => $scheduleId, 'route' => $route, 'agency' => $agency,
                'vehicle' => $vehicle, 'driver' => $driver,
                'departure_date' => $departureDate, 'departure_time' => $sData['time'],
                'travel_class' => $sData['class'], 'price_per_seat' => $sData['price'],
                'allow_cod' => $sData['cod'],
            ];
        }

        return $schedules;
    }

    // ═══════════════════════════════════════════════════════
    // BOOKINGS
    // ═══════════════════════════════════════════════════════
    
    private function seedBookings(array $schedules, array $customers): array
    {
        $bookingConfigs = [
            ['status' => 'paid', 'schedule_idx' => 0, 'customer_idx' => 0, 'pax' => 2],
            ['status' => 'paid', 'schedule_idx' => 0, 'customer_idx' => 2, 'pax' => 1],
            ['status' => 'paid', 'schedule_idx' => 1, 'customer_idx' => 4, 'pax' => 3],
            ['status' => 'paid', 'schedule_idx' => 2, 'customer_idx' => 5, 'pax' => 1],
            ['status' => 'paid', 'schedule_idx' => 3, 'customer_idx' => 6, 'pax' => 2],
            ['status' => 'pending', 'schedule_idx' => 0, 'customer_idx' => 7, 'pax' => 1],
            ['status' => 'pending', 'schedule_idx' => 3, 'customer_idx' => 8, 'pax' => 1],
            ['status' => 'confirmed', 'schedule_idx' => 1, 'customer_idx' => 1, 'pax' => 1],
            ['status' => 'confirmed', 'schedule_idx' => 5, 'customer_idx' => 3, 'pax' => 2],
            ['status' => 'completed', 'schedule_idx' => 0, 'customer_idx' => 0, 'pax' => 1],
            ['status' => 'completed', 'schedule_idx' => 2, 'customer_idx' => 4, 'pax' => 2],
            ['status' => 'cancelled', 'schedule_idx' => 4, 'customer_idx' => 9, 'pax' => 1],
            ['status' => 'paid', 'schedule_idx' => 4, 'customer_idx' => 1, 'pax' => 1],
            ['status' => 'paid', 'schedule_idx' => 5, 'customer_idx' => 2, 'pax' => 1],
            ['status' => 'paid', 'schedule_idx' => 3, 'customer_idx' => 0, 'pax' => 3],
        ];

        $bookings = [];

        foreach ($bookingConfigs as $idx => $cfg) {
            $schedule = $schedules[$cfg['schedule_idx']];
            $customer = $customers[$cfg['customer_idx']];
            $route = $schedule->route;
            
            $originStop = $route->stops[0];
            $destStop = end($route->stops);
            
            $pricing = DB::table('route_pricing')
                ->where('schedule_id', $schedule->id)
                ->where('origin_stop_id', $originStop->id)
                ->where('destination_stop_id', $destStop->id)
                ->first();
            
            $passengerCount = $cfg['pax'];
            $pricePerPerson = $pricing ? $pricing->price : $schedule->price_per_seat;
            $basePrice = $pricePerPerson * $passengerCount;
            $serviceFee = 5000;
            $platformFee = (int)($basePrice * 0.03);
            $totalPrice = $basePrice + $serviceFee + $platformFee;
            
            $createdAt = match($cfg['status']) {
                'completed' => now()->subDays(rand(10, 30)),
                'cancelled' => now()->subDays(rand(3, 7)),
                default => now()->subDays(rand(0, 2))->subHours(rand(1, 23)),
            };
            
            $completedAt = $cfg['status'] === 'completed' ? $createdAt->copy()->addHours(rand(3, 8)) : null;
            $cancelledAt = $cfg['status'] === 'cancelled' ? $createdAt->copy()->addHours(rand(1, 24)) : null;
            
            $bookingId = DB::table('bookings')->insertGetId([
                'booking_code' => 'GM-' . $createdAt->format('Ymd') . '-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                'schedule_id' => $schedule->id,
                'customer_id' => $customer->id,
                'origin_stop_id' => $originStop->id,
                'destination_stop_id' => $destStop->id,
                'route_pricing_id' => $pricing?->id,
                'pickup_address' => 'Jl. Test No. ' . rand(1, 100) . ', ' . $originStop->city_name,
                'destination_address' => 'Jl. Tujuan No. ' . rand(1, 100) . ', ' . $destStop->city_name,
                'total_passengers' => $passengerCount,
                'total_price' => $totalPrice,
                'base_price' => $basePrice,
                'service_fee' => $serviceFee,
                'platform_fee' => $platformFee,
                'discount_amount' => 0,
                'status' => $cfg['status'],
                'cancelled_at' => $cancelledAt,
                'completed_at' => $completedAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            for ($p = 1; $p <= $passengerCount; $p++) {
                $pickedUpAt = in_array($cfg['status'], ['completed', 'on_going']) ? $completedAt?->copy()->subHours(rand(1, 3)) : null;
                $droppedOffAt = $cfg['status'] === 'completed' ? $completedAt : null;
                
                DB::table('booking_passengers')->insert([
                    'booking_id' => $bookingId,
                    'passenger_name' => 'Penumpang ' . $p . ' - ' . $customer->name,
                    'passenger_phone' => $customer->phone,
                    'baggage_weight' => rand(5, 15),
                    'seat_number' => $p,
                    'picked_up_at' => $pickedUpAt,
                    'dropped_off_at' => $droppedOffAt,
                    'created_at' => $createdAt, 'updated_at' => $createdAt,
                ]);
            }

            $bookings[] = (object) [
                'id' => $bookingId,
                'booking_code' => 'GM-' . $createdAt->format('Ymd') . '-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                'schedule' => $schedule, 'customer' => $customer,
                'status' => $cfg['status'], 'total_price' => $totalPrice,
                'base_price' => $basePrice, 'passenger_count' => $passengerCount,
                'created_at' => $createdAt, 'completed_at' => $completedAt,
            ];
        }

        return $bookings;
    }

    // ═══════════════════════════════════════════════════════
    // PAYMENTS
    // ═══════════════════════════════════════════════════════
    
    private function seedPayments(array $bookings, array $paymentAgents): array
    {
        $payments = [];
        
        foreach ($bookings as $idx => $booking) {
            $paymentType = match($booking->status) {
                'pending' => null,
                'confirmed' => 'cod',
                'cancelled' => 'midtrans',
                'completed' => rand(0, 1) ? 'midtrans' : 'cash',
                'paid' => ['midtrans', 'cash', 'cod'][$idx % 3],
                default => 'midtrans',
            };
            
            if (!$paymentType) continue;
            
            $commission = $booking->total_price * 0.05;
            $agencyRevenue = $booking->total_price - $commission;
            
            $paymentStatus = match($booking->status) {
                'confirmed' => 'cod_pending',
                'cancelled' => 'failed',
                'paid', 'completed' => 'paid',
                default => 'paid',
            };
            
            $paidAt = in_array($paymentStatus, ['paid', 'cod_confirmed']) ? $booking->created_at : null;
            
            $paymentId = DB::table('payments')->insertGetId([
                'booking_id' => $booking->id, 'cash_payment_id' => null,
                'amount' => $booking->total_price, 'commission' => $commission,
                'agency_revenue' => $agencyRevenue,
                'payment_type' => $paymentType, 'status' => $paymentStatus,
                'payment_method' => $paymentType === 'midtrans' ? 'bank_transfer' : null,
                'transaction_id' => $paymentType === 'midtrans' ? 'trx-' . uniqid() : null,
                'paid_at' => $paidAt,
                'expired_at' => $booking->created_at->copy()->addMinutes(30),
                'created_at' => $booking->created_at, 'updated_at' => $booking->created_at,
            ]);

            $cashPaymentId = null;
            if ($paymentType === 'cash') {
                $agentCommission = $booking->total_price * 0.02;
                $platformCommission = $booking->total_price * 0.03;
                
                $cashPaymentId = DB::table('cash_payments')->insertGetId([
                    'booking_id' => $booking->id,
                    'payment_agent_id' => $paymentAgents[array_rand($paymentAgents)]->id,
                    'payment_code' => 'WM-' . $booking->created_at->format('Ymd') . '-' . strtoupper(substr(md5($booking->id), 0, 6)),
                    'amount' => $booking->total_price,
                    'agent_commission' => $agentCommission,
                    'platform_commission' => $platformCommission,
                    'status' => $paymentStatus === 'paid' ? 'confirmed' : 'settled',
                    'confirmed_at' => $paidAt,
                    'expired_at' => $booking->created_at->copy()->addHours(24),
                    'created_at' => $booking->created_at, 'updated_at' => $booking->created_at,
                ]);
                
                DB::table('payments')->where('id', $paymentId)->update(['cash_payment_id' => $cashPaymentId]);
            }

            $payments[] = (object) [
                'id' => $paymentId, 'booking_id' => $booking->id,
                'payment_type' => $paymentType, 'status' => $paymentStatus,
                'amount' => $booking->total_price, 'cash_payment_id' => $cashPaymentId,
            ];
        }

        return $payments;
    }

    // ═══════════════════════════════════════════════════════
    // SETTLEMENTS, WITHDRAWALS, PROMOS, REVIEWS, TRANSFERS, WALLETS
    // ═══════════════════════════════════════════════════════
    
    private function seedSettlements(array $paymentAgents): array
    {
        $settlements = [];
        $statuses = ['pending', 'paid', 'verified'];
        
        for ($i = 0; $i < 3; $i++) {
            $agent = $paymentAgents[$i];
            $periodEnd = now()->subDays(rand(1, 7))->startOfWeek()->subDay();
            $periodStart = $periodEnd->copy()->subDays(6);
            $totalAmount = rand(500000, 2000000);
            $totalCommission = (int)($totalAmount * 0.02);
            
            $settlementId = DB::table('settlements')->insertGetId([
                'payment_agent_id' => $agent->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'total_transactions' => rand(5, 20),
                'total_amount' => $totalAmount,
                'total_commission' => $totalCommission,
                'amount_to_settle' => $totalAmount - $totalCommission,
                'status' => $statuses[$i],
                'paid_at' => $statuses[$i] !== 'pending' ? $periodEnd->copy()->addDay() : null,
                'verified_by' => $statuses[$i] === 'verified' ? 1 : null,
                'verified_at' => $statuses[$i] === 'verified' ? $periodEnd->copy()->addDays(2) : null,
                'created_at' => $periodEnd, 'updated_at' => $periodEnd,
            ]);
            
            $settlements[] = (object) ['id' => $settlementId, 'agent_id' => $agent->id, 'status' => $statuses[$i]];
        }
        return $settlements;
    }

    private function seedWithdrawals(array $agencies): array
    {
        $withdrawals = [];
        
        $wData = [
            ['agency' => 0, 'amount' => 500000, 'status' => 'completed', 'bank' => 'BCA', 'acc' => '1234567890', 'name' => 'H. Ahmad Jaya'],
            ['agency' => 1, 'amount' => 300000, 'status' => 'pending', 'bank' => 'BNI', 'acc' => '0987654321', 'name' => 'Siti Rahayu'],
        ];

        foreach ($wData as $wd) {
            $agency = $agencies[$wd['agency']];
            $adminFee = 5000;
            
            DB::table('withdrawals')->insertGetId([
                'agency_id' => $agency->id, 'amount' => $wd['amount'],
                'admin_fee' => $adminFee, 'net_amount' => $wd['amount'] - $adminFee,
                'bank_name' => $wd['bank'], 'bank_account_number' => $wd['acc'],
                'bank_account_name' => $wd['name'], 'status' => $wd['status'],
                'approved_by' => $wd['status'] !== 'pending' ? 1 : null,
                'approved_at' => $wd['status'] !== 'pending' ? now()->subDays(rand(1, 5)) : null,
                'completed_at' => $wd['status'] === 'completed' ? now()->subDays(rand(1, 3)) : null,
                'created_at' => now()->subDays(rand(5, 15)), 'updated_at' => now()->subDays(rand(1, 5)),
            ]);
        }
        return $withdrawals;
    }

    private function seedPromos(object $admin, array $routes, array $customers): array
    {
        $promos = [];
        $now = now();
        
        $promoData = [
            ['name' => 'Diskon Lebaran 2026', 'type' => 'general', 'desc' => 'Diskon spesial musim lebaran', 'pct' => 15, 'max' => 50000, 'min' => 100000, 'start' => $now->copy()->subDays(5), 'end' => $now->copy()->addDays(25), 'bearer' => 'platform', 'methods' => 'midtrans,cash,cod'],
            ['name' => 'Promo Rute Surabaya', 'type' => 'selective', 'desc' => 'Diskon rute tujuan Surabaya', 'pct' => 10, 'max' => 30000, 'min' => 150000, 'start' => $now->copy()->subDays(10), 'end' => $now->copy()->addDays(20), 'bearer' => 'shared', 'route' => 0, 'methods' => 'midtrans,cash'],
            ['name' => 'Flash Sale Premium', 'type' => 'general', 'desc' => 'Diskon kelas premium', 'pct' => 20, 'max' => 75000, 'min' => 200000, 'start' => $now->copy()->subDays(2), 'end' => $now->copy()->addDays(5), 'bearer' => 'platform', 'methods' => 'midtrans'],
            ['name' => 'Referral Reward dari Citra Dewi', 'type' => 'referral', 'desc' => 'Reward referral untuk Budi', 'pct' => 20, 'max' => 30000, 'min' => 0, 'start' => $now->copy()->subDays(3), 'end' => $now->copy()->addDays(27), 'bearer' => 'platform', 'methods' => null, 'created_by_user' => 0],
        ];

        foreach ($promoData as $p) {
            $routeId = isset($p['route']) ? $routes[$p['route']]->id : null;
            $createdBy = $admin->id;
            if (isset($p['created_by_user'])) {
                $createdBy = $customers[$p['created_by_user']]->id;
            }
            
            DB::table('promos')->insertGetId([
                'name' => $p['name'], 'type' => $p['type'], 'description' => $p['desc'],
                'discount_percent' => $p['pct'], 'max_discount' => $p['max'],
                'min_purchase' => $p['min'], 'route_id' => $routeId,
                'applicable_payment_methods' => $p['methods'],
                'start_date' => $p['start'], 'end_date' => $p['end'],
                'cost_bearer' => $p['bearer'],
                'platform_share_percent' => $p['bearer'] === 'shared' ? 50 : 100,
                'agency_share_percent' => $p['bearer'] === 'shared' ? 50 : 0,
                'is_active' => true, 'created_by' => $createdBy,
                'created_at' => $p['start'], 'updated_at' => $p['start'],
            ]);
        }
        return $promos;
    }

    private function seedReviews(array $bookings, array $agencies, array $customers): array
    {
        $reviews_text = [
            'Perjalanan sangat nyaman, sopir ramah dan tepat waktu!',
            'Mobil bersih, AC dingin. Recommended!',
            'Pelayanan bagus, tapi AC kurang dingin.',
            'Sangat memuaskan, akan booking lagi.',
            'Driver profesional dan rute sesuai.',
        ];
        
        $completedBookings = array_values(array_filter($bookings, fn($b) => $b->status === 'completed'));
        
        for ($i = 0; $i < min(5, count($completedBookings)); $i++) {
            $booking = $completedBookings[$i];
            $agency = $agencies[array_rand($agencies)];
            $customer = $customers[array_rand($customers)];
            
            DB::table('reviews')->insert([
                'booking_id' => $booking->id, 'agency_id' => $agency->id,
                'customer_id' => $customer->id, 'rating' => rand(3, 5),
                'review' => $reviews_text[$i],
                'created_at' => $booking->completed_at, 'updated_at' => $booking->completed_at,
            ]);
        }
        return [];
    }

    private function seedPassengerTransfers(array $schedules, array $bookings): array
    {
        // Internal transfer
        $sameRouteSchedules = array_values(array_filter($schedules, fn($s) => 
            $s->route->id === $schedules[0]->route->id && $s->agency->id === $schedules[0]->agency->id
        ));
        
        if (count($sameRouteSchedules) >= 2) {
            $fromSched = $sameRouteSchedules[0];
            $toSched = $sameRouteSchedules[1];
            
            $paidBookings = array_values(array_filter($bookings, fn($b) => 
                $b->schedule->id === $fromSched->id && in_array($b->status, ['paid', 'confirmed'])
            ));
            
            if (count($paidBookings) >= 2) {
                $transferBookings = array_slice($paidBookings, 0, 2);
                $totalPass = array_sum(array_map(fn($b) => $b->passenger_count, $transferBookings));
                $totalVal = array_sum(array_map(fn($b) => $b->total_price, $transferBookings));
                
                $transferId = DB::table('passenger_transfers')->insertGetId([
                    'from_schedule_id' => $fromSched->id, 'to_schedule_id' => $toSched->id,
                    'from_agency_id' => $fromSched->agency->id, 'to_agency_id' => $toSched->agency->id,
                    'total_passengers' => $totalPass,
                    'transfer_fee_per_passenger' => 0, 'total_transfer_fee' => 0,
                    'total_booking_value' => $totalVal,
                    'status' => 'completed', 'approved_by' => 1,
                    'approved_at' => now()->subHours(2), 'completed_at' => now()->subHours(2),
                    'notes' => 'Transfer internal - mobil digabung',
                    'created_at' => now()->subHours(3), 'updated_at' => now()->subHours(2),
                ]);

                foreach ($transferBookings as $tb) {
                    DB::table('passenger_transfer_bookings')->insert([
                        'passenger_transfer_id' => $transferId, 'booking_id' => $tb->id,
                        'created_at' => now()->subHours(3), 'updated_at' => now()->subHours(3),
                    ]);
                }
            }
        }
        
        // External transfer
        $firstRouteId = $schedules[0]->route->id;
        $firstAgencyId = $schedules[0]->agency->id;
        
        $externalSchedules = array_values(array_filter($schedules, fn($s) => 
            $s->route->id === $firstRouteId && $s->agency->id !== $firstAgencyId
        ));
        
        $fromSchedules = array_values(array_filter($schedules, fn($s) => $s->agency->id === $firstAgencyId));
        
        if (count($externalSchedules) >= 1 && count($fromSchedules) >= 1) {
            $fromSched = $fromSchedules[0];
            $toSched = $externalSchedules[0];
            
            $pendingBookings = array_values(array_filter($bookings, fn($b) => 
                $b->schedule->id === $fromSched->id && $b->status === 'paid'
            ));
            
            if (count($pendingBookings) >= 1) {
                $tb = $pendingBookings[0];
                
                DB::table('passenger_transfers')->insertGetId([
                    'from_schedule_id' => $fromSched->id, 'to_schedule_id' => $toSched->id,
                    'from_agency_id' => $fromSched->agency->id, 'to_agency_id' => $toSched->agency->id,
                    'total_passengers' => $tb->passenger_count,
                    'transfer_fee_per_passenger' => 20000,
                    'total_transfer_fee' => 20000 * $tb->passenger_count,
                    'total_booking_value' => $tb->total_price,
                    'status' => 'pending',
                    'notes' => 'Permintaan transfer ke agency penerima',
                    'created_at' => now()->subHours(1), 'updated_at' => now()->subHours(1),
                ]);
            }
        }
        return [];
    }

    private function seedWallets(array $agencies, array $bookings): array
    {
        $wallets = [];
        
        foreach ($agencies as $idx => $agency) {
            $agencyBookings = array_filter($bookings, fn($b) => 
                $b->schedule->agency->id === $agency->id && $b->status === 'completed'
            );
            $totalEarned = array_sum(array_map(fn($b) => $b->total_price, $agencyBookings));
            $availableBalance = $totalEarned * 0.7;
            $pendingBalance = $totalEarned * 0.3;
            $depositBalance = $idx === 0 ? 1000000 : ($idx === 1 ? 500000 : 0);
            $totalWithdrawn = $idx === 0 ? 500000 : ($idx === 1 ? 300000 : 0);
            
            DB::table('agency_wallets')->insertGetId([
                'agency_id' => $agency->id,
                'available_balance' => max(0, $availableBalance - $totalWithdrawn),
                'pending_balance' => $pendingBalance,
                'deposit_balance' => $depositBalance,
                'cod_hold_balance' => 0,
                'total_earned' => $totalEarned,
                'total_withdrawn' => $totalWithdrawn,
                'created_at' => now()->subMonths(5), 'updated_at' => now(),
            ]);
            
            // Wallet transactions
            if ($totalEarned > 0) {
                DB::table('wallet_transactions')->insert([
                    'agency_id' => $agency->id, 'type' => 'credit',
                    'amount' => $totalEarned, 'balance_before' => 0, 'balance_after' => $totalEarned,
                    'description' => 'Total pendapatan booking',
                    'reference_type' => 'booking', 'reference_id' => null,
                    'created_at' => now()->subMonths(2),
                ]);
            }
            if ($totalWithdrawn > 0) {
                DB::table('wallet_transactions')->insert([
                    'agency_id' => $agency->id, 'type' => 'debit',
                    'amount' => $totalWithdrawn, 'balance_before' => $totalEarned, 'balance_after' => $totalEarned - $totalWithdrawn,
                    'description' => 'Penarikan dana',
                    'reference_type' => 'withdrawal', 'reference_id' => null,
                    'created_at' => now()->subMonths(1),
                ]);
            }
            if ($depositBalance > 0) {
                DB::table('wallet_transactions')->insert([
                    'agency_id' => $agency->id, 'type' => 'credit',
                    'amount' => $depositBalance, 'balance_before' => 0, 'balance_after' => $depositBalance,
                    'description' => 'Top up saldo deposit',
                    'reference_type' => 'topup', 'reference_id' => null,
                    'created_at' => now()->subWeeks(2),
                ]);
            }
        }
        return $wallets;
    }

    private function seedReferralCodes(array $customers): void
    {
        // Budi (index 0) - Referrer
        DB::table('referral_codes')->insert([
            'user_id' => $customers[0]->id, 'code' => 'BUDI123',
            'total_referred' => 1, 'successful_referrals' => 1,
            'created_at' => now()->subDays(10), 'updated_at' => now()->subDays(2),
        ]);

        // Ani (index 1) - Referrer
        DB::table('referral_codes')->insert([
            'user_id' => $customers[1]->id, 'code' => 'ANI456',
            'total_referred' => 1, 'successful_referrals' => 0,
            'created_at' => now()->subDays(8), 'updated_at' => now()->subDays(1),
        ]);

        // Citra (index 2) - Di-referral Budi
        DB::table('users')->where('id', $customers[2]->id)->update(['referred_by' => $customers[0]->id]);
        DB::table('referral_trackings')->insert([
            'referrer_id' => $customers[0]->id, 'referred_user_id' => $customers[2]->id,
            'referral_code' => 'BUDI123', 'is_successful' => true,
            'successful_at' => now()->subDays(2),
            'created_at' => now()->subDays(5), 'updated_at' => now()->subDays(2),
        ]);

        // Dodi (index 3) - Di-referral Ani
        DB::table('users')->where('id', $customers[3]->id)->update(['referred_by' => $customers[1]->id]);
        DB::table('referral_trackings')->insert([
            'referrer_id' => $customers[1]->id, 'referred_user_id' => $customers[3]->id,
            'referral_code' => 'ANI456', 'is_successful' => false,
            'created_at' => now()->subDays(1), 'updated_at' => now()->subDays(1),
        ]);

        // Promo usage untuk Budi (reward dari referral Citra)
        DB::table('promo_usages')->insert([
            'promo_id' => 4, 'user_id' => $customers[0]->id,
            'booking_id' => null, 'discount_amount' => 30000,
            'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2),
        ]);
    }

    private function seedNotifications(object $admin, array $agencies, array $customers, array $bookings): void
    {
        $notifications = [
            [
                'user_id' => $admin->id,
                'title' => 'Selamat Datang',
                'body' => 'Dashboard admin siap digunakan. Kelola agency, rute, promo, dan pantau semua aktivitas.',
                'data' => json_encode(['type' => 'info', 'action' => 'dashboard']),
            ],
            [
                'user_id' => $agencies[0]->user_id,
                'title' => '📋 Booking Baru',
                'body' => 'Ada booking baru yang menunggu pembayaran. Cek dashboard untuk detail.',
                'data' => json_encode(['type' => 'new_booking', 'action' => 'booking']),
            ],
            [
                'user_id' => $agencies[1]->user_id,
                'title' => '📋 Booking Baru',
                'body' => 'Ada booking baru yang menunggu pembayaran. Cek dashboard untuk detail.',
                'data' => json_encode(['type' => 'new_booking', 'action' => 'booking']),
            ],
            [
                'user_id' => $customers[0]->id,
                'title' => '✅ Booking Dikonfirmasi',
                'body' => 'Booking GM-20240702-0001 Anda telah dikonfirmasi. E-Ticket dapat diunduh.',
                'data' => json_encode(['type' => 'booking_confirmed', 'action' => 'e_ticket']),
            ],
            [
                'user_id' => $customers[1]->id,
                'title' => '🎉 Referral Berhasil',
                'body' => 'Selamat! Teman Anda telah berhasil bertransaksi. Anda mendapatkan diskon 20% untuk booking berikutnya!',
                'data' => json_encode(['type' => 'referral_success', 'action' => 'promo']),
            ],
        ];
        
        foreach ($notifications as $n) {
            DB::table('notifications')->insert([
                'user_id' => $n['user_id'],
                'title' => $n['title'],
                'body' => $n['body'],
                'data' => $n['data'],
                'is_read' => rand(0, 1) ? true : false,
                'read_at' => rand(0, 1) ? now()->subHours(rand(1, 48)) : null,
                'created_at' => now()->subHours(rand(1, 72)),
                'updated_at' => now()->subHours(rand(1, 72)),
            ]);
        }
    }
}