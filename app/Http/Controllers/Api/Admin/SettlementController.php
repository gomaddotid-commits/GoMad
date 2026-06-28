<?php
// File: app/Http/Controllers/Api/Admin/SettlementController.php
// Deskripsi: API Controller untuk manajemen settlement oleh admin

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SettlementResource;
use App\Models\Settlement;
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
        $query = Settlement::with(['paymentAgent.user']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->payment_agent_id) {
            $query->where('payment_agent_id', $request->payment_agent_id);
        }

        $settlements = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar settlement berhasil diambil.',
            'data' => SettlementResource::collection($settlements),
            'meta' => [
                'current_page' => $settlements->currentPage(),
                'last_page' => $settlements->lastPage(),
                'total' => $settlements->total(),
            ],
        ]);
    }

    public function pending(): JsonResponse
    {
        $settlements = $this->settlementService->getPendingSettlements();

        return response()->json([
            'success' => true,
            'message' => 'Daftar settlement pending berhasil diambil.',
            'data' => SettlementResource::collection($settlements),
            'meta' => ['total' => $settlements->count()],
        ]);
    }

    public function verify(Settlement $settlement): JsonResponse
    {
        try {
            $this->settlementService->verifySettlement($settlement, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Settlement berhasil diverifikasi.',
                'data' => new SettlementResource($settlement->fresh()),
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
}

// End of file