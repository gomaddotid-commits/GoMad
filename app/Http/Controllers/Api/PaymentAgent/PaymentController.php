<?php
// File: app/Http/Controllers/Api/PaymentAgent/PaymentController.php
// Deskripsi: API Controller untuk konfirmasi pembayaran oleh payment agent

namespace App\Http\Controllers\Api\PaymentAgent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConfirmCashPaymentRequest;
use App\Services\CashPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly CashPaymentService $cashPaymentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $agent = $request->user()->paymentAgent;

        $payments = $this->cashPaymentService->getAgentCashPayments(
            $agent->id,
            $request->status
        );

        return response()->json([
            'success' => true,
            'message' => 'Daftar pembayaran berhasil diambil.',
            'data' => $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_code' => $payment->payment_code,
                    'amount' => (float) $payment->amount,
                    'amount_formatted' => 'Rp ' . number_format($payment->amount, 0, ',', '.'),
                    'status' => $payment->status,
                    'booking_code' => $payment->booking->booking_code ?? null,
                    'customer_name' => $payment->booking->customer->name ?? null,
                    'confirmed_at' => $payment->confirmed_at?->format('Y-m-d H:i:s'),
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'total' => $payments->count(),
            ],
        ]);
    }

    public function confirm(ConfirmCashPaymentRequest $request): JsonResponse
    {
        $agent = $request->user()->paymentAgent;

        try {
            $cashPayment = $this->cashPaymentService->confirmCashPayment(
                $request->payment_code,
                $agent->id,
                $request->pin
            );

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil dikonfirmasi.',
                'data' => [
                    'payment_code' => $cashPayment->payment_code,
                    'amount' => (float) $cashPayment->amount,
                    'amount_formatted' => 'Rp ' . number_format($cashPayment->amount, 0, ',', '.'),
                    'booking_code' => $cashPayment->booking->booking_code,
                    'confirmed_at' => $cashPayment->confirmed_at->format('Y-m-d H:i:s'),
                ],
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 422);
        }
    }

    public function show(Request $request, string $code): JsonResponse
    {
        $cashPayment = $this->cashPaymentService->getCashPaymentByCode($code);

        if (!$cashPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Kode pembayaran tidak ditemukan.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pembayaran berhasil diambil.',
            'data' => [
                'payment_code' => $cashPayment->payment_code,
                'amount' => (float) $cashPayment->amount,
                'amount_formatted' => 'Rp ' . number_format($cashPayment->amount, 0, ',', '.'),
                'status' => $cashPayment->status,
                'booking_code' => $cashPayment->booking->booking_code ?? null,
                'customer_name' => $cashPayment->booking->customer->name ?? null,
                'customer_phone' => $cashPayment->booking->customer->phone ?? null,
                'route' => $cashPayment->booking->originStop->city_name . ' → ' . $cashPayment->booking->destinationStop->city_name,
                'is_expired' => $cashPayment->expired_at && now()->greaterThan($cashPayment->expired_at),
                'expired_at' => $cashPayment->expired_at?->format('Y-m-d H:i:s'),
            ],
            'meta' => null,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $agent = $request->user()->paymentAgent;

        $payments = $this->cashPaymentService->getAgentCashPayments($agent->id);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pembayaran berhasil diambil.',
            'data' => $payments->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_code' => $payment->payment_code,
                    'amount' => (float) $payment->amount,
                    'agent_commission' => (float) $payment->agent_commission,
                    'status' => $payment->status,
                    'booking_code' => $payment->booking->booking_code ?? null,
                    'confirmed_at' => $payment->confirmed_at?->format('Y-m-d H:i:s'),
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'total' => $payments->count(),
                'total_commission' => (float) $payments->sum('agent_commission'),
            ],
        ]);
    }
}

// End of file