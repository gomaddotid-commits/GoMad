<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Models\PassengerTransfer;
use App\Models\Schedule;
use App\Services\PassengerTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function __construct(
        private readonly PassengerTransferService $transferService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;
        $transfers = $this->transferService->getAgencyTransfers($agency->id);

        return response()->json([
            'success' => true,
            'message' => 'Daftar transfer berhasil diambil.',
            'data' => $transfers,
            'meta' => ['total' => $transfers->count()],
        ]);
    }

    public function availableSchedules(Request $request, Schedule $schedule): JsonResponse
    {
        $availableSchedules = $this->transferService->findAvailableSchedules($schedule);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal tersedia untuk transfer.',
            'data' => $availableSchedules,
            'meta' => ['total' => $availableSchedules->count()],
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate([
            'from_schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'to_schedule_id' => ['required', 'integer', 'exists:schedules,id', 'different:from_schedule_id'],
            'booking_ids' => ['required', 'array', 'min:1'],
            'booking_ids.*' => ['integer', 'exists:bookings,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $transfer = $this->transferService->createTransferRequest($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Permintaan transfer berhasil dibuat.',
                'data' => $transfer,
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

    public function approve(PassengerTransfer $transfer): JsonResponse
    {
        try {
            $this->transferService->approveTransfer($transfer, request()->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Transfer disetujui.',
                'data' => $transfer->fresh(),
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

    public function reject(Request $request, PassengerTransfer $transfer): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->transferService->rejectTransfer($transfer, $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Transfer ditolak.',
                'data' => null,
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

    public function cancel(PassengerTransfer $transfer): JsonResponse
    {
        try {
            $this->transferService->cancelTransfer($transfer);

            return response()->json([
                'success' => true,
                'message' => 'Transfer dibatalkan.',
                'data' => null,
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