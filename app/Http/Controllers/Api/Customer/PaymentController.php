<?php
// File: app/Http/Controllers/Api/Customer/PaymentController.php
// Deskripsi: API Controller untuk pembayaran customer

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\CashPaymentService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly CashPaymentService $cashPaymentService,
    ) {}

    public function payWithMidtrans(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
        ]);

        $booking = Booking::where('id', $request->booking_id)
            ->where('customer_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan atau sudah dibayar.',
                'data' => null,
                'meta' => null,
            ], 422);
        }

        try {
            $snapToken = $this->paymentService->getSnapToken($booking);

            return response()->json([
                'success' => true,
                'message' => 'Snap token berhasil dibuat.',
                'data' => [
                    'snap_token' => $snapToken,
                    'booking_code' => $booking->booking_code,
                    'amount' => (float) $booking->total_price,
                    'amount_formatted' => 'Rp ' . number_format($booking->total_price, 0, ',', '.'),
                ],
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pembayaran: ' . $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 500);
        }
    }

    public function payWithCash(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => ['required', 'integer', 'exists:bookings,id'],
        ]);

        $booking = Booking::where('id', $request->booking_id)
            ->where('customer_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan atau sudah dibayar.',
                'data' => null,
                'meta' => null,
            ], 422);
        }

        $cashPayment = $this->cashPaymentService->createCashPayment($booking);

        return response()->json([
            'success' => true,
            'message' => 'Kode pembayaran cash berhasil dibuat.',
            'data' => [
                'payment_code' => $cashPayment->payment_code,
                'booking_code' => $booking->booking_code,
                'amount' => (float) $booking->total_price,
                'amount_formatted' => 'Rp ' . number_format($booking->total_price, 0, ',', '.'),
                'expired_at' => $cashPayment->expired_at?->format('Y-m-d H:i:s'),
            ],
            'meta' => null,
        ]);
    }

    public function checkCashPayment(string $code): JsonResponse
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
            'message' => 'Status pembayaran berhasil diambil.',
            'data' => [
                'payment_code' => $cashPayment->payment_code,
                'status' => $cashPayment->status,
                'amount' => (float) $cashPayment->amount,
                'amount_formatted' => 'Rp ' . number_format($cashPayment->amount, 0, ',', '.'),
                'is_expired' => $cashPayment->expired_at && now()->greaterThan($cashPayment->expired_at),
                'expired_at' => $cashPayment->expired_at?->format('Y-m-d H:i:s'),
            ],
            'meta' => null,
        ]);
    }

    public function midtransCallback(Request $request): JsonResponse
    {
        try {
            $this->paymentService->handleMidtransCallback($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Callback diproses.',
                'data' => null,
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 400);
        }
    }

    public function nearbyWarungs(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $agentService = app(\App\Services\PaymentAgentService::class);
        $agents = $agentService->getNearbyAgents(
            (float) $request->latitude,
            (float) $request->longitude
        );

        return response()->json([
            'success' => true,
            'message' => 'Daftar warung terdekat berhasil diambil.',
            'data' => \App\Http\Resources\Api\PaymentAgentResource::collection($agents),
            'meta' => [
                'total' => $agents->count(),
            ],
        ]);
    }
}

// End of file