<?php
// File: app/Services/WalletService.php
// Deskripsi: Service untuk manajemen dompet digital agency

namespace App\Services;

use App\Models\Agency;
use App\Models\AgencyWallet;
use App\Models\Booking;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Get or create wallet untuk agency
     */
    public function getOrCreateWallet(Agency $agency): AgencyWallet
    {
        $wallet = AgencyWallet::where('agency_id', $agency->id)->first();
        if (!$wallet) {
            $wallet = AgencyWallet::create([
                'agency_id' => $agency->id,
                'available_balance' => 0,
                'pending_balance' => 0,
                'deposit_balance' => 0,
                'cod_hold_balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
            ]);
        }
        return $wallet;
    }

    // ==================== BASIC CREDIT/DEBIT ====================

    public function creditWallet(Agency $agency, float $amount, string $description, string $refType, int $refId): void
    {
        DB::transaction(function () use ($agency, $amount, $description, $refType, $refId) {
            $wallet = $this->getOrCreateWallet($agency);
            $balanceBefore = (float) $wallet->available_balance;
            $balanceAfter = $balanceBefore + $amount;
            $wallet->update(['available_balance' => $balanceAfter, 'total_earned' => (float) $wallet->total_earned + $amount]);
            WalletTransaction::create([
                'agency_id' => $agency->id, 'type' => 'credit', 'amount' => $amount,
                'balance_before' => $balanceBefore, 'balance_after' => $balanceAfter,
                'description' => $description, 'reference_type' => $refType, 'reference_id' => $refId,
                'created_at' => now(),
            ]);
        });
    }

    public function debitWallet(Agency $agency, float $amount, string $description, string $refType, int $refId): void
    {
        DB::transaction(function () use ($agency, $amount, $description, $refType, $refId) {
            $wallet = $this->getOrCreateWallet($agency);
            if ((float) $wallet->available_balance < $amount) {
                throw new \Exception('Saldo tidak mencukupi.');
            }
            $balanceBefore = (float) $wallet->available_balance;
            $balanceAfter = $balanceBefore - $amount;
            $wallet->update(['available_balance' => $balanceAfter, 'total_withdrawn' => (float) $wallet->total_withdrawn + $amount]);
            WalletTransaction::create([
                'agency_id' => $agency->id, 'type' => 'debit', 'amount' => $amount,
                'balance_before' => $balanceBefore, 'balance_after' => $balanceAfter,
                'description' => $description, 'reference_type' => $refType, 'reference_id' => $refId,
                'created_at' => now(),
            ]);
        });
    }

    public function addPendingBalance(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $agency = $booking->schedule->agency;
            $wallet = $this->getOrCreateWallet($agency);
            $payment = $booking->payment;
            if ($payment) {
                $revenue = (float) $payment->agency_revenue;
                $wallet->update(['pending_balance' => (float) $wallet->pending_balance + $revenue]);
            }
        });
    }

    public function releaseFunds(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $agency = $booking->schedule->agency;
            $wallet = $this->getOrCreateWallet($agency);
            $payment = $booking->payment;
            if ($payment && (float) $payment->agency_revenue > 0) {
                $revenue = (float) $payment->agency_revenue;
                $wallet->update([
                    'pending_balance' => max(0, (float) $wallet->pending_balance - $revenue),
                    'available_balance' => (float) $wallet->available_balance + $revenue,
                ]);
                WalletTransaction::create([
                    'agency_id' => $agency->id, 'type' => 'credit', 'amount' => $revenue,
                    'balance_before' => (float) $wallet->available_balance,
                    'balance_after' => (float) $wallet->available_balance + $revenue,
                    'description' => "Dana dirilis untuk booking {$booking->booking_code}",
                    'reference_type' => 'booking', 'reference_id' => $booking->id, 'created_at' => now(),
                ]);
            }
        });
    }

    public function getBalance(Agency $agency): array
    {
        $wallet = $this->getOrCreateWallet($agency);
        return [
            'available_balance' => (float) $wallet->available_balance,
            'pending_balance' => (float) $wallet->pending_balance,
            'total_balance' => (float) $wallet->available_balance + (float) $wallet->pending_balance,
            'total_earned' => (float) $wallet->total_earned,
            'total_withdrawn' => (float) $wallet->total_withdrawn,
        ];
    }

    // ==================== DEPOSIT & COD ====================

    /**
     * Cek apakah agency bisa mengaktifkan COD di jadwal baru
     */
    public function canActivateCod(Agency $agency, float $requiredDeposit): bool
    {
        $wallet = $this->getOrCreateWallet($agency);
        $availableDeposit = (float) $wallet->deposit_balance - (float) $wallet->cod_hold_balance;
        return $availableDeposit >= $requiredDeposit;
    }

    /**
     * Cek apakah agency bisa menggunakan COD (untuk validasi customer)
     */
    public function canUseCod(Agency $agency, float $minBalance = 500000): bool
    {
        $wallet = $this->getOrCreateWallet($agency);
        $availableDeposit = (float) $wallet->deposit_balance - (float) $wallet->cod_hold_balance;
        return $availableDeposit >= $minBalance;
    }

    /**
     * Hold saldo deposit untuk jadwal COD (saat jadwal dibuat)
     */
    public function holdCodDeposit(Agency $agency, float $amount, int $scheduleId): void
    {
        DB::transaction(function () use ($agency, $amount, $scheduleId) {
            $wallet = $this->getOrCreateWallet($agency);
            $before = (float) $wallet->cod_hold_balance;
            $after = $before + $amount;
            $wallet->update(['cod_hold_balance' => $after]);
            WalletTransaction::create([
                'agency_id' => $agency->id, 'type' => 'debit', 'amount' => $amount,
                'balance_before' => $before, 'balance_after' => $after,
                'description' => 'Hold Saldo COD untuk Jadwal #' . $scheduleId,
                'reference_type' => 'cod_schedule_hold', 'reference_id' => $scheduleId, 'created_at' => now(),
            ]);
        });
    }

    /**
     * Release saldo deposit untuk jadwal COD (saat jadwal selesai/dibatalkan)
     */
    public function releaseCodDeposit(Agency $agency, float $amount, int $scheduleId): void
    {
        DB::transaction(function () use ($agency, $amount, $scheduleId) {
            $wallet = $this->getOrCreateWallet($agency);
            $holdBefore = (float) $wallet->cod_hold_balance;
            $holdAfter = max(0, $holdBefore - $amount);
            $availBefore = (float) $wallet->available_balance;
            $availAfter = $availBefore + $amount;
            $wallet->update([
                'cod_hold_balance' => $holdAfter,
                'available_balance' => $availAfter,
            ]);
            WalletTransaction::create([
                'agency_id' => $agency->id, 'type' => 'credit', 'amount' => $amount,
                'balance_before' => $holdBefore, 'balance_after' => $holdAfter,
                'description' => 'Release Saldo COD Jadwal #' . $scheduleId . ' (Selesai) → Masuk ke Saldo Tersedia',
                'reference_type' => 'cod_schedule_release', 'reference_id' => $scheduleId, 'created_at' => now(),
            ]);
        });
    }

    /**
     * Hold saldo COD untuk booking (saat customer pilih COD)
     */
    public function holdCodBalance(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $agency = $booking->schedule->agency;
            $wallet = $this->getOrCreateWallet($agency);
            $before = (float) $wallet->cod_hold_balance;
            $after = $before + (float) $booking->total_price;
            $wallet->update(['cod_hold_balance' => $after]);
            WalletTransaction::create([
                'agency_id' => $agency->id, 'type' => 'debit', 'amount' => (float) $booking->total_price,
                'balance_before' => $before, 'balance_after' => $after,
                'description' => 'Hold COD Booking ' . $booking->booking_code,
                'reference_type' => 'cod_booking_hold', 'reference_id' => $booking->id, 'created_at' => now(),
            ]);
        });
    }

    /**
     * Release saldo COD untuk booking (saat driver konfirmasi)
     */
    public function releaseCodBalance(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $agency = $booking->schedule->agency;
            $wallet = $this->getOrCreateWallet($agency);
            $before = (float) $wallet->cod_hold_balance;
            $after = max(0, $before - (float) $booking->total_price);
            $availBefore = (float) $wallet->available_balance;
            $availAfter = $availBefore + (float) $booking->total_price;
            $wallet->update([
                'cod_hold_balance' => $after,
                'available_balance' => $availAfter,
            ]);
            WalletTransaction::create([
                'agency_id' => $agency->id, 'type' => 'credit', 'amount' => (float) $booking->total_price,
                'balance_before' => $before, 'balance_after' => $after,
                'description' => 'Release COD Booking ' . $booking->booking_code . ' (Dikonfirmasi) → Masuk ke Saldo Tersedia',
                'reference_type' => 'cod_booking_release', 'reference_id' => $booking->id, 'created_at' => now(),
            ]);
        });
    }

    // ==================== TOP UP ====================

    public function createTopUpTransaction(Agency $agency, float $amount): array
    {
        $wallet = $this->getOrCreateWallet($agency);
        $adminFee = (float) \App\Models\PlatformSetting::getValue('topup_admin_fee', 3500);
        $totalAmount = $amount + $adminFee;
        $serverKey = config('gomad.midtrans.server_key');
        $isProduction = config('gomad.midtrans.is_production', false);
        $baseUrl = $isProduction ? 'https://app.midtrans.com/snap/v1/transactions' : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
        $orderId = 'TOPUP-' . $agency->id . '-' . time();
        $payload = [
            'transaction_details' => ['order_id' => $orderId, 'gross_amount' => (int) $totalAmount],
            'customer_details' => ['first_name' => $agency->agency_name, 'email' => $agency->user->email ?? 'agency@gomad.id', 'phone' => $agency->contact_alternate ?? $agency->user->phone ?? ''],
            'item_details' => [
                ['id' => 'TOPUP', 'price' => (int) $amount, 'quantity' => 1, 'name' => 'Top Up Saldo Deposit - ' . $agency->agency_name],
                ['id' => 'ADMIN', 'price' => (int) $adminFee, 'quantity' => 1, 'name' => 'Biaya Admin Top Up'],
            ],
        ];
        try {
            $response = \Illuminate\Support\Facades\Http::withBasicAuth($serverKey, '')
                ->withHeaders(['Content-Type' => 'application/json'])->post($baseUrl, $payload);
            if ($response->successful()) {
                $result = $response->json();
                return ['success' => true, 'snap_token' => $result['token'] ?? '', 'order_id' => $orderId, 'admin_fee' => $adminFee, 'total_amount' => $totalAmount];
            }
            return ['success' => false, 'message' => 'Gagal membuat pembayaran'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function processTopUpCallback(array $payload): void
    {
        $orderId = $payload['order_id'] ?? '';
        if (!str_starts_with($orderId, 'TOPUP-')) return;
        $transactionStatus = $payload['transaction_status'] ?? '';
        if (in_array($transactionStatus, ['capture', 'settlement'])) {
            preg_match('/^TOPUP-(\d+)-\d+$/', $orderId, $matches);
            if (empty($matches[1])) return;
            $agencyId = (int) $matches[1];
            $agency = Agency::find($agencyId);
            if (!$agency) return;
            $totalAmount = (float) ($payload['gross_amount'] ?? 0);
            $adminFee = (float) \App\Models\PlatformSetting::getValue('topup_admin_fee', 3500);
            $depositAmount = $totalAmount - $adminFee;
            $wallet = $this->getOrCreateWallet($agency);
            $balanceBefore = (float) $wallet->deposit_balance;
            $balanceAfter = $balanceBefore + $depositAmount;
            $wallet->update(['deposit_balance' => $balanceAfter]);
            WalletTransaction::create([
                'agency_id' => $agency->id, 'type' => 'credit', 'amount' => $depositAmount,
                'balance_before' => $balanceBefore, 'balance_after' => $balanceAfter,
                'description' => 'Top Up Saldo Deposit (Rp ' . number_format($depositAmount, 0, ',', '.') . ' + Biaya Admin Rp ' . number_format($adminFee, 0, ',', '.') . ')',
                'reference_type' => 'topup', 'reference_id' => $orderId, 'created_at' => now(),
            ]);
        }
    }

    // ==================== TRANSFER ====================

    public function transferToDeposit(Agency $agency, float $amount): void
    {
        $wallet = $this->getOrCreateWallet($agency);
        if ((float) $wallet->available_balance < $amount) {
            throw new \Exception('Saldo tersedia tidak mencukupi.');
        }
        if ($amount < 10000) {
            throw new \Exception('Minimal transfer Rp 10.000.');
        }
        DB::transaction(function () use ($agency, $amount) {
            $wallet = $this->getOrCreateWallet($agency);
            $availBefore = (float) $wallet->available_balance;
            $availAfter = $availBefore - $amount;
            $depoBefore = (float) $wallet->deposit_balance;
            $depoAfter = $depoBefore + $amount;
            $wallet->update(['available_balance' => $availAfter, 'deposit_balance' => $depoAfter]);
            WalletTransaction::create([
                'agency_id' => $agency->id, 'type' => 'debit', 'amount' => $amount,
                'balance_before' => $availBefore, 'balance_after' => $availAfter,
                'description' => 'Transfer ke Saldo Deposit', 'reference_type' => 'transfer_to_deposit',
                'reference_id' => null, 'created_at' => now(),
            ]);
            WalletTransaction::create([
                'agency_id' => $agency->id, 'type' => 'credit', 'amount' => $amount,
                'balance_before' => $depoBefore, 'balance_after' => $depoAfter,
                'description' => 'Transfer dari Saldo Tersedia', 'reference_type' => 'transfer_from_available',
                'reference_id' => null, 'created_at' => now(),
            ]);
        });
    }

    // ==================== GETTERS ====================

    public function getTransactionHistory(Agency $agency, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return WalletTransaction::where('agency_id', $agency->id)->latest('created_at')->limit($limit)->get();
    }

    public function getMutationHistory(Agency $agency, ?string $type = null, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $query = WalletTransaction::where('agency_id', $agency->id)->latest('created_at');
        if ($type) $query->where('reference_type', $type);
        return $query->limit($limit)->get();
    }

    public function getBalanceSummary(Agency $agency): array
    {
        $wallet = $this->getOrCreateWallet($agency);
        return [
            'available_balance' => (float) $wallet->available_balance,
            'pending_balance' => (float) $wallet->pending_balance,
            'deposit_balance' => (float) $wallet->deposit_balance,
            'cod_hold_balance' => (float) $wallet->cod_hold_balance,
            'available_deposit' => (float) $wallet->deposit_balance - (float) $wallet->cod_hold_balance,
            'total_earned' => (float) $wallet->total_earned,
            'total_withdrawn' => (float) $wallet->total_withdrawn,
        ];
    }
}

// End of file