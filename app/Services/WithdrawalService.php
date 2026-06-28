<?php
// File: app/Services/WithdrawalService.php
// Deskripsi: Service untuk penarikan dana agency via Midtrans disbursement

namespace App\Services;

use App\Enums\WithdrawalStatus;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidWithdrawalException;
use App\Models\Agency;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WithdrawalService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly NotificationService $notificationService,
    ) {}

    public function createWithdrawal(Agency $agency, array $data): Withdrawal
    {
        return DB::transaction(function () use ($agency, $data) {
            $balance = $this->walletService->getBalance($agency);
            $minimalWithdrawal = (float) PlatformSetting::getValue('minimal_withdrawal', 100000);
            $adminFee = (float) PlatformSetting::getValue('withdrawal_admin_fee', 5000);
            
            $amount = (float) $data['amount'];
            
            if ($amount < $minimalWithdrawal) {
                throw new InvalidWithdrawalException(
                    'Minimal penarikan adalah Rp ' . number_format($minimalWithdrawal, 0, ',', '.')
                );
            }
            
            if ($amount > $balance['available_balance']) {
                throw new InsufficientBalanceException(
                    'Saldo tidak mencukupi. Saldo tersedia: Rp ' . number_format($balance['available_balance'], 0, ',', '.')
                );
            }
            
            $netAmount = $amount - $adminFee;
            
            if ($netAmount <= 0) {
                throw new InvalidWithdrawalException('Jumlah penarikan setelah biaya admin harus lebih dari Rp 0.');
            }
            
            $autoApproveLimit = (float) PlatformSetting::getValue('auto_approve_limit', 5000000);
            $initialStatus = $amount < $autoApproveLimit ? WithdrawalStatus::APPROVED : WithdrawalStatus::PENDING;
            
            $withdrawal = Withdrawal::create([
                'agency_id' => $agency->id,
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'net_amount' => $netAmount,
                'bank_name' => $data['bank_name'],
                'bank_account_number' => $data['bank_account_number'],
                'bank_account_name' => $data['bank_account_name'],
                'status' => $initialStatus->value,
            ]);
            
            $this->walletService->debitWallet(
                $agency,
                $amount,
                'Penarikan dana #' . $withdrawal->id,
                'withdrawal',
                $withdrawal->id
            );
            
            if ($initialStatus === WithdrawalStatus::APPROVED) {
                $withdrawal->update([
                    'approved_at' => now(),
                    'status' => WithdrawalStatus::PROCESSING->value,
                ]);
                
                // Process disbursement in background
                dispatch(function () use ($withdrawal) {
                    $this->processDisbursement($withdrawal);
                })->afterResponse();
            }
            
            return $withdrawal;
        });
    }

    public function approveWithdrawal(Withdrawal $withdrawal, User $admin): void
    {
        DB::transaction(function () use ($withdrawal, $admin) {
            if ($withdrawal->status !== WithdrawalStatus::PENDING->value) {
                throw new \Exception('Withdrawal tidak dalam status pending.');
            }
            
            $withdrawal->update([
                'status' => WithdrawalStatus::APPROVED->value,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);
            
            $withdrawal->update(['status' => WithdrawalStatus::PROCESSING->value]);
            
            dispatch(function () use ($withdrawal) {
                $this->processDisbursement($withdrawal);
            })->afterResponse();
            
            $this->notificationService->withdrawalApproved($withdrawal);
        });
    }

    public function rejectWithdrawal(Withdrawal $withdrawal, User $admin, string $reason): void
    {
        DB::transaction(function () use ($withdrawal, $admin, $reason) {
            if ($withdrawal->status !== WithdrawalStatus::PENDING->value) {
                throw new \Exception('Withdrawal tidak dalam status pending.');
            }
            
            $withdrawal->update([
                'status' => WithdrawalStatus::REJECTED->value,
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'rejected_reason' => $reason,
            ]);
            
            $agency = $withdrawal->agency;
            $this->walletService->creditWallet(
                $agency,
                $withdrawal->amount,
                'Pengembalian dana withdrawal #' . $withdrawal->id . ' (Ditolak)',
                'withdrawal_refund',
                $withdrawal->id
            );
            
            $this->notificationService->withdrawalRejected($withdrawal, $reason);
        });
    }

    public function processDisbursement(Withdrawal $withdrawal): void
    {
        $isProduction = config('gomad.midtrans.is_production', false);
        
        // ========== SANDBOX MODE: SIMULASI SAJA ==========
        if (!$isProduction) {
            Log::info('SANDBOX: Simulating withdrawal disbursement', [
                'withdrawal_id' => $withdrawal->id,
                'amount' => $withdrawal->net_amount,
                'bank' => $withdrawal->bank_name,
                'account' => $withdrawal->bank_account_number,
            ]);
            
            // Simulasi sukses (tanpa panggil API)
            $withdrawal->update([
                'status' => WithdrawalStatus::COMPLETED->value,
                'transaction_id' => 'SIM-' . $withdrawal->id . '-' . time(),
                'payment_detail' => [
                    'mode' => 'sandbox_simulation',
                    'simulated_at' => now()->toIso8601String(),
                    'bank' => $withdrawal->bank_name,
                    'account' => $withdrawal->bank_account_number,
                    'amount' => $withdrawal->net_amount,
                ],
                'completed_at' => now(),
            ]);
            
            Log::info('SANDBOX: Withdrawal simulation completed', ['withdrawal_id' => $withdrawal->id]);
            
            // Kirim notifikasi ke agency (opsional, untuk testing)
            $this->notificationService->sendWhatsApp(
                $withdrawal->agency->user->phone,
                "🏦 *SIMULASI WITHDRAWAL (SANDBOX)*\n\n" .
                "Penarikan dana #{$withdrawal->id} telah diproses (SIMULASI).\n" .
                "Jumlah: Rp " . number_format($withdrawal->net_amount, 0, ',', '.') . "\n" .
                "Bank: {$withdrawal->bank_name}\n" .
                "Rekening: {$withdrawal->bank_account_number}\n\n" .
                "⚠️ Ini hanya simulasi. Di production, dana akan dikirim ke rekening Anda."
            );
            
            return;
        }
        
        // ========== PRODUCTION MODE: PANGGIL API ASLI ==========
        try {
            $serverKey = config('gomad.midtrans.server_key');
            
            $baseUrl = 'https://app.midtrans.com/iris/api/v1/beneficiaries';
            
            // Create beneficiary first
            $beneficiaryPayload = [
                'name' => $withdrawal->bank_account_name,
                'account' => $withdrawal->bank_account_number,
                'bank' => $this->getBankCode($withdrawal->bank_name),
            ];
            
            $beneficiaryResponse = Http::withBasicAuth($serverKey, '')
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($baseUrl, $beneficiaryPayload);
            
            if (!$beneficiaryResponse->successful()) {
                Log::error('Midtrans Beneficiary Creation Failed', [
                    'withdrawal_id' => $withdrawal->id,
                    'response' => $beneficiaryResponse->body(),
                ]);
                throw new \Exception('Gagal membuat beneficiary: ' . $beneficiaryResponse->body());
            }
            
            $beneficiaryData = $beneficiaryResponse->json();
            $beneficiaryNickname = $beneficiaryData['nickname'] ?? null;
            
            if (!$beneficiaryNickname) {
                throw new \Exception('Beneficiary nickname not found in response.');
            }
            
            // Create payout
            $payoutUrl = 'https://app.midtrans.com/iris/api/v1/payouts';
            
            $payoutPayload = [
                'payouts' => [
                    [
                        'beneficiary_name' => $beneficiaryNickname,
                        'amount' => (string) $withdrawal->net_amount,
                        'notes' => 'Withdrawal #' . $withdrawal->id . ' - GoMad',
                    ],
                ],
            ];
            
            $payoutResponse = Http::withBasicAuth($serverKey, '')
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Idempotency-Key' => 'withdrawal-' . $withdrawal->id . '-' . time(),
                ])
                ->post($payoutUrl, $payoutPayload);
            
            if ($payoutResponse->successful()) {
                $payoutData = $payoutResponse->json();
                
                $withdrawal->update([
                    'status' => WithdrawalStatus::COMPLETED->value,
                    'transaction_id' => $payoutData['payouts'][0]['reference_no'] ?? null,
                    'payment_detail' => $payoutData,
                    'completed_at' => now(),
                ]);
                
                Log::info('Withdrawal Disbursement Success', [
                    'withdrawal_id' => $withdrawal->id,
                    'response' => $payoutData,
                ]);
            } else {
                Log::error('Midtrans Payout Failed', [
                    'withdrawal_id' => $withdrawal->id,
                    'response' => $payoutResponse->body(),
                ]);
                
                $withdrawal->update([
                    'status' => WithdrawalStatus::FAILED->value,
                    'payment_detail' => ['error' => $payoutResponse->body()],
                ]);
                
                // Refund to wallet
                $agency = $withdrawal->agency;
                $this->walletService->creditWallet(
                    $agency,
                    $withdrawal->amount,
                    'Pengembalian dana withdrawal gagal #' . $withdrawal->id,
                    'withdrawal_failed_refund',
                    $withdrawal->id
                );
            }
        } catch (\Exception $e) {
            Log::error('Withdrawal Disbursement Exception', [
                'withdrawal_id' => $withdrawal->id,
                'error' => $e->getMessage(),
            ]);
            
            $withdrawal->update([
                'status' => WithdrawalStatus::FAILED->value,
                'payment_detail' => ['error' => $e->getMessage()],
            ]);
            
            // Refund to wallet
            $agency = $withdrawal->agency;
            $this->walletService->creditWallet(
                $agency,
                $withdrawal->amount,
                'Pengembalian dana withdrawal error #' . $withdrawal->id,
                'withdrawal_error_refund',
                $withdrawal->id
            );
        }
    }

    public function handleDisbursementCallback(array $payload): void
    {
        Log::info('Midtrans Disbursement Callback', $payload);
        
        $referenceNo = $payload['reference_no'] ?? null;
        $status = $payload['status'] ?? null;
        
        if (!$referenceNo) {
            Log::error('Disbursement callback missing reference_no');
            return;
        }
        
        $withdrawal = Withdrawal::where('transaction_id', $referenceNo)->first();
        
        if (!$withdrawal) {
            Log::error('Withdrawal not found for reference: ' . $referenceNo);
            return;
        }
        
        if ($status === 'completed' || $status === 'success') {
            $withdrawal->update([
                'status' => WithdrawalStatus::COMPLETED->value,
                'completed_at' => now(),
            ]);
        } elseif (in_array($status, ['failed', 'rejected', 'cancelled'])) {
            $withdrawal->update([
                'status' => WithdrawalStatus::FAILED->value,
                'payment_detail' => array_merge($withdrawal->payment_detail ?? [], ['callback' => $payload]),
            ]);
            
            // Refund
            $agency = $withdrawal->agency;
            $this->walletService->creditWallet(
                $agency,
                $withdrawal->amount,
                'Pengembalian dana withdrawal #' . $withdrawal->id,
                'withdrawal_callback_refund',
                $withdrawal->id
            );
        }
    }

    private function getBankCode(string $bankName): string
    {
        $bankCodes = [
            'bca' => 'bca',
            'bni' => 'bni',
            'bri' => 'bri',
            'mandiri' => 'mandiri',
            'cimb' => 'cimb_niaga',
            'danamon' => 'danamon',
            'permata' => 'permata',
            'bsi' => 'bsi',
        ];
        
        $bankNameLower = strtolower(trim($bankName));
        
        return $bankCodes[$bankNameLower] ?? $bankNameLower;
    }

    public function getAgencyWithdrawals(Agency $agency, ?string $status = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Withdrawal::where('agency_id', $agency->id)->latest();
        
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->get();
    }

    public function getPendingWithdrawals(): \Illuminate\Database\Eloquent\Collection
    {
        return Withdrawal::with(['agency.user', 'agency.wallet'])
            ->where('status', WithdrawalStatus::PENDING->value)
            ->latest()
            ->get();
    }
}

// End of file