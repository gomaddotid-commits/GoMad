<?php
// File: database/seeders/PaymentAgentSeeder.php
// Deskripsi: Seeder untuk payment agent (Warung GoMad) dengan data lengkap

namespace Database\Seeders;

use App\Models\PaymentAgent;
use App\Models\User;
use App\Models\CashPayment;
use App\Models\Settlement;
use App\Models\Booking;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PaymentAgentSeeder extends Seeder
{
    public function run(): void
    {
        // Warung 1: Warung Bu Sum (Verified, Sumenep Kota)
        $user1 = User::create([
            'name' => 'Sumiati',
            'email' => 'warungbusum@gomad.id',
            'phone' => '081777777771',
            'password' => Hash::make('password'),
            'role' => 'payment_agent',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $agent1 = PaymentAgent::create([
            'user_id' => $user1->id,
            'agent_name' => 'Warung Bu Sum',
            'owner_name' => 'Sumiati',
            'owner_phone' => '081777777771',
            'guard_name' => 'Ahmad',
            'guard_phone' => '087777777772',
            'address' => 'Jl. Trunojoyo No. 10, Sumenep Kota, Sumenep',
            'kecamatan' => 'Kota Sumenep',
            'maps_link' => 'https://maps.google.com/?q=-7.0051,113.8586',
            'latitude' => -7.0051,
            'longitude' => 113.8586,
            'pin' => Hash::make('123456'),
            'is_active' => true,
            'is_verified' => true,
            'commission_rate' => 2.00,
            'total_transactions' => 45,
            'total_commission' => 250000,
            'balance_to_settle' => 1500000,
        ]);

        // Warung 2: Toko Pak Haji (Verified, Pamekasan)
        $user2 = User::create([
            'name' => 'Haji Syafii',
            'email' => 'tokopakhaji@gomad.id',
            'phone' => '081888888881',
            'password' => Hash::make('password'),
            'role' => 'payment_agent',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $agent2 = PaymentAgent::create([
            'user_id' => $user2->id,
            'agent_name' => 'Toko Pak Haji',
            'owner_name' => 'Haji Syafii',
            'owner_phone' => '081888888881',
            'guard_name' => 'Faisal',
            'guard_phone' => '087888888882',
            'address' => 'Jl. Raya Pamekasan No. 25, Pamekasan',
            'kecamatan' => 'Pamekasan',
            'maps_link' => 'https://maps.google.com/?q=-7.1613,113.4825',
            'latitude' => -7.1613,
            'longitude' => 113.4825,
            'pin' => Hash::make('654321'),
            'is_active' => true,
            'is_verified' => true,
            'commission_rate' => 2.00,
            'total_transactions' => 28,
            'total_commission' => 180000,
            'balance_to_settle' => 900000,
        ]);

        // Warung 3: Warung Mbak Nur (Verified, Bangkalan)
        $user3 = User::create([
            'name' => 'Nur Hayati',
            'email' => 'warungmbaknur@gomad.id',
            'phone' => '081999999991',
            'password' => Hash::make('password'),
            'role' => 'payment_agent',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $agent3 = PaymentAgent::create([
            'user_id' => $user3->id,
            'agent_name' => 'Warung Mbak Nur',
            'owner_name' => 'Nur Hayati',
            'owner_phone' => '081999999991',
            'guard_name' => null,
            'guard_phone' => null,
            'address' => 'Jl. Raya Bangkalan No. 8, Bangkalan',
            'kecamatan' => 'Bangkalan',
            'maps_link' => 'https://maps.google.com/?q=-7.0307,112.7450',
            'latitude' => -7.0307,
            'longitude' => 112.7450,
            'pin' => Hash::make('111111'),
            'is_active' => true,
            'is_verified' => true,
            'commission_rate' => 2.00,
            'total_transactions' => 15,
            'total_commission' => 120000,
            'balance_to_settle' => 600000,
        ]);

        // Warung 4: Toko Kelontong Berkah (Verified, Surabaya)
        $user4 = User::create([
            'name' => 'Bapak Rudi',
            'email' => 'tokoberkah@gomad.id',
            'phone' => '081999999992',
            'password' => Hash::make('password'),
            'role' => 'payment_agent',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $agent4 = PaymentAgent::create([
            'user_id' => $user4->id,
            'agent_name' => 'Toko Kelontong Berkah',
            'owner_name' => 'Bapak Rudi',
            'owner_phone' => '081999999992',
            'guard_name' => 'Rina',
            'guard_phone' => '087999999993',
            'address' => 'Jl. Diponegoro No. 45, Surabaya',
            'kecamatan' => 'Surabaya Pusat',
            'maps_link' => 'https://maps.google.com/?q=-7.2575,112.7521',
            'latitude' => -7.2575,
            'longitude' => 112.7521,
            'pin' => Hash::make('222222'),
            'is_active' => true,
            'is_verified' => true,
            'commission_rate' => 2.00,
            'total_transactions' => 20,
            'total_commission' => 200000,
            'balance_to_settle' => 1000000,
        ]);

        // Warung 5: Warung Barokah (Pending, Sumenep)
        $user5 = User::create([
            'name' => 'Ibu Fatimah',
            'email' => 'warungbarokah@gomad.id',
            'phone' => '081999999994',
            'password' => Hash::make('password'),
            'role' => 'payment_agent',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $agent5 = PaymentAgent::create([
            'user_id' => $user5->id,
            'agent_name' => 'Warung Barokah',
            'owner_name' => 'Ibu Fatimah',
            'owner_phone' => '081999999994',
            'guard_name' => null,
            'guard_phone' => null,
            'address' => 'Jl. KH Mansyur No. 3, Sumenep',
            'kecamatan' => 'Kota Sumenep',
            'maps_link' => 'https://maps.google.com/?q=-7.0100,113.8600',
            'latitude' => -7.0100,
            'longitude' => 113.8600,
            'pin' => Hash::make('333333'),
            'is_active' => true,
            'is_verified' => false,
            'commission_rate' => 2.00,
            'total_transactions' => 0,
            'total_commission' => 0,
            'balance_to_settle' => 0,
        ]);

        // ============================================================
        // DATA CASH PAYMENTS (Transaksi Warung)
        // ============================================================
        
        // Buat dummy bookings untuk cash payment
        $schedule = \App\Models\Schedule::first();
        if ($schedule) {
            $customer = User::where('email', 'budi@test.com')->first();
            
            if ($customer && $schedule) {
                $stops = $schedule->route->stops()->orderBy('stop_order')->get();
                $originStop = $stops->first();
                $destStop = $stops->last();
                
                // Transaksi 1: Warung Bu Sum - Confirmed
                $booking1 = Booking::create([
                    'booking_code' => 'GM-' . now()->format('Ymd') . '-0100',
                    'schedule_id' => $schedule->id,
                    'customer_id' => $customer->id,
                    'origin_stop_id' => $originStop->id,
                    'destination_stop_id' => $destStop->id,
                    'pickup_address' => 'Jl. Kartini No. 15, Sumenep',
                    'destination_address' => 'Jl. Pahlawan No. 20, Surabaya',
                    'total_passengers' => 2,
                    'total_price' => 300000,
                    'status' => 'paid',
                ]);

                \App\Models\BookingPassenger::create([
                    'booking_id' => $booking1->id,
                    'passenger_name' => 'Budi Prasetyo',
                    'passenger_phone' => '081555555555',
                    'seat_number' => 1,
                ]);

                \App\Models\BookingPassenger::create([
                    'booking_id' => $booking1->id,
                    'passenger_name' => 'Siti Aminah',
                    'passenger_phone' => '081555555556',
                    'seat_number' => 2,
                ]);

                // Cash payment untuk booking 1 (confirmed, by Warung Bu Sum)
                $cash1 = CashPayment::create([
                    'booking_id' => $booking1->id,
                    'payment_agent_id' => $agent1->id,
                    'payment_code' => 'WM-' . now()->format('Ymd') . '-A1B2C3',
                    'amount' => 300000,
                    'agent_commission' => 6000,
                    'platform_commission' => 15000,
                    'status' => 'confirmed',
                    'confirmed_at' => now()->subDays(5),
                    'expired_at' => now()->subDays(5)->addHours(24),
                ]);

                Payment::create([
                    'booking_id' => $booking1->id,
                    'cash_payment_id' => $cash1->id,
                    'amount' => 300000,
                    'commission' => 15000,
                    'agency_revenue' => 279000,
                    'payment_type' => 'cash',
                    'status' => 'paid',
                    'paid_at' => now()->subDays(5),
                ]);

                // Transaksi 2: Toko Pak Haji - Confirmed
                $booking2 = Booking::create([
                    'booking_code' => 'GM-' . now()->format('Ymd') . '-0101',
                    'schedule_id' => $schedule->id,
                    'customer_id' => $customer->id,
                    'origin_stop_id' => $originStop->id,
                    'destination_stop_id' => $destStop->id,
                    'pickup_address' => 'Jl. Kartini No. 15, Sumenep',
                    'destination_address' => 'Jl. Veteran No. 10, Surabaya',
                    'total_passengers' => 1,
                    'total_price' => 150000,
                    'status' => 'paid',
                ]);

                \App\Models\BookingPassenger::create([
                    'booking_id' => $booking2->id,
                    'passenger_name' => 'Ani Rahmawati',
                    'passenger_phone' => '081666666666',
                    'seat_number' => 1,
                ]);

                $cash2 = CashPayment::create([
                    'booking_id' => $booking2->id,
                    'payment_agent_id' => $agent2->id,
                    'payment_code' => 'WM-' . now()->format('Ymd') . '-D4E5F6',
                    'amount' => 150000,
                    'agent_commission' => 3000,
                    'platform_commission' => 7500,
                    'status' => 'confirmed',
                    'confirmed_at' => now()->subDays(3),
                    'expired_at' => now()->subDays(3)->addHours(24),
                ]);

                Payment::create([
                    'booking_id' => $booking2->id,
                    'cash_payment_id' => $cash2->id,
                    'amount' => 150000,
                    'commission' => 7500,
                    'agency_revenue' => 139500,
                    'payment_type' => 'cash',
                    'status' => 'paid',
                    'paid_at' => now()->subDays(3),
                ]);

                // Transaksi 3: Pending (belum dibayar) - untuk demo kode bayar aktif
                $booking3 = Booking::create([
                    'booking_code' => 'GM-' . now()->format('Ymd') . '-0102',
                    'schedule_id' => $schedule->id,
                    'customer_id' => $customer->id,
                    'origin_stop_id' => $originStop->id,
                    'destination_stop_id' => $destStop->id,
                    'pickup_address' => 'Jl. Kartini No. 15, Sumenep',
                    'destination_address' => 'Jl. Merdeka No. 5, Surabaya',
                    'total_passengers' => 1,
                    'total_price' => 150000,
                    'status' => 'pending',
                ]);

                \App\Models\BookingPassenger::create([
                    'booking_id' => $booking3->id,
                    'passenger_name' => 'Joko Widodo',
                    'passenger_phone' => '081777777777',
                    'seat_number' => 1,
                ]);

                CashPayment::create([
                    'booking_id' => $booking3->id,
                    'payment_code' => 'WM-' . now()->format('Ymd') . '-PEND01',
                    'amount' => 150000,
                    'agent_commission' => 3000,
                    'platform_commission' => 7500,
                    'status' => 'pending',
                    'expired_at' => now()->addHours(20),
                ]);
            }
        }

        // ============================================================
        // DATA SETTLEMENTS (Tagihan untuk Warung)
        // ============================================================
        
        // Settlement Minggu Lalu (Paid & Verified) - Warung Bu Sum
        Settlement::create([
            'payment_agent_id' => $agent1->id,
            'period_start' => now()->subWeek()->startOfWeek(Carbon::MONDAY)->subWeek()->toDateString(),
            'period_end' => now()->subWeek()->startOfWeek(Carbon::MONDAY)->subDay()->toDateString(),
            'total_transactions' => 12,
            'total_amount' => 1800000,
            'total_commission' => 36000,
            'amount_to_settle' => 1764000,
            'status' => 'verified',
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'STL-VRF-' . uniqid(),
            'paid_at' => now()->subDays(5),
            'verified_at' => now()->subDays(4),
            'verified_by' => 1, // Admin ID
        ]);

        // Settlement Minggu Lalu - Toko Pak Haji
        Settlement::create([
            'payment_agent_id' => $agent2->id,
            'period_start' => now()->subWeek()->startOfWeek(Carbon::MONDAY)->subWeek()->toDateString(),
            'period_end' => now()->subWeek()->startOfWeek(Carbon::MONDAY)->subDay()->toDateString(),
            'total_transactions' => 8,
            'total_amount' => 1200000,
            'total_commission' => 24000,
            'amount_to_settle' => 1176000,
            'status' => 'verified',
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'STL-VRF-' . uniqid(),
            'paid_at' => now()->subDays(4),
            'verified_at' => now()->subDays(3),
            'verified_by' => 1,
        ]);

        // Settlement Minggu Ini (Paid, menunggu verifikasi) - Warung Bu Sum
        Settlement::create([
            'payment_agent_id' => $agent1->id,
            'period_start' => now()->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'period_end' => now()->subWeek()->startOfWeek(Carbon::MONDAY)->addDays(6)->toDateString(),
            'total_transactions' => 10,
            'total_amount' => 1500000,
            'total_commission' => 30000,
            'amount_to_settle' => 1470000,
            'status' => 'paid',
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'STL-PAID-' . uniqid(),
            'paid_at' => now()->subDays(1),
        ]);

        // Settlement Minggu Ini (Pending) - Toko Pak Haji
        Settlement::create([
            'payment_agent_id' => $agent2->id,
            'period_start' => now()->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'period_end' => now()->subWeek()->startOfWeek(Carbon::MONDAY)->addDays(6)->toDateString(),
            'total_transactions' => 6,
            'total_amount' => 900000,
            'total_commission' => 18000,
            'amount_to_settle' => 882000,
            'status' => 'pending',
        ]);

        // Settlement Minggu Ini (Pending) - Toko Berkah
        Settlement::create([
            'payment_agent_id' => $agent4->id,
            'period_start' => now()->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'period_end' => now()->subWeek()->startOfWeek(Carbon::MONDAY)->addDays(6)->toDateString(),
            'total_transactions' => 5,
            'total_amount' => 1000000,
            'total_commission' => 20000,
            'amount_to_settle' => 980000,
            'status' => 'pending',
        ]);

        // Settlement Overdue (2 minggu lalu, belum dibayar) - Warung Mbak Nur
        Settlement::create([
            'payment_agent_id' => $agent3->id,
            'period_start' => now()->subWeeks(3)->startOfWeek(Carbon::MONDAY)->toDateString(),
            'period_end' => now()->subWeeks(3)->startOfWeek(Carbon::MONDAY)->addDays(6)->toDateString(),
            'total_transactions' => 4,
            'total_amount' => 600000,
            'total_commission' => 12000,
            'amount_to_settle' => 588000,
            'status' => 'overdue',
        ]);

        echo "Payment Agent Seeded:\n";
        echo "✅ Warung Bu Sum (verified) - warungbusum@gomad.id / password / PIN: 123456\n";
        echo "✅ Toko Pak Haji (verified) - tokopakhaji@gomad.id / password / PIN: 654321\n";
        echo "✅ Warung Mbak Nur (verified) - warungmbaknur@gomad.id / password / PIN: 111111\n";
        echo "✅ Toko Berkah (verified) - tokoberkah@gomad.id / password / PIN: 222222\n";
        echo "⏳ Warung Barokah (pending) - warungbarokah@gomad.id / password / PIN: 333333\n";
        echo "\nSettlement Data:\n";
        echo "📋 Warung Bu Sum: 1 verified + 1 paid (menunggu verifikasi)\n";
        echo "📋 Toko Pak Haji: 1 verified + 1 pending\n";
        echo "📋 Toko Berkah: 1 pending\n";
        echo "📋 Warung Mbak Nur: 1 overdue\n";
    }
}

// End of file