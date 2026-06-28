<?php
// File: app/Services/SettlementService.php
// Deskripsi: Service untuk settlement (rekonsiliasi) warung setiap Senin

namespace App\Services;

use App\Enums\SettlementStatus;
use App\Models\CashPayment;
use App\Models\PaymentAgent;
use App\Models\PlatformSetting;
use App\Models\Settlement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SettlementService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function generateWeeklySettlements(): void
    {
        $now = Carbon::now();
        $periodEnd = $now->copy()->startOfWeek(Carbon::MONDAY)->subDay();
        $periodStart = $periodEnd->copy()->subDays(6);
        
        if ($now->dayOfWeek !== Carbon::MONDAY) {
            Log::info('Settlement: Not Monday, skipping generation.');
            return;
        }
        
        Log::info("Generating settlements for period: {$periodStart->toDateString()} to {$periodEnd->toDateString()}");
        
        $agents = PaymentAgent::where('is_active', true)->where('is_verified', true)->get();
        
        foreach ($agents as $agent) {
            $this->generateAgentSettlement($agent, $periodStart, $periodEnd);
        }
        
        Log::info('Weekly settlements generation completed.');
    }

    public function generateAgentSettlement(PaymentAgent $agent, Carbon $periodStart, Carbon $periodEnd): ?Settlement
    {
        return DB::transaction(function () use ($agent, $periodStart, $periodEnd) {
            // Check if settlement already exists for this period
            $existingSettlement = Settlement::where('payment_agent_id', $agent->id)
                ->where('period_start', $periodStart->toDateString())
                ->where('period_end', $periodEnd->toDateString())
                ->first();
            
            if ($existingSettlement) {
                Log::info("Settlement already exists for agent {$agent->agent_name} in this period.");
                return $existingSettlement;
            }
            
            // Get all confirmed cash payments in this period that are not yet settled
            $cashPayments = CashPayment::where('payment_agent_id', $agent->id)
                ->where('status', 'confirmed')
                ->whereNull('settlement_id')
                ->whereBetween('confirmed_at', [
                    $periodStart->startOfDay(),
                    $periodEnd->endOfDay(),
                ])
                ->get();
            
            if ($cashPayments->isEmpty()) {
                Log::info("No unsettled cash payments for agent {$agent->agent_name} in this period.");
                return null;
            }
            
            $totalTransactions = $cashPayments->count();
            $totalAmount = $cashPayments->sum('amount');
            $totalCommission = $cashPayments->sum('agent_commission');
            $amountToSettle = $totalAmount - $totalCommission;
            
            $settlement = Settlement::create([
                'payment_agent_id' => $agent->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'total_transactions' => $totalTransactions,
                'total_amount' => $totalAmount,
                'total_commission' => $totalCommission,
                'amount_to_settle' => $amountToSettle,
                'status' => SettlementStatus::PENDING->value,
            ]);
            
            // Link cash payments to this settlement
            CashPayment::whereIn('id', $cashPayments->pluck('id'))
                ->update([
                    'settlement_id' => $settlement->id,
                    'status' => 'settled',
                    'settled_at' => now(),
                ]);
            
            // Update agent's balance to settle
            $agent->update([
                'balance_to_settle' => max(0, (float) $agent->balance_to_settle - $amountToSettle),
                'last_settlement_at' => now(),
            ]);
            
            $this->notificationService->settlementGenerated($settlement);
            
            Log::info("Settlement generated for agent {$agent->agent_name}: Rp " . number_format($amountToSettle, 0, ',', '.'));
            
            return $settlement;
        });
    }

    public function paySettlement(Settlement $settlement): string
    {
        if ($settlement->status !== SettlementStatus::PENDING->value) {
            throw new \Exception('Settlement tidak dalam status pending.');
        }
        
        $serverKey = config('gomad.midtrans.server_key');
        $isProduction = config('gomad.midtrans.is_production', false);
        
        $baseUrl = $isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
        
        $agent = $settlement->paymentAgent;
        $orderId = 'STL-' . $settlement->id . '-' . time();
        
        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $settlement->amount_to_settle,
            ],
            'customer_details' => [
                'first_name' => $agent->owner_name,
                'email' => $agent->user->email ?? 'warung@gomad.id',
                'phone' => $agent->owner_phone,
            ],
            'callbacks' => [
                'finish' => config('app.url') . '/payment-agent/settlements/' . $settlement->id,
            ],
        ];
        
        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($baseUrl, $payload);
            
            if ($response->successful()) {
                $result = $response->json();
                
                $settlement->update([
                    'payment_detail' => array_merge($settlement->payment_detail ?? [], [
                        'snap_request' => $payload,
                        'snap_response' => $result,
                    ]),
                ]);
                
                return $result['token'] ?? '';
            }
            
            Log::error('Settlement Snap Token Error', [
                'settlement_id' => $settlement->id,
                'response' => $response->body(),
            ]);
            
            throw new \Exception('Gagal membuat Snap Token untuk settlement: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Settlement Snap Token Exception', [
                'settlement_id' => $settlement->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function handleSettlementCallback(array $payload): void
    {
        Log::info('Settlement Callback Received', $payload);
        
        $orderId = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;
        
        if (!$orderId) {
            throw new \Exception('Order ID not found in settlement callback.');
        }
        
        // Extract settlement ID from order_id (format: STL-{id}-{timestamp})
        preg_match('/^STL-(\d+)-\d+$/', $orderId, $matches);
        
        if (empty($matches[1])) {
            Log::error('Cannot parse settlement ID from order_id: ' . $orderId);
            return;
        }
        
        $settlementId = (int) $matches[1];
        $settlement = Settlement::find($settlementId);
        
        if (!$settlement) {
            Log::error('Settlement not found: ' . $settlementId);
            return;
        }
        
        if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
            if ($fraudStatus === 'accept') {
                $settlement->update([
                    'status' => SettlementStatus::PAID->value,
                    'transaction_id' => $payload['transaction_id'] ?? null,
                    'payment_method' => $payload['payment_type'] ?? null,
                    'paid_at' => now(),
                    'payment_detail' => array_merge($settlement->payment_detail ?? [], [
                        'callback' => $payload,
                    ]),
                ]);
                
                $this->notificationService->settlementPaid($settlement);
            }
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
            Log::warning("Settlement payment failed for settlement {$settlementId}: {$transactionStatus}");
        }
    }

    public function verifySettlement(Settlement $settlement, User $admin): void
    {
        DB::transaction(function () use ($settlement, $admin) {
            if ($settlement->status !== SettlementStatus::PAID->value) {
                throw new \Exception('Settlement harus dalam status paid untuk diverifikasi.');
            }
            
            $settlement->update([
                'status' => SettlementStatus::VERIFIED->value,
                'verified_by' => $admin->id,
                'verified_at' => now(),
            ]);
        });
    }

    public function getAgentSettlements(int $agentId, ?string $status = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Settlement::where('payment_agent_id', $agentId)->latest();
        
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->get();
    }

    public function getPendingSettlements(): \Illuminate\Database\Eloquent\Collection
    {
        return Settlement::with(['paymentAgent.user'])
            ->whereIn('status', [SettlementStatus::PENDING->value, SettlementStatus::PAID->value])
            ->latest()
            ->get();
    }

    public function markOverdueSettlements(): void
    {
        $overdueDate = Carbon::now()->subDays(7);
        
        Settlement::where('status', SettlementStatus::PENDING->value)
            ->where('created_at', '<', $overdueDate)
            ->update(['status' => SettlementStatus::OVERDUE->value]);
    }
}

// End of file