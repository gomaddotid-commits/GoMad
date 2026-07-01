<?php
// File: app/Services/CashPaymentService.php
// Deskripsi: Service untuk pembayaran cash via Warung GoMad

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Helpers\PaymentCodeGenerator;
use App\Models\Booking;
use App\Models\CashPayment;
use App\Models\Payment;
use App\Models\PaymentAgent;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CashPaymentService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly WalletService $walletService,
    ) {}

    public function generatePaymentCode(): string
    {
        return PaymentCodeGenerator::generate();
    }

    public function createCashPayment(Booking $booking): CashPayment
    {
        return DB::transaction(function () use ($booking) {
            $existingCashPayment = CashPayment::where('booking_id', $booking->id)->first();
            if ($existingCashPayment) {
                return $existingCashPayment;
            }

            $commissionData = app(PricingService::class)->calculateCommission($booking->total_price, 'cash');
            
            $expiryHours = (int) PlatformSetting::getValue('payment_code_expiry_hours', 24);
            
            $cashPayment = CashPayment::create([
                'booking_id' => $booking->id,
                'payment_code' => $this->generatePaymentCode(),
                'amount' => $booking->total_price,
                'agent_commission' => $commissionData['agent_commission'],
                'platform_commission' => $commissionData['platform_commission'],
                'status' => 'pending',
                'expired_at' => now()->addHours($expiryHours),
            ]);

            Payment::create([
                'booking_id' => $booking->id,
                'cash_payment_id' => $cashPayment->id,
                'amount' => $booking->total_price,
                'commission' => $commissionData['platform_commission'],
                'agency_revenue' => $commissionData['agency_revenue'],
                'payment_type' => 'cash',
                'status' => PaymentStatus::PENDING->value,
                'expired_at' => now()->addHours($expiryHours),
            ]);

            $this->notificationService->sendWhatsApp(
                $booking->customer->phone,
                "Kode bayar GoMad Anda: *{$cashPayment->payment_code}*\n" .
                "Total: Rp " . number_format($booking->total_price, 0, ',', '.') . "\n" .
                "Bayar di Warung GoMad terdekat sebelum " . $cashPayment->expired_at->format('d M Y H:i') . "\n" .
                "Simpan kode ini untuk pembayaran."
            );

            return $cashPayment;
        });
    }

    public function confirmCashPayment(string $code, int $agentId, string $pin): CashPayment
    {
        return DB::transaction(function () use ($code, $agentId, $pin) {
            $agent = PaymentAgent::findOrFail($agentId);
            
            if (!$agent->is_active || !$agent->is_verified) {
                throw new \Exception('Warung tidak aktif atau belum diverifikasi.');
            }

            if (!$this->verifyPin($agent, $pin)) {
                throw new \Exception('PIN tidak valid.');
            }

            $cashPayment = CashPayment::where('payment_code', $code)
                ->where('status', 'pending')
                ->first();

            if (!$cashPayment) {
                throw new \Exception('Kode pembayaran tidak ditemukan atau sudah digunakan.');
            }

            if ($cashPayment->expired_at && now()->greaterThan($cashPayment->expired_at)) {
                $cashPayment->update(['status' => 'expired']);
                throw new \Exception('Kode pembayaran sudah kadaluarsa.');
            }

            $cashPayment->update([
                'payment_agent_id' => $agentId,
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            $payment = Payment::where('cash_payment_id', $cashPayment->id)->first();
            if ($payment) {
                $payment->update([
                    'status' => PaymentStatus::PAID->value,
                    'paid_at' => now(),
                ]);
            }

            $booking = $cashPayment->booking;
            $booking->update(['status' => BookingStatus::PAID->value]);

            $agent->increment('total_transactions');
            $agent->increment('total_commission', $cashPayment->agent_commission);
            $agent->increment('balance_to_settle', $cashPayment->amount - $cashPayment->agent_commission);

            $this->walletService->addPendingBalance($booking);
            
            $this->notificationService->cashPaymentConfirmed($booking);
            
            $this->notificationService->sendWhatsApp(
                $booking->customer->phone,
                "Pembayaran untuk booking *{$booking->booking_code}* telah dikonfirmasi.\n" .
                "Total: Rp " . number_format($booking->total_price, 0, ',', '.') . "\n" .
                "Warung: {$agent->agent_name}\n" .
                "Terima kasih telah menggunakan GoMad!"
            );

            try {
                $promoService = app(\App\Services\PromoService::class);
                $promoService->processReferralReward($booking);
                Log::info('Referral reward processed for booking: ' . $booking->booking_code);
            } catch (\Exception $e) {
                Log::error('Referral reward processing failed: ' . $e->getMessage(), [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
                // Jangan throw error - jangan ganggu flow pembayaran utama
            }

            return $cashPayment;
        });
    }

    public function verifyPin(PaymentAgent $agent, string $pin): bool
    {
        return Hash::check($pin, $agent->pin);
    }

    public function expireCashPayment(CashPayment $cashPayment): void
    {
        if ($cashPayment->status === 'pending') {
            $cashPayment->update(['status' => 'expired']);
            
            $payment = Payment::where('cash_payment_id', $cashPayment->id)->first();
            if ($payment) {
                $payment->update(['status' => PaymentStatus::EXPIRED->value]);
            }

            $booking = $cashPayment->booking;
            if ($booking && $booking->status === BookingStatus::PENDING->value) {
                $booking->update([
                    'status' => BookingStatus::CANCELLED->value,
                    'cancelled_at' => now(),
                ]);
            }
        }
    }

    public function getCashPaymentByCode(string $code): ?CashPayment
    {
        return CashPayment::with(['booking.schedule.route', 'booking.originStop', 'booking.destinationStop'])
            ->where('payment_code', $code)
            ->first();
    }

    public function getAgentCashPayments(int $agentId, ?string $status = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = CashPayment::with(['booking.schedule.route', 'booking.originStop', 'booking.destinationStop'])
            ->where('payment_agent_id', $agentId)
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }
}

// End of file