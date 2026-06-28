<?php
// File: database/seeders/WithdrawalSeeder.php
// Deskripsi: Seeder untuk data withdrawal (penarikan dana) agency

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\AgencyWallet;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Database\Seeder;

class WithdrawalSeeder extends Seeder
{
    public function run(): void
    {
        echo "💰 GENERATING WITHDRAWAL DATA...\n";
        echo "═══════════════════════════════════════════\n\n";

        $adminId = User::where('email', 'admin@gomad.id')->first()?->id ?? 1;

        // Ambil semua agency VERIFIED yang punya wallet
        $agencies = Agency::where('is_verified', true)->get();

        if ($agencies->isEmpty()) {
            echo "⚠️  Tidak ada agency verified untuk withdrawal\n";
            return;
        }

        $totalWithdrawals = 0;
        $banks = ['BCA', 'BRI', 'BNI', 'Mandiri', 'CIMB Niaga', 'BTN', 'BJB'];

        foreach ($agencies as $index => $agency) {
            $wallet = AgencyWallet::where('agency_id', $agency->id)->first();

            if (!$wallet || $wallet->total_earned <= 0) {
                continue; // Skip agency tanpa riwayat pendapatan
            }

            // ========================================
            // 1. WITHDRAWAL COMPLETED (2 transaksi)
            // ========================================
            for ($i = 0; $i < 2; $i++) {
                $bank = fake()->randomElement($banks);
                $amount = rand(500000, 3000000);
                $adminFee = 5000;
                $netAmount = $amount - $adminFee;

                Withdrawal::create([
                    'agency_id' => $agency->id,
                    'amount' => $amount,
                    'admin_fee' => $adminFee,
                    'net_amount' => $netAmount,
                    'bank_name' => $bank,
                    'bank_account_number' => fake()->numerify('##############'),
                    'bank_account_name' => $agency->contact_person ?? fake('id_ID')->name(),
                    'status' => 'completed',
                    'approved_by' => $adminId,
                    'approved_at' => now()->subDays(rand(7, 60)),
                    'transaction_id' => 'WD-' . strtoupper(substr(md5($agency->id . $i . 'completed'), 0, 10)),
                    'payment_detail' => json_encode([
                        'method' => 'bank_transfer',
                        'bank' => $bank,
                        'reference' => 'REF' . rand(100000, 999999),
                        'processed_at' => now()->subDays(rand(6, 59))->toDateTimeString(),
                    ]),
                    'completed_at' => now()->subDays(rand(6, 59)),
                ]);

                // Update wallet
                if ($wallet->total_withdrawn >= $amount) {
                    $wallet->update([
                        'total_withdrawn' => $wallet->total_withdrawn,
                        'available_balance' => max(0, $wallet->available_balance),
                    ]);
                }

                $totalWithdrawals++;
            }

            // ========================================
            // 2. WITHDRAWAL PROCESSING (1 transaksi)
            // ========================================
            $bank = fake()->randomElement($banks);
            $amount = rand(300000, 1500000);
            $adminFee = 5000;
            $netAmount = $amount - $adminFee;

            Withdrawal::create([
                'agency_id' => $agency->id,
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'net_amount' => $netAmount,
                'bank_name' => $bank,
                'bank_account_number' => fake()->numerify('##############'),
                'bank_account_name' => $agency->contact_person ?? fake('id_ID')->name(),
                'status' => 'processing',
                'approved_by' => $adminId,
                'approved_at' => now()->subHours(rand(1, 24)),
                'transaction_id' => 'WD-' . strtoupper(substr(md5($agency->id . 'processing'), 0, 10)),
                'payment_detail' => json_encode([
                    'method' => 'bank_transfer',
                    'bank' => $bank,
                    'reference' => 'REF' . rand(100000, 999999),
                    'processed_at' => now()->subHours(rand(1, 23))->toDateTimeString(),
                ]),
            ]);

            $totalWithdrawals++;

            // ========================================
            // 3. WITHDRAWAL PENDING (1 transaksi)
            // ========================================
            $bank = fake()->randomElement($banks);
            $amount = rand(200000, 2000000);
            $adminFee = 5000;
            $netAmount = $amount - $adminFee;

            Withdrawal::create([
                'agency_id' => $agency->id,
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'net_amount' => $netAmount,
                'bank_name' => $bank,
                'bank_account_number' => fake()->numerify('##############'),
                'bank_account_name' => $agency->contact_person ?? fake('id_ID')->name(),
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'transaction_id' => null,
                'payment_detail' => null,
            ]);

            $totalWithdrawals++;

            // ========================================
            // 4. WITHDRAWAL REJECTED (1 per 3 agency)
            // ========================================
            if ($index % 3 === 0) {
                $bank = fake()->randomElement($banks);
                $amount = rand(500000, 2500000);
                $adminFee = 5000;
                $netAmount = $amount - $adminFee;

                $rejectedReasons = [
                    'Saldo tidak mencukupi untuk penarikan ini.',
                    'Dokumen verifikasi belum lengkap.',
                    'Nomor rekening tidak valid.',
                    'Nama rekening tidak sesuai dengan data agency.',
                    'Batas maksimal penarikan harian terlampaui.',
                    'Akun bank sedang dalam proses verifikasi.',
                ];

                Withdrawal::create([
                    'agency_id' => $agency->id,
                    'amount' => $amount,
                    'admin_fee' => $adminFee,
                    'net_amount' => $netAmount,
                    'bank_name' => $bank,
                    'bank_account_number' => fake()->numerify('##############'),
                    'bank_account_name' => $agency->contact_person ?? fake('id_ID')->name(),
                    'status' => 'rejected',
                    'approved_by' => $adminId,
                    'approved_at' => now()->subDays(rand(3, 14)),
                    'rejected_reason' => fake()->randomElement($rejectedReasons),
                    'transaction_id' => 'WD-' . strtoupper(substr(md5($agency->id . 'rejected'), 0, 10)),
                    'payment_detail' => null,
                ]);

                $totalWithdrawals++;
            }

            // ========================================
            // 5. WITHDRAWAL FAILED (1 per 5 agency)
            // ========================================
            if ($index % 5 === 0) {
                $bank = fake()->randomElement($banks);
                $amount = rand(500000, 2000000);
                $adminFee = 5000;
                $netAmount = $amount - $adminFee;

                $failedReasons = [
                    'Transfer gagal - rekening tujuan tidak aktif.',
                    'Gangguan jaringan bank.',
                    'Batas waktu transfer terlampaui.',
                    'Kesalahan sistem - mohon coba lagi.',
                ];

                Withdrawal::create([
                    'agency_id' => $agency->id,
                    'amount' => $amount,
                    'admin_fee' => $adminFee,
                    'net_amount' => $netAmount,
                    'bank_name' => $bank,
                    'bank_account_number' => fake()->numerify('##############'),
                    'bank_account_name' => $agency->contact_person ?? fake('id_ID')->name(),
                    'status' => 'failed',
                    'approved_by' => $adminId,
                    'approved_at' => now()->subDays(rand(1, 7)),
                    'rejected_reason' => fake()->randomElement($failedReasons),
                    'transaction_id' => 'WD-' . strtoupper(substr(md5($agency->id . 'failed'), 0, 10)),
                    'payment_detail' => json_encode([
                        'method' => 'bank_transfer',
                        'bank' => $bank,
                        'reference' => 'REF' . rand(100000, 999999),
                        'error' => fake()->randomElement($failedReasons),
                    ]),
                ]);

                $totalWithdrawals++;
            }
        }

        echo "\n═══════════════════════════════════════════\n";
        echo "✅ WITHDRAWAL DATA GENERATED!\n";
        echo "═══════════════════════════════════════════\n";
        echo "📊 Total Withdrawals: {$totalWithdrawals}\n";
        echo "🏢 Agencies: {$agencies->count()}\n\n";

        echo "📋 STATUS BREAKDOWN:\n";
        echo "──────────────────────────────────────────────\n";
        echo "✅ Completed: " . Withdrawal::where('status', 'completed')->count() . "\n";
        echo "🔄 Processing: " . Withdrawal::where('status', 'processing')->count() . "\n";
        echo "⏳ Pending: " . Withdrawal::where('status', 'pending')->count() . "\n";
        echo "❌ Rejected: " . Withdrawal::where('status', 'rejected')->count() . "\n";
        echo "⚠️  Failed: " . Withdrawal::where('status', 'failed')->count() . "\n";
        echo "──────────────────────────────────────────────\n";
    }
}