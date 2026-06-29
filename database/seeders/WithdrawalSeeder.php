<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agency;
use App\Models\AgencyWallet;
use App\Models\User;
use App\Models\Withdrawal;

class WithdrawalSeeder extends Seeder
{
    public function run(): void
    {
        echo "💰 GENERATING WITHDRAWAL DATA...\n";
        echo "═══════════════════════════════════════════\n\n";

        $adminId = User::where('email', 'admin@gomad.id')->first()?->id ?? 1;
        $agencies = Agency::where('is_verified', true)->get();

        if ($agencies->isEmpty()) {
            echo "⚠️  Tidak ada agency verified untuk withdrawal\n";
            return;
        }

        $totalWithdrawals = 0;
        $banks = ['BCA', 'BRI', 'BNI', 'Mandiri', 'CIMB Niaga', 'BTN', 'BJB'];
        $names = ['Budi Santoso', 'Ani Rahmawati', 'Joko Widodo', 'Siti Nurhaliza', 'Ahmad Dhani', 'Rina Nose'];

        foreach ($agencies as $index => $agency) {
            $wallet = AgencyWallet::where('agency_id', $agency->id)->first();

            if (!$wallet || $wallet->total_earned <= 0) {
                continue;
            }

            // Helper functions
            $randomBank = function() use ($banks) {
                return $banks[array_rand($banks)];
            };
            $randomName = function() use ($names) {
                return $names[array_rand($names)];
            };
            $randomAccount = function() {
                return (string) rand(10000000000000, 99999999999999);
            };
            $randomTransactionId = function($prefix) {
                return $prefix . '-' . strtoupper(substr(md5(rand()), 0, 10));
            };

            // Completed (2x)
            for ($i = 0; $i < 2; $i++) {
                $amount = rand(500000, 3000000);
                $adminFee = 5000;

                Withdrawal::create([
                    'agency_id' => $agency->id,
                    'amount' => $amount,
                    'admin_fee' => $adminFee,
                    'net_amount' => $amount - $adminFee,
                    'bank_name' => $randomBank(),
                    'bank_account_number' => $randomAccount(),
                    'bank_account_name' => $agency->contact_person ?? $randomName(),
                    'status' => 'completed',
                    'approved_by' => $adminId,
                    'approved_at' => now()->subDays(rand(7, 60)),
                    'transaction_id' => $randomTransactionId('WD'),
                    'payment_detail' => json_encode([
                        'method' => 'bank_transfer',
                        'bank' => $randomBank(),
                        'reference' => 'REF' . rand(100000, 999999),
                        'processed_at' => now()->subDays(rand(6, 59))->toDateTimeString(),
                    ]),
                    'completed_at' => now()->subDays(rand(6, 59)),
                ]);

                $totalWithdrawals++;
            }

            // Processing (1x)
            $amount = rand(300000, 1500000);
            $adminFee = 5000;

            Withdrawal::create([
                'agency_id' => $agency->id,
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'net_amount' => $amount - $adminFee,
                'bank_name' => $randomBank(),
                'bank_account_number' => $randomAccount(),
                'bank_account_name' => $agency->contact_person ?? $randomName(),
                'status' => 'processing',
                'approved_by' => $adminId,
                'approved_at' => now()->subHours(rand(1, 24)),
                'transaction_id' => $randomTransactionId('WD'),
                'payment_detail' => json_encode([
                    'method' => 'bank_transfer',
                    'bank' => $randomBank(),
                    'reference' => 'REF' . rand(100000, 999999),
                    'processed_at' => now()->subHours(rand(1, 23))->toDateTimeString(),
                ]),
            ]);

            $totalWithdrawals++;

            // Pending (1x)
            $amount = rand(200000, 2000000);
            $adminFee = 5000;

            Withdrawal::create([
                'agency_id' => $agency->id,
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'net_amount' => $amount - $adminFee,
                'bank_name' => $randomBank(),
                'bank_account_number' => $randomAccount(),
                'bank_account_name' => $agency->contact_person ?? $randomName(),
                'status' => 'pending',
            ]);

            $totalWithdrawals++;

            // Rejected (1 per 3 agency)
            if ($index % 3 === 0) {
                $amount = rand(500000, 2500000);
                $adminFee = 5000;

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
                    'net_amount' => $amount - $adminFee,
                    'bank_name' => $randomBank(),
                    'bank_account_number' => $randomAccount(),
                    'bank_account_name' => $agency->contact_person ?? $randomName(),
                    'status' => 'rejected',
                    'approved_by' => $adminId,
                    'approved_at' => now()->subDays(rand(3, 14)),
                    'rejected_reason' => $rejectedReasons[array_rand($rejectedReasons)],
                    'transaction_id' => $randomTransactionId('WD'),
                ]);

                $totalWithdrawals++;
            }

            // Failed (1 per 5 agency)
            if ($index % 5 === 0) {
                $amount = rand(500000, 2000000);
                $adminFee = 5000;

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
                    'net_amount' => $amount - $adminFee,
                    'bank_name' => $randomBank(),
                    'bank_account_number' => $randomAccount(),
                    'bank_account_name' => $agency->contact_person ?? $randomName(),
                    'status' => 'failed',
                    'approved_by' => $adminId,
                    'approved_at' => now()->subDays(rand(1, 7)),
                    'rejected_reason' => $failedReasons[array_rand($failedReasons)],
                    'transaction_id' => $randomTransactionId('WD'),
                    'payment_detail' => json_encode([
                        'method' => 'bank_transfer',
                        'bank' => $randomBank(),
                        'reference' => 'REF' . rand(100000, 999999),
                        'error' => $failedReasons[array_rand($failedReasons)],
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