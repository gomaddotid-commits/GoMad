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
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($booking) {
            // ═══════════════════════════════════════════
            // 🔧 REFRESH BOOKING UNTUK DAPATKAN HARGA TERAKHIR
            // ═══════════════════════════════════════════
            $booking->refresh();

            // Bersihkan semua payment expired milik booking ini
            Payment::where('booking_id', $booking->id)
                ->where('payment_type', 'midtrans')
                ->where('status', PaymentStatus::PENDING->value)
                ->where('expired_at', '<', now())
                ->delete();

            $payment = Payment::where('booking_id', $booking->id)
                ->where('payment_type', 'midtrans')
                ->first();

            // Jika payment sudah PAID → tolak
            if ($payment && $payment->status === PaymentStatus::PAID->value) {
                // Kembalikan token yang sudah ada jika masih valid
                $existingToken = $payment->payment_detail['snap_token'] ?? null;
                if ($existingToken) {
                    return $existingToken;
                }
                throw new \RuntimeException('Pembayaran untuk booking ini sudah selesai.');
            }

            // Jika payment masih PENDING dan belum expired → return token yang SUDAH ADA
            if ($payment && $payment->status === PaymentStatus::PENDING->value
                && $payment->expired_at && $payment->expired_at->isFuture()) {

                $existingToken = $payment->payment_detail['snap_token'] ?? null;

                // ⚡ PENTING: Cek apakah harga di payment SAMA dengan harga booking saat ini
                if ((float) $payment->amount !== (float) $booking->total_price) {
                    Log::info('Payment amount mismatch - creating new payment', [
                        'booking_code' => $booking->booking_code,
                        'payment_amount' => $payment->amount,
                        'booking_total_price' => $booking->total_price,
                    ]);

                    // Hapus payment lama (harganya sudah tidak sesuai)
                    $payment->delete();
                    $payment = null;
                } elseif ($existingToken) {
                    Log::info('Reusing existing Snap Token', [
                        'booking_code' => $booking->booking_code,
                        'amount' => $booking->total_price,
                        'expires_at' => $payment->expired_at->toISOString(),
                    ]);
                    return $existingToken;
                }
            }

            // Hapus payment lama yang sudah tidak valid
            if ($payment) {
                $payment->delete();
            }

            // ═══════════════════════════════════════════
            // 🔧 BUAT PAYMENT BARU DENGAN HARGA FINAL
            // ═══════════════════════════════════════════
            $commissionData = app(PricingService::class)->calculateCommission(
                (float) $booking->total_price,
                'midtrans'
            );

            $paymentTimeout = (int) PlatformSetting::getValue('payment_timeout', 30);

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'amount' => (float) $booking->total_price,
                'commission' => $commissionData['platform_commission'],
                'agency_revenue' => $commissionData['agency_revenue'],
                'payment_type' => 'midtrans',
                'status' => PaymentStatus::PENDING->value,
                'expired_at' => now()->addMinutes($paymentTimeout),
            ]);

            // ═══════════════════════════════════════════
            // GENERATE SNAP TOKEN DENGAN HARGA FINAL
            // ═══════════════════════════════════════════
            $serverKey = config('gomad.midtrans.server_key');
            $isProduction = config('gomad.midtrans.is_production', false);

            $baseUrl = $isProduction
                ? 'https://app.midtrans.com/snap/v1/transactions'
                : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

            $orderId = $booking->booking_code . '-' . time();

            // ⚡ GROSS_AMOUNT MENGGUNAKAN HARGA FINAL (SUDAH TERMASUK PROMO)
            $grossAmount = (int) round($booking->total_price);

            // ═══════════════════════════════════════════
            // 🔧 BUILD ITEM DETAILS SESUAI MIDTRANS SPEC
            // ═══════════════════════════════════════════
            $itemDetails = [];

            // Ambil nilai masing-masing komponen
            $basePrice = (float) ($booking->base_price ?? 0);
            $serviceFee = (float) ($booking->service_fee ?? 0);
            $platformFee = (float) ($booking->platform_fee ?? 0);
            $discountAmount = (float) ($booking->discount_amount ?? 0);

            // Jika base_price tidak di-set, hitung mundur dari total
            if ($basePrice <= 0) {
                $basePrice = (float) $booking->total_price + $discountAmount - $serviceFee - $platformFee;
            }

            // Potong nama kota agar tidak terlalu panjang (max ~40 karakter untuk name)
            $originCity = \Illuminate\Support\Str::limit($booking->originStop->city_name ?? 'Origin', 18, '');
            $destCity = \Illuminate\Support\Str::limit($booking->destinationStop->city_name ?? 'Dest', 18, '');

            // Item 1: Harga tiket dasar (sudah termasuk diskon jika ada)
            $ticketPrice = (int) round($basePrice - $discountAmount);
            if ($ticketPrice > 0) {
                $itemDetails[] = [
                    'id' => 'TKT-' . $booking->id,
                    'price' => $ticketPrice,
                    'quantity' => 1,
                    'name' => 'Tiket ' . $originCity . '-' . $destCity,
                ];
            }

            // Item 2: Biaya Layanan (jika > 0)
            if ($serviceFee > 0) {
                $itemDetails[] = [
                    'id' => 'FEE-' . $booking->id,
                    'price' => (int) round($serviceFee),
                    'quantity' => 1,
                    'name' => 'Biaya Layanan',
                ];
            }

            // Item 3: Biaya Platform (jika > 0)
            if ($platformFee > 0) {
                $itemDetails[] = [
                    'id' => 'PLT-' . $booking->id,
                    'price' => (int) round($platformFee),
                    'quantity' => 1,
                    'name' => 'Biaya Platform',
                ];
            }

            // Item 4: Diskon Promo (jika ada, sebagai item terpisah BUKAN negative)
            // Midtrans lebih stabil dengan diskon sebagai pengurang di ticket price
            // Tapi kita tetap catat sebagai item terpisah untuk transparansi
            if ($discountAmount > 0) {
                $itemDetails[] = [
                    'id' => 'DSC-' . $booking->id,
                    'price' => -(int) round($discountAmount),
                    'quantity' => 1,
                    'name' => 'Diskon Promo',
                ];
            }

            // ⚡ VALIDASI: Pastikan total item_details = gross_amount
            $itemsTotal = 0;
            foreach ($itemDetails as $item) {
                $itemsTotal += $item['price'] * $item['quantity'];
            }

            // Jika ada selisih karena rounding, tambahkan sebagai adjustment
            $adjustment = $grossAmount - $itemsTotal;
            if ($adjustment !== 0) {
                // Jika selisih kecil, masukkan ke item terakhir
                if (abs($adjustment) <= 100 && !empty($itemDetails)) {
                    $lastIndex = count($itemDetails) - 1;
                    $itemDetails[$lastIndex]['price'] += $adjustment;
                } else {
                    // Jika selisih besar, buat item adjustment
                    $itemDetails[] = [
                        'id' => 'ADJ-' . $booking->id,
                        'price' => $adjustment,
                        'quantity' => 1,
                        'name' => 'Penyesuaian',
                    ];
                }
            }

            $payload = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $grossAmount,
                ],
                'customer_details' => [
                    'first_name' => $booking->customer->name,
                    'email' => $booking->customer->email,
                    'phone' => $booking->customer->phone,
                ],
                'item_details' => array_values($itemDetails),
                'callbacks' => [
                    'finish' => route('customer.booking.show', $booking),
                ],
            ];

            Log::info('Creating Midtrans Snap Token', [
                'booking_code' => $booking->booking_code,
                'total_price' => $booking->total_price,
                'base_price' => $basePrice,
                'service_fee' => $serviceFee,
                'platform_fee' => $platformFee,
                'discount_amount' => $discountAmount,
                'gross_amount' => $grossAmount,
                'items_total' => $itemsTotal + $adjustment,
                'item_count' => count($itemDetails),
                'order_id' => $orderId,
            ]);

            try {
                $response = \Illuminate\Support\Facades\Http::withBasicAuth($serverKey, '')
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->timeout(10)
                    ->post($baseUrl, $payload);

                if ($response->successful()) {
                    $result = $response->json();

                    $payment->update([
                        'transaction_id' => $orderId,
                        'payment_detail' => array_merge($payment->payment_detail ?? [], [
                            'snap_request' => $payload,
                            'snap_response' => $result,
                            'snap_token' => $result['token'] ?? null,
                            'snap_token_created_at' => now()->toISOString(),
                            'gross_amount' => $grossAmount,
                            'discount_applied' => $discountAmount,
                        ]),
                    ]);

                    return $result['token'] ?? '';
                }

                Log::error('Midtrans Snap Token Error', [
                    'response' => $response->body(),
                    'booking_code' => $booking->booking_code,
                    'payload' => $payload,
                ]);

                throw new \Exception('Gagal membuat Snap Token: ' . $response->body());
            } catch (\Exception $e) {
                Log::error('Midtrans Snap Token Exception', [
                    'error' => $e->getMessage(),
                    'booking_code' => $booking->booking_code,
                ]);
                throw $e;
            }
        });
    }

    public function handleMidtransCallback(array $payload): void
    {
        Log::info('Midtrans Callback Received', $payload);

        // 1. Verifikasi signature
        if (!$this->verifySignature($payload)) {
            Log::error('Midtrans Signature Verification Failed', $payload);
            throw new \Exception('Signature verification failed.');
        }

        $orderId = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        if (!$orderId) {
            throw new \Exception('Order ID not found in callback.');
        }

        // Cek apakah ini top up
        if (str_starts_with($orderId, 'TOPUP-')) {
            app(WalletService::class)->processTopUpCallback($payload);
            return;
        }

        $booking = Booking::where('booking_code', $orderId)->first();
        if (!$booking) {
            throw new \Exception("Booking not found: {$orderId}");
        }

        $payment = Payment::where('booking_id', $booking->id)->first();
        if (!$payment) {
            throw new \Exception("Payment not found for booking: {$orderId}");
        }

        // ═══════════════════════════════════════════
        // 🔒 IDEMPOTENCY GUARD: Cegah double processing
        // ═══════════════════════════════════════════
        $lastProcessedStatus = $payment->payment_detail['last_callback_status'] ?? null;
        $lastProcessedFraud = $payment->payment_detail['last_callback_fraud'] ?? null;

        if ($lastProcessedStatus === $transactionStatus && $lastProcessedFraud === $fraudStatus) {
            Log::info('Duplicate Midtrans callback ignored (idempotent)', [
                'order_id' => $orderId,
                'status' => $transactionStatus,
                'fraud' => $fraudStatus,
            ]);
            return;
        }

        $finalPaymentStatuses = [
            PaymentStatus::PAID->value,
            PaymentStatus::FAILED->value,
            PaymentStatus::REFUNDED->value,
            PaymentStatus::EXPIRED->value,
        ];

        if (in_array($payment->status, $finalPaymentStatuses)) {
            Log::warning('Midtrans callback ignored - payment already final', [
                'order_id' => $orderId,
                'payment_status' => $payment->status,
                'callback_status' => $transactionStatus,
            ]);
            return;
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
                    'last_callback_status' => $transactionStatus,
                    'last_callback_fraud' => $fraudStatus,
                    'last_callback_at' => now()->toISOString(),
                ]),
            ]);

            if ($newStatus === PaymentStatus::PAID) {
                $booking->update(['status' => BookingStatus::PAID->value]);
                $this->walletService->addPendingBalance($booking);

                // ═══════════════════════════════════════════
                // NOTIFIKASI via dispatchAfterResponse (ASYNC)
                // ═══════════════════════════════════════════
                dispatch(function () use ($booking) {
                    $this->notificationService->paymentConfirmed($booking);

                    try {
                        $promoService = app(\App\Services\PromoService::class);
                        $promoService->processReferralReward($booking);
                    } catch (\Exception $e) {
                        Log::error('Referral reward processing failed: ' . $e->getMessage());
                    }
                })->afterResponse();

            } elseif ($newStatus === PaymentStatus::FAILED) {
                if ($booking->status !== BookingStatus::PAID->value) {
                    $booking->update(['status' => BookingStatus::CANCELLED->value, 'cancelled_at' => now()]);

                    dispatch(function () use ($booking) {
                        $this->notificationService->bookingCancelled($booking, 'Pembayaran gagal');
                    })->afterResponse();
                }
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

    /**
     * Refund pembayaran rental
     */
    public function refundPaymentForRental(Rental $rental, float $refundAmount): array
    {
        $payment = $rental->payment;
        
        if (!$payment) {
            return ['success' => false, 'message' => 'Tidak ada data pembayaran.'];
        }
        
        if ($payment->payment_type !== 'midtrans') {
            return ['success' => false, 'message' => 'Hanya pembayaran Midtrans yang bisa direfund via API.'];
        }
        
        if ($payment->status !== \App\Enums\PaymentStatus::PAID->value) {
            return ['success' => false, 'message' => 'Pembayaran tidak dalam status paid.'];
        }
        
        $isProduction = config('gomad.midtrans.is_production', false);
        $serverKey = config('gomad.midtrans.server_key');
        
        if (empty($serverKey)) {
            // Simulasi
            $payment->update([
                'status' => \App\Enums\PaymentStatus::REFUNDED->value,
                'payment_detail' => array_merge($payment->payment_detail ?? [], [
                    'refund' => [
                        'refund_amount' => $refundAmount,
                        'refunded_at' => now()->toIso8601String(),
                        'mode' => 'simulation',
                        'status' => 'refunded_simulated',
                    ],
                ]),
            ]);
            
            return ['success' => true, 'message' => 'Refund berhasil (simulasi).'];
        }
        
        $transactionId = $payment->transaction_id ?? 'RNTL-' . $rental->id;
        
        $baseUrl = $isProduction 
            ? 'https://api.midtrans.com/v2' 
            : 'https://api.sandbox.midtrans.com/v2';
        
        try {
            $response = \Illuminate\Support\Facades\Http::withBasicAuth($serverKey, '')
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($baseUrl . '/' . $transactionId . '/refund', [
                    'amount' => (int) $refundAmount,
                    'reason' => 'Pembatalan rental ' . $rental->rental_code,
                ]);
            
            $result = $response->json();
            $httpCode = $response->status();
            
            if ($httpCode === 200 && in_array($result['status_code'] ?? '', ['200', '201'])) {
                $payment->update([
                    'status' => \App\Enums\PaymentStatus::REFUNDED->value,
                    'payment_detail' => array_merge($payment->payment_detail ?? [], [
                        'refund' => array_merge($result, [
                            'refund_amount' => $refundAmount,
                            'refunded_at' => now()->toIso8601String(),
                            'status' => 'refunded',
                        ]),
                    ]),
                ]);
                
                return ['success' => true, 'message' => 'Refund berhasil diproses.'];
            }
            
            // Gagal refund via API → perlu manual
            $payment->update([
                'status' => \App\Enums\PaymentStatus::REFUNDED->value,
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
            
            return ['success' => true, 'message' => 'Refund akan diproses manual oleh admin.'];
            
        } catch (\Exception $e) {
            \Log::error('Rental refund exception', [
                'rental_code' => $rental->rental_code,
                'error' => $e->getMessage(),
            ]);
            
            $payment->update([
                'status' => \App\Enums\PaymentStatus::REFUNDED->value,
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
            
            return ['success' => true, 'message' => 'Refund akan diproses manual oleh admin.'];
        }
    }
    
}

// End of file