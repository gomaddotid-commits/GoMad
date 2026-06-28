<?php
// File: database/seeders/WalletTransactionSeeder.php
// Deskripsi: Seeder untuk data transaksi wallet agency

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\AgencyWallet;
use App\Models\Withdrawal;
use Illuminate\Database\Seeder;

class WalletTransactionSeeder extends Seeder
{
    public function run(): void
    {
        echo "💰 GENERATING WALLET TRANSACTIONS...\n";
        echo "═══════════════════════════════════════════\n\n";

        // Ambil agency VERIFIED yang punya wallet
        $agencies = Agency::where('is_verified', true)
            ->whereHas('wallet')
            ->with('wallet')
            ->get();

        if ($agencies->isEmpty()) {
            echo "⚠️  Tidak ada agency verified dengan wallet\n";
            return;
        }

        $transactionCount = 0;

        foreach ($agencies as $agency) {
            $wallet = $agency->wallet;
            $currentBalance = $wallet->available_balance + $wallet->pending_balance;

            // ========================================
            // 1. TOP UP transactions (3-5 per agency)
            // ========================================
            $topupCount = rand(3, 5);
            $balanceBefore = 0;

            for ($i = 0; $i < $topupCount; $i++) {
                $amount = rand(500000, 5000000);
                $balanceAfter = $balanceBefore + $amount;

                \App\Models\WalletTransaction::create([
                    'agency_id' => $agency->id,
                    'type' => 'credit',
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'description' => 'Top Up Deposit',
                    'reference_type' => 'topup',
                    'reference_id' => null,
                    'created_at' => now()->subDays(rand(30, 365)),
                ]);

                $balanceBefore = $balanceAfter;
                $transactionCount++;
            }

            // ========================================
            // 2. COMMISSION transactions (5-10 per agency)
            // ========================================
            $commissionCount = rand(5, 10);
            for ($i = 0; $i < $commissionCount; $i++) {
                $amount = rand(5000, 50000);
                $balanceAfter = $balanceBefore + $amount;

                \App\Models\WalletTransaction::create([
                    'agency_id' => $agency->id,
                    'type' => 'credit',
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'description' => 'Komisi booking',
                    'reference_type' => 'booking',
                    'reference_id' => rand(1, 500),
                    'created_at' => now()->subDays(rand(1, 60)),
                ]);

                $balanceBefore = $balanceAfter;
                $transactionCount++;
            }

            // ========================================
            // 3. WITHDRAWAL transactions (based on actual withdrawals)
            // ========================================
            $withdrawals = Withdrawal::where('agency_id', $agency->id)
                ->whereIn('status', ['completed', 'processing', 'approved'])
                ->get();

            foreach ($withdrawals as $withdrawal) {
                $balanceAfter = $balanceBefore - $withdrawal->net_amount;

                \App\Models\WalletTransaction::create([
                    'agency_id' => $agency->id,
                    'type' => 'debit',
                    'amount' => $withdrawal->net_amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => max(0, $balanceAfter),
                    'description' => 'Penarikan dana - ' . $withdrawal->bank_name,
                    'reference_type' => 'withdrawal',
                    'reference_id' => $withdrawal->id,
                    'created_at' => $withdrawal->created_at ?? now()->subDays(rand(1, 30)),
                ]);

                $balanceBefore = max(0, $balanceAfter);
                $transactionCount++;
            }

            // ========================================
            // 4. COD PAYMENT transactions (2-5 per agency)
            // ========================================
            $codCount = rand(2, 5);
            for ($i = 0; $i < $codCount; $i++) {
                $amount = rand(100000, 500000);
                $balanceAfter = $balanceBefore + $amount;

                \App\Models\WalletTransaction::create([
                    'agency_id' => $agency->id,
                    'type' => 'credit',
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'description' => 'Pembayaran COD diterima',
                    'reference_type' => 'cod_payment',
                    'reference_id' => rand(1, 100),
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);

                $balanceBefore = $balanceAfter;
                $transactionCount++;
            }

            // ========================================
            // 5. REFUND transactions (1-2 per agency)
            // ========================================
            $refundCount = rand(1, 2);
            for ($i = 0; $i < $refundCount; $i++) {
                $amount = rand(50000, 300000);
                $balanceAfter = $balanceBefore - $amount;

                \App\Models\WalletTransaction::create([
                    'agency_id' => $agency->id,
                    'type' => 'debit',
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => max(0, $balanceAfter),
                    'description' => 'Refund booking dibatalkan',
                    'reference_type' => 'refund',
                    'reference_id' => rand(1, 50),
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);

                $balanceBefore = max(0, $balanceAfter);
                $transactionCount++;
            }
        }

        echo "\n═══════════════════════════════════════════\n";
        echo "✅ {$transactionCount} Wallet Transactions created\n";
        echo "═══════════════════════════════════════════\n\n";

        echo "📊 TRANSACTION TYPE BREAKDOWN:\n";
        echo "──────────────────────────────────────────────\n";
        $creditCount = \App\Models\WalletTransaction::where('type', 'credit')->count();
        $debitCount = \App\Models\WalletTransaction::where('type', 'debit')->count();
        echo "  📈 Credit: {$creditCount}\n";
        echo "  📉 Debit: {$debitCount}\n";
        echo "──────────────────────────────────────────────\n";
    }
}