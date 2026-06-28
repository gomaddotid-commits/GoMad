<?php
// File: app/Http/Controllers/Api/PaymentAgent/SettlementController.php
// Deskripsi: API Controller untuk settlement payment agent

namespace App\Http\Controllers\Api\PaymentAgent;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SettlementResource;
use App\Services\SettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function __construct(
        private readonly SettlementService $settlementService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $agent = $request->user()->paymentAgent;

        $settlements = $this->settlementService->getAgentSettlements(
            $agent->id,
            $request->status
        );

        return response()->json([
            'success' => true,
            'message' => 'Daftar settlement berhasil diambil.',
            'data' => SettlementResource::collection($settlements),
            'meta' => [
                'total' => $settlements->count(),
            ],
        ]);
    }

    public function show(Request $request, $settlement): JsonResponse
    {
        $agent = $request->user()->paymentAgent;

        $settlement = \App\Models\Settlement::where('payment_agent_id', $agent->id)
            ->findOrFail($settlement);

        $settlement->load(['cashPayments.booking']);

        return response()->json([
            'success' => true,
            'message' => 'Detail settlement berhasil diambil.',
            'data' => new SettlementResource($settlement),
            'meta' => null,
        ]);
    }

    public function pay(Request $request, $settlement): JsonResponse
    {
        $agent = $request->user()->paymentAgent;

        $settlement = \App\Models\Settlement::where('payment_agent_id', $agent->id)
            ->where('status', 'pending')
            ->findOrFail($settlement);

        try {
            $snapToken = $this->settlementService->paySettlement($settlement);

            return response()->json([
                'success' => true,
                'message' => 'Snap token untuk settlement berhasil dibuat.',
                'data' => [
                    'snap_token' => $snapToken,
                    'settlement_id' => $settlement->id,
                    'amount' => (float) $settlement->amount_to_settle,
                    'amount_formatted' => 'Rp ' . number_format($settlement->amount_to_settle, 0, ',', '.'),
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

    public function settlementCallback(Request $request): JsonResponse
    {
        try {
            $this->settlementService->handleSettlementCallback($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Callback settlement diproses.',
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
}

// End of file