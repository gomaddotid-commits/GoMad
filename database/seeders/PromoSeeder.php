<?php
// File: database/seeders/PromoSeeder.php
// Deskripsi: Seeder untuk promo (general, selective, referral)

namespace Database\Seeders;

use App\Models\Promo;
use App\Models\PromoUsage;
use App\Models\ReferralCode;
use App\Models\ReferralTracking;
use App\Models\User;
use App\Models\Schedule;
use Illuminate\Database\Seeder;

class PromoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@gomad.id')->first();
        if (!$admin) {
            echo "⚠️ Admin tidak ditemukan.\n";
            return;
        }

        echo "Membuat data promo...\n";

        // ============================================================
        // PROMO GENERAL 1: Diskon Natal & Tahun Baru
        // ============================================================
        Promo::create([
            'name' => 'Diskon Natal & Tahun Baru',
            'type' => 'general',
            'description' => 'Promo spesial akhir tahun untuk semua customer terdaftar. Diskon 15% untuk semua rute.',
            'discount_percent' => 15,
            'max_discount' => 50000,
            'min_purchase' => 150000,
            'route_id' => null,
            'travel_class' => null,
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'cost_bearer' => 'platform',
            'platform_share_percent' => 100,
            'agency_share_percent' => 0,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        echo "  ✅ Promo General: Diskon Natal & Tahun Baru (15%)\n";

        // ============================================================
        // PROMO GENERAL 2: Flash Sale Lebaran
        // ============================================================
        Promo::create([
            'name' => 'Flash Sale Lebaran',
            'type' => 'general',
            'description' => 'Diskon spesial Lebaran 20% untuk semua rute. Minimal pembelian Rp 200.000.',
            'discount_percent' => 20,
            'max_discount' => 60000,
            'min_purchase' => 200000,
            'route_id' => null,
            'travel_class' => null,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'cost_bearer' => 'platform',
            'platform_share_percent' => 100,
            'agency_share_percent' => 0,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        echo "  ✅ Promo General: Flash Sale Lebaran (20%)\n";

        // ============================================================
        // PROMO SELEKTIF 1: Flash Sale Sumenep-Surabaya
        // ============================================================
        $routeSby = \App\Models\Route::where('route_name', 'Sumenep - Surabaya')->first();
        
        $promoSelective1 = Promo::create([
            'name' => 'Flash Sale Sumenep-Surabaya',
            'type' => 'selective',
            'description' => 'Diskon 25% khusus rute Sumenep-Surabaya kelas Ekonomi. Agency bisa memilih untuk mengaktifkan.',
            'discount_percent' => 25,
            'max_discount' => 40000,
            'min_purchase' => 100000,
            'route_id' => $routeSby?->id,
            'travel_class' => 'economy',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(21)->toDateString(),
            'cost_bearer' => 'shared',
            'platform_share_percent' => 50,
            'agency_share_percent' => 50,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        echo "  ✅ Promo Selektif: Flash Sale Sumenep-Surabaya (25%)\n";

        // ============================================================
        // PROMO SELEKTIF 2: Diskon Premium Malang
        // ============================================================
        $routeMlg = \App\Models\Route::where('route_name', 'Sumenep - Malang')->first();
        
        $promoSelective2 = Promo::create([
            'name' => 'Diskon Premium Malang',
            'type' => 'selective',
            'description' => 'Diskon 10% untuk rute Sumenep-Malang. Biaya ditanggung agency.',
            'discount_percent' => 10,
            'max_discount' => 30000,
            'min_purchase' => 150000,
            'route_id' => $routeMlg?->id,
            'travel_class' => 'premium',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(14)->toDateString(),
            'cost_bearer' => 'agency',
            'platform_share_percent' => 0,
            'agency_share_percent' => 100,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        echo "  ✅ Promo Selektif: Diskon Premium Malang (10%)\n";

        // ============================================================
        // PROMO SELEKTIF 3: Early Bird Sumenep-Surabaya
        // ============================================================
        Promo::create([
            'name' => 'Early Bird Sumenep-Surabaya',
            'type' => 'selective',
            'description' => 'Diskon 30% untuk pemesanan awal rute Sumenep-Surabaya. Shared cost.',
            'discount_percent' => 30,
            'max_discount' => 50000,
            'min_purchase' => 100000,
            'route_id' => $routeSby?->id,
            'travel_class' => null,
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'cost_bearer' => 'shared',
            'platform_share_percent' => 60,
            'agency_share_percent' => 40,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        echo "  ✅ Promo Selektif: Early Bird Sumenep-Surabaya (30%)\n";

        // ============================================================
        // PASANG PROMO SELEKTIF KE SCHEDULE
        // ============================================================
        $schedule1 = Schedule::find(1); // Hari ini, Sumenep-Surabaya, Jaya Abadi
        $schedule3 = Schedule::find(3); // Lusa, Sumenep-Surabaya, Jaya Abadi
        $schedule4 = Schedule::find(4); // Lusa, Sumenep-Surabaya, Makmur Travel

        if ($schedule1 && $promoSelective1) {
            $schedule1->promos()->attach($promoSelective1->id);
            echo "  📎 Promo dipasang di Schedule #1 (Hari ini, Jaya Abadi)\n";
        }
        if ($schedule3 && $promoSelective1) {
            $schedule3->promos()->attach($promoSelective1->id);
            echo "  📎 Promo dipasang di Schedule #3 (Lusa, Jaya Abadi - Transfer)\n";
        }
        if ($schedule4 && $promoSelective1) {
            $schedule4->promos()->attach($promoSelective1->id);
            echo "  📎 Promo dipasang di Schedule #4 (Lusa, Makmur Travel)\n";
        }

        // ============================================================
        // REFERRAL CODES & TRACKING
        // ============================================================
        $budi = User::where('email', 'budi@test.com')->first();
        $ani = User::where('email', 'ani@test.com')->first();
        
        // Customer 3 (baru, untuk demo referral)
        $customer3 = User::firstOrCreate(
            ['email' => 'dian@test.com'],
            [
                'name' => 'Dian Permata',
                'phone' => '081777777773',
                'password' => bcrypt('password'),
                'role' => 'customer',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Customer 4 (baru, untuk demo referral)
        $customer4 = User::firstOrCreate(
            ['email' => 'eko@test.com'],
            [
                'name' => 'Eko Prasetyo',
                'phone' => '081777777774',
                'password' => bcrypt('password'),
                'role' => 'customer',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // Generate referral codes
        if ($budi) {
            $refBudi = ReferralCode::firstOrCreate(
                ['user_id' => $budi->id],
                [
                    'code' => 'BUDI123',
                    'total_referred' => 3,
                    'successful_referrals' => 2,
                ]
            );
            echo "  ✅ Referral Code: Budi → BUDI123\n";
        }

        if ($ani) {
            $refAni = ReferralCode::firstOrCreate(
                ['user_id' => $ani->id],
                [
                    'code' => 'ANI456',
                    'total_referred' => 1,
                    'successful_referrals' => 1,
                ]
            );
            echo "  ✅ Referral Code: Ani → ANI456\n";
        }

        // Referral Tracking: Budi mengajak Dian (SUKSES)
        if ($budi && $customer3) {
            $customer3->update(['referred_by' => $budi->id]);
            
            ReferralTracking::create([
                'referrer_id' => $budi->id,
                'referred_user_id' => $customer3->id,
                'referral_code' => 'BUDI123',
                'is_successful' => true,
                'successful_at' => now()->subDays(2),
            ]);
            echo "  ✅ Referral: Budi → Dian (SUKSES)\n";
        }

        // Referral Tracking: Budi mengajak Eko (BELUM SUKSES)
        if ($budi && $customer4) {
            $customer4->update(['referred_by' => $budi->id]);
            
            ReferralTracking::create([
                'referrer_id' => $budi->id,
                'referred_user_id' => $customer4->id,
                'referral_code' => 'BUDI123',
                'is_successful' => false,
                'successful_at' => null,
            ]);
            echo "  ✅ Referral: Budi → Eko (PENDING - belum transaksi)\n";
        }

        // Referral Tracking: Ani mengajak seseorang (SUKSES)
        if ($ani) {
            $refUser = User::firstOrCreate(
                ['email' => 'fitri@test.com'],
                [
                    'name' => 'Fitri Handayani',
                    'phone' => '081777777775',
                    'password' => bcrypt('password'),
                    'role' => 'customer',
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'referred_by' => $ani->id,
                ]
            );

            ReferralTracking::create([
                'referrer_id' => $ani->id,
                'referred_user_id' => $refUser->id,
                'referral_code' => 'ANI456',
                'is_successful' => true,
                'successful_at' => now()->subDays(5),
            ]);
            echo "  ✅ Referral: Ani → Fitri (SUKSES)\n";
        }

        // ============================================================
        // PROMO REFERRAL REWARD (untuk Budi, karena berhasil referral)
        // ============================================================
        if ($budi) {
            Promo::create([
                'name' => 'Referral Reward - Budi',
                'type' => 'referral',
                'description' => 'Reward dari referral Dian Permata. Diskon 30% (maks Rp 50.000).',
                'discount_percent' => 30,
                'max_discount' => 50000,
                'min_purchase' => 100000,
                'route_id' => null,
                'travel_class' => null,
                'start_date' => now()->subDays(2)->toDateString(),
                'end_date' => now()->addDays(28)->toDateString(),
                'cost_bearer' => 'platform',
                'platform_share_percent' => 100,
                'agency_share_percent' => 0,
                'is_active' => true,
                'created_by' => $admin->id,
            ]);
            echo "  ✅ Promo Referral: Budi dapat diskon 30% (dari referral Dian)\n";
        }

        if ($ani) {
            Promo::create([
                'name' => 'Referral Reward - Ani',
                'type' => 'referral',
                'description' => 'Reward dari referral Fitri Handayani. Diskon 20% (maks Rp 30.000).',
                'discount_percent' => 20,
                'max_discount' => 30000,
                'min_purchase' => 100000,
                'route_id' => null,
                'travel_class' => null,
                'start_date' => now()->subDays(5)->toDateString(),
                'end_date' => now()->addDays(25)->toDateString(),
                'cost_bearer' => 'platform',
                'platform_share_percent' => 100,
                'agency_share_percent' => 0,
                'is_active' => true,
                'created_by' => $admin->id,
            ]);
            echo "  ✅ Promo Referral: Ani dapat diskon 20% (dari referral Fitri)\n";
        }

        // ============================================================
        // PROMO USAGE (contoh penggunaan promo)
        // ============================================================
        $booking1 = \App\Models\Booking::where('booking_code', 'like', 'GM-%')->first();
        
        if ($booking1 && $promoSelective1) {
            PromoUsage::create([
                'promo_id' => $promoSelective1->id,
                'user_id' => $booking1->customer_id,
                'booking_id' => $booking1->id,
                'discount_amount' => 40000,
            ]);
            echo "  ✅ Promo Usage: Booking {$booking1->booking_code} pakai Flash Sale\n";
        }

        echo "\n📊 RINGKASAN DATA PROMO:\n";
        echo "──────────────────────────────────────────────\n";
        echo "✅ 2 Promo General (Natal 15%, Lebaran 20%)\n";
        echo "✅ 3 Promo Selektif (Flash Sale SBY 25%, Premium MLG 10%, Early Bird 30%)\n";
        echo "✅ 2 Promo Referral (Budi 30%, Ani 20%)\n";
        echo "✅ Referral Codes: BUDI123 (Budi), ANI456 (Ani)\n";
        echo "✅ Referral Tracking: 3 sukses, 1 pending\n";
        echo "✅ Promo terpasang di Schedule #1, #3, #4\n";
        echo "──────────────────────────────────────────────\n";
        echo "\n💡 TIPS DEMO PROMO:\n";
        echo "  1. Login Budi (budi@test.com) → Booking → lihat promo Referral + General\n";
        echo "  2. Login Ani (ani@test.com) → Booking → lihat promo Referral\n";
        echo "  3. Schedule Sumenep-Surabaya → tampil badge Flash Sale 25%\n";
        echo "  4. Admin → Promo → lihat semua promo & statistik\n";
        echo "  5. Agency → Promo → pasang/lepas promo selektif\n";
    }
}

// End of file