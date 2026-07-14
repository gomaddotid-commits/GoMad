<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\VehicleRentalSetting;
use App\Services\RentalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    public function __construct(
        private readonly RentalService $rentalService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $rentals = $this->rentalService->getCustomerRentals(
            $request->user(),
            $request->status
        );

        return response()->json([
            'success' => true,
            'message' => 'Daftar rental berhasil diambil.',
            'data' => $rentals,
            'meta' => ['total' => $rentals->count()],
        ]);
    }

    public function availableVehicles(Request $request): JsonResponse
    {
        $vehicles = $this->rentalService->getAvailableRentalVehicles(
            $request->only(['type'])
        );

        return response()->json([
            'success' => true,
            'message' => 'Daftar mobil rental tersedia.',
            'data' => $vehicles,
            'meta' => ['total' => $vehicles->count()],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'type' => ['required', 'in:self_drive,with_driver'],
            'start_datetime' => ['required', 'date', 'after:now'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'duration_unit' => ['required', 'in:hour,day'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $data = $request->all();
            $data['customer_id'] = $request->user()->id;

            $rental = $this->rentalService->createRentalBooking($data);

            return response()->json([
                'success' => true,
                'message' => 'Booking rental berhasil dibuat.',
                'data' => $rental,
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

    public function show(Rental $rental): JsonResponse
    {
        if ($rental->customer_id !== request()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke rental ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        $rental->load(['vehicle.rentalSetting', 'agency', 'payment']);

        return response()->json([
            'success' => true,
            'message' => 'Detail rental berhasil diambil.',
            'data' => $rental,
            'meta' => null,
        ]);
    }

    public function documentStatus(Request $request): JsonResponse
    {
        $status = $this->rentalService->getCustomerDocumentStatus($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Status dokumen berhasil diambil.',
            'data' => $status,
            'meta' => null,
        ]);
    }

    public function submitDocuments(Request $request): JsonResponse
    {
        $request->validate([
            'ktp_number' => ['required', 'string', 'max:50'],
            'ktp_photo' => ['required', 'string'],
            'sim_number' => ['required', 'string', 'max:50'],
            'sim_photo' => ['required', 'string'],
            'npwp_number' => ['nullable', 'string', 'max:50'],
            'npwp_photo' => ['nullable', 'string'],
        ]);

        try {
            $documents = $this->rentalService->submitDocuments(
                $request->user(),
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil disubmit untuk verifikasi.',
                'data' => $documents,
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

    public function cancel(Rental $rental): JsonResponse
    {
        if ($rental->customer_id !== request()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        if (!in_array($rental->status, ['pending', 'paid'])) {
            return response()->json([
                'success' => false,
                'message' => 'Rental tidak dapat dibatalkan.',
                'data' => null,
                'meta' => null,
            ], 422);
        }

        $rental->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Rental berhasil dibatalkan.',
            'data' => $rental->fresh(),
            'meta' => null,
        ]);
    }
}