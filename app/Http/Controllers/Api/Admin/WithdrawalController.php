<?php
// File: app/Http/Controllers/Api/Admin/WithdrawalController.php
// Deskripsi: API Controller untuk manajemen withdrawal oleh admin

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\WithdrawalResource;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $withdrawalService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Withdrawal::with(['agency.user', 'approver']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->agency_id) {
            $query->where('agency_id', $request->agency_id);
        }

        $withdrawals = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar withdrawal berhasil diambil.',
            'data' => WithdrawalResource::collection($withdrawals),
            'meta' => [
                'current_page' => $withdrawals->currentPage(),
                'last_page' => $withdrawals->lastPage(),
                'total' => $withdrawals->total(),
            ],
        ]);
    }

    public function pending(): JsonResponse
    {
        $withdrawals = $this->withdrawalService->getPendingWithdrawals();

        return response()->json([
            'success' => true,
            'message' => 'Daftar withdrawal pending berhasil diambil.',
            'data' => WithdrawalResource::collection($withdrawals),
            'meta' => ['total' => $withdrawals->count()],
        ]);
    }

    public function approve(Withdrawal $withdrawal): JsonResponse
    {
        try {
            $this->withdrawalService->approveWithdrawal($withdrawal, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal berhasil disetujui.',
                'data' => new WithdrawalResource($withdrawal->fresh()),
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

    public function reject(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->withdrawalService->rejectWithdrawal($withdrawal, request()->user(), $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal ditolak.',
                'data' => new WithdrawalResource($withdrawal->fresh()),
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

    public function disbursementCallback(Request $request): JsonResponse
    {
        try {
            $this->withdrawalService->handleDisbursementCallback($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Callback disbursement diproses.',
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