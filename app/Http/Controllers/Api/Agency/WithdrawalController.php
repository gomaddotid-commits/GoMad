<?php
// File: app/Http/Controllers/Api/Agency/WithdrawalController.php
// Deskripsi: API Controller untuk penarikan dana agency

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateWithdrawalRequest;
use App\Http\Resources\Api\WithdrawalResource;
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
        $agency = $request->user()->agency;

        $withdrawals = $this->withdrawalService->getAgencyWithdrawals(
            $agency,
            $request->status
        );

        return response()->json([
            'success' => true,
            'message' => 'Daftar penarikan berhasil diambil.',
            'data' => WithdrawalResource::collection($withdrawals),
            'meta' => [
                'total' => $withdrawals->count(),
            ],
        ]);
    }

    public function store(CreateWithdrawalRequest $request): JsonResponse
    {
        $agency = $request->user()->agency;

        try {
            $withdrawal = $this->withdrawalService->createWithdrawal(
                $agency,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Penarikan berhasil dibuat.',
                'data' => new WithdrawalResource($withdrawal),
                'meta' => null,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 422);
        }
    }

    public function show(Request $request, $withdrawal): JsonResponse
    {
        $agency = $request->user()->agency;
        $withdrawal = \App\Models\Withdrawal::where('agency_id', $agency->id)
            ->findOrFail($withdrawal);

        return response()->json([
            'success' => true,
            'message' => 'Detail penarikan berhasil diambil.',
            'data' => new WithdrawalResource($withdrawal),
            'meta' => null,
        ]);
    }
}

// End of file