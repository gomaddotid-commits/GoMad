<?php
// File: app/Services/PaymentService.php
// Deskripsi: Service untuk pembayaran Midtrans dan e-ticket

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly NotificationService $notificationService,
    ) {}

    public function createPayment(Booking $booking): Payment
    {
        $existingPayment = Payment::where('booking_id', $booking->id)->first();
        if ($existingPayment) {
            return $existingPayment;
        }

        $commissionData = app(PricingService::class)->calculateCommission($booking->total_price, 'midtrans');
        
        $paymentTimeout = (int) PlatformSetting::getValue('payment_timeout', 30);
        
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => $booking->total_price,
            'commission' => $commissionData['platform_commission'],
            'agency_revenue' => $commissionData['agency_revenue'],
            'payment_type' => 'midtrans',
            'status' => PaymentStatus::PENDING->value,
            'expired_at' => now()->addMinutes($paymentTimeout),
        ]);

        return $payment;
    }

    public function getSnapToken(Booking $booking): string
    {
        $payment = $this->createPayment($booking);
        
        $serverKey = config('gomad.midtrans.server_key');
        $isProduction = config('gomad.midtrans.is_production', false);
        
        $baseUrl = $isProduction 
            ? 'https://app.midtrans.com/snap/v1/transactions' 
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $payload = [
            'transaction_details' => [
                'order_id' => $booking->booking_code,
                'gross_amount' => (int) $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $booking->customer->name,
                'email' => $booking->customer->email,
                'phone' => $booking->customer->phone,
            ],
            'callbacks' => [
                'finish' => config('app.url') . '/booking/' . $booking->booking_code . '/detail',
            ],
        ];

        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($baseUrl, $payload);

            if ($response->successful()) {
                $result = $response->json();
                
                $payment->update([
                    'payment_detail' => array_merge($payment->payment_detail ?? [], [
                        'snap_response' => $result,
                    ]),
                ]);
                
                return $result['token'] ?? '';
            }

            Log::error('Midtrans Snap Token Error', [
                'response' => $response->body(),
                'booking_code' => $booking->booking_code,
            ]);
            
            throw new \Exception('Gagal membuat Snap Token: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Midtrans Snap Token Exception', [
                'error' => $e->getMessage(),
                'booking_code' => $booking->booking_code,
            ]);
            throw $e;
        }
    }

    public function handleMidtransCallback(array $payload): void
    {
        Log::info('Midtrans Callback Received', $payload);

        if (!$this->verifySignature($payload)) {
            Log::error('Midtrans Signature Verification Failed', $payload);
            throw new \Exception('Signature verification failed.');
        }

        $orderId = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        // Cek apakah ini top up
        if (str_starts_with($orderId, 'TOPUP-')) {
            app(WalletService::class)->processTopUpCallback($payload);
            return;
        }
        
        if (!$orderId) {
            throw new \Exception('Order ID not found in callback.');
        }

        $booking = Booking::where('booking_code', $orderId)->first();
        if (!$booking) {
            throw new \Exception("Booking not found: {$orderId}");
        }

        $payment = Payment::where('booking_id', $booking->id)->first();
        if (!$payment) {
            throw new \Exception("Payment not found for booking: {$orderId}");
        }

        $newStatus = null;

        if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
            if ($fraudStatus === 'accept') {
                $newStatus = PaymentStatus::PAID;
            } elseif ($fraudStatus === 'challenge') {
                $newStatus = PaymentStatus::PENDING;
            } else {
                $newStatus = PaymentStatus::FAILED;
            }
        } elseif ($transactionStatus === 'pending') {
            $newStatus = PaymentStatus::PENDING;
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
            $newStatus = PaymentStatus::FAILED;
        } elseif ($transactionStatus === 'refund' || $transactionStatus === 'partial_refund') {
            $newStatus = PaymentStatus::REFUNDED;
        }

        if ($newStatus) {
            $payment->update([
                'status' => $newStatus->value,
                'transaction_id' => $payload['transaction_id'] ?? null,
                'payment_method' => $payload['payment_type'] ?? null,
                'payment_channel' => $payload['payment_type'] ?? null,
                'paid_at' => $newStatus === PaymentStatus::PAID ? now() : null,
                'payment_detail' => array_merge($payment->payment_detail ?? [], [
                    'callback' => $payload,
                ]),
            ]);

            if ($newStatus === PaymentStatus::PAID) {
                $booking->update(['status' => BookingStatus::PAID->value]);
                $this->walletService->addPendingBalance($booking);
                $this->notificationService->paymentConfirmed($booking);

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
            } elseif ($newStatus === PaymentStatus::FAILED) {
                $booking->update(['status' => BookingStatus::CANCELLED->value, 'cancelled_at' => now()]);
                $this->notificationService->bookingCancelled($booking, 'Pembayaran gagal');
            }
        }
    }

    public function verifySignature(array $payload): bool
    {
        $serverKey = config('gomad.midtrans.server_key');
        
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        
        $rawSignature = $orderId . $statusCode . $grossAmount . $serverKey;
        $calculatedSignature = hash('sha512', $rawSignature);
        
        $providedSignature = $payload['signature_key'] ?? '';
        
        return hash_equals($calculatedSignature, $providedSignature);
    }

    public function generateETicket(Booking $booking): string
    {
        $booking->load([
            'schedule.route',
            'schedule.agency',
            'schedule.vehicle',
            'originStop',
            'destinationStop',
            'passengers',
            'payment',
        ]);

        $eTicketData = [
            'booking_code' => $booking->booking_code,
            'agency_name' => $booking->schedule->agency->agency_name,
            'agency_logo' => $booking->schedule->agency->logo,
            'route' => $booking->schedule->route->route_name,
            'origin_city' => $booking->originStop->city_name,
            'destination_city' => $booking->destinationStop->city_name,
            'departure_date' => $booking->schedule->departure_date->format('d M Y'),
            'departure_time' => $booking->schedule->departure_time,
            'vehicle' => $booking->schedule->vehicle->brand . ' ' . $booking->schedule->vehicle->model,
            'plate_number' => $booking->schedule->vehicle->plate_number,
            'passengers' => $booking->passengers->map(function ($p) {
                return [
                    'name' => $p->passenger_name,
                    'seat' => $p->seat_number,
                ];
            }),
            'total_price' => $booking->total_price,
            'pickup_address' => $booking->pickup_address,
            'destination_address' => $booking->destination_address,
            'generated_at' => now()->format('d M Y H:i'),
        ];

        $eTicketUrl = url("/e-ticket/{$booking->booking_code}");
        
        $booking->update(['e_ticket_url' => $eTicketUrl]);

        return $eTicketUrl;
    }

    public function expirePayment(Payment $payment): void
    {
        if ($payment->status === PaymentStatus::PENDING->value) {
            $payment->update(['status' => PaymentStatus::EXPIRED->value]);
            
            $booking = $payment->booking;
            if ($booking && in_array($booking->status, [BookingStatus::PENDING->value, BookingStatus::CONFIRMED->value])) {
                $booking->update([
                    'status' => BookingStatus::CANCELLED->value,
                    'cancelled_at' => now(),
                ]);
            }
        }
    }

    /**
     * Refund pembayaran Midtrans
     */
    public function refundPayment(Booking $booking, ?float $amount = null): array
    {
        $payment = $booking->payment;
        
        if (!$payment) {
            return ['success' => false, 'message' => 'Tidak ada data pembayaran.'];
        }
        
        if ($payment->payment_type !== 'midtrans') {
            return ['success' => false, 'message' => 'Hanya pembayaran Midtrans yang bisa direfund via API.'];
        }
        
        if (!in_array($payment->status, [PaymentStatus::PAID->value, PaymentStatus::COD_CONFIRMED->value, PaymentStatus::REFUND_PENDING->value])) {
            return ['success' => false, 'message' => 'Pembayaran tidak dalam status yang bisa direfund.'];
        }
        
        $refundAmount = $amount ?? (float) $payment->amount;
        $isProduction = config('gomad.midtrans.is_production', false);
        $serverKey = config('gomad.midtrans.server_key');
        
        if (empty($serverKey)) {
            return $this->simulateRefund($payment, $refundAmount, $booking);
        }
        
        $transactionId = $payment->transaction_id;
        
        if (!$transactionId) {
            $transactionId = $booking->booking_code;
        }
        
        $baseUrl = $isProduction 
            ? 'https://api.midtrans.com/v2' 
            : 'https://api.sandbox.midtrans.com/v2';
        
        try {
            \Log::info('Processing refund', [
                'booking_code' => $booking->booking_code,
                'transaction_id' => $transactionId,
                'refund_amount' => $refundAmount,
            ]);
            
            $response = \Illuminate\Support\Facades\Http::withBasicAuth($serverKey, '')
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($baseUrl . '/' . $transactionId . '/refund', [
                    'amount' => (int) $refundAmount,
                    'reason' => 'Pembatalan booking ' . $booking->booking_code,
                ]);
            
            $result = $response->json();
            $httpCode = $response->status();
            
            if ($httpCode === 200 && in_array($result['status_code'] ?? '', ['200', '201'])) {
                $payment->update([
                    'status' => PaymentStatus::REFUNDED->value,
                    'payment_detail' => array_merge($payment->payment_detail ?? [], [
                        'refund' => array_merge($result, [
                            'refund_amount' => $refundAmount,
                            'refunded_at' => now()->toIso8601String(),
                            'status' => 'refunded',
                        ]),
                    ]),
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Refund berhasil diproses.',
                    'data' => ['refund_amount' => $refundAmount, 'status' => 'refunded'],
                ];
            }
            
            // Refund API gagal → perlu manual
            $payment->update([
                'status' => PaymentStatus::REFUNDED->value,
                'payment_detail' => array_merge($payment->payment_detail ?? [], [
                    'refund' => [
                        'refund_amount' => $refundAmount,
                        'refunded_at' => now()->toIso8601String(),
                        'needs_manual_refund' => true,
                        'api_response' => $result,
                        'status' => 'needs_manual_refund',
                    ],
                ]),
            ]);
            
            return [
                'success' => true,
                'message' => 'Refund akan diproses manual oleh admin.',
                'data' => ['refund_amount' => $refundAmount, 'status' => 'pending_manual'],
            ];
        } catch (\Exception $e) {
            \Log::error('Refund exception', ['booking_code' => $booking->booking_code, 'error' => $e->getMessage()]);
            
            $payment->update([
                'status' => PaymentStatus::REFUNDED->value,
                'payment_detail' => array_merge($payment->payment_detail ?? [], [
                    'refund' => [
                        'refund_amount' => $refundAmount,
                        'refunded_at' => now()->toIso8601String(),
                        'needs_manual_refund' => true,
                        'error' => $e->getMessage(),
                        'status' => 'error_manual_refund',
                    ],
                ]),
            ]);
            
            return [
                'success' => true,
                'message' => 'Refund akan diproses manual oleh admin.',
                'data' => ['refund_amount' => $refundAmount, 'status' => 'pending_manual'],
            ];
        }
    }

    private function simulateRefund(Payment $payment, float $refundAmount, Booking $booking): array
    {
        $payment->update([
            'status' => PaymentStatus::REFUNDED->value,
            'payment_detail' => array_merge($payment->payment_detail ?? [], [
                'refund' => [
                    'refund_amount' => $refundAmount,
                    'refunded_at' => now()->toIso8601String(),
                    'mode' => 'simulation',
                    'status' => 'refunded_simulated',
                ],
            ]),
        ]);
        
        return [
            'success' => true,
            'message' => 'Refund berhasil (simulasi).',
            'data' => ['refund_amount' => $refundAmount, 'status' => 'refunded'],
        ];
    }

    public function approveRefund(Booking $booking, User $admin): array
    {
        $payment = $booking->payment;
        
        if ($payment->status !== PaymentStatus::REFUND_PENDING->value) {
            return ['success' => false, 'message' => 'Refund tidak dalam status menunggu approval.'];
        }
        
        $refundData = $payment->payment_detail['refund'] ?? [];
        $refundAmount = $refundData['amount'] ?? (float) $payment->amount;
        
        $result = $this->refundPayment($booking, $refundAmount);
        
        if ($result['success']) {
            $payment->update([
                'status' => PaymentStatus::REFUNDED->value,
                'payment_detail' => array_merge($payment->payment_detail ?? [], [
                    'refund' => array_merge($refundData, [
                        'approved_at' => now()->toIso8601String(),
                        'approved_by' => $admin->id,
                        'approved_by_name' => $admin->name,
                        'status' => 'approved_and_refunded',
                    ]),
                ]),
            ]);
        }
        
        return $result;
    }

    public function rejectRefund(Booking $booking, User $admin, string $reason): array
    {
        $payment = $booking->payment;
        
        if ($payment->status !== PaymentStatus::REFUND_PENDING->value) {
            return ['success' => false, 'message' => 'Refund tidak dalam status menunggu approval.'];
        }
        
        $payment->update([
            'status' => PaymentStatus::REFUND_REJECTED->value,
            'payment_detail' => array_merge($payment->payment_detail ?? [], [
                'refund' => array_merge($payment->payment_detail['refund'] ?? [], [
                    'rejected_at' => now()->toIso8601String(),
                    'rejected_by' => $admin->id,
                    'rejected_by_name' => $admin->name,
                    'rejection_reason' => $reason,
                    'status' => 'rejected',
                ]),
            ]),
        ]);
        
        return ['success' => true, 'message' => 'Refund ditolak.'];
    }

    
}

// End of file