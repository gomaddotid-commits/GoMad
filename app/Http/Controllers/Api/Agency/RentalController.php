<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\Vehicle;
use App\Services\RentalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    public function __construct(
        private readonly RentalService $rentalService,
    ) {}

    /**
     * Daftar rental agency
     */
    public function index(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        $rentals = $this->rentalService->getAgencyRentals(
            $agency,
            $request->status
        );

        return response()->json([
            'success' => true,
            'message' => 'Daftar rental berhasil diambil.',
            'data' => $rentals,
            'meta' => ['total' => $rentals->count()],
        ]);
    }

    /**
     * Detail rental
     */
    public function show(Rental $rental): JsonResponse
    {
        $agency = request()->user()->agency;

        if ($rental->agency_id !== $agency->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke rental ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        $rental->load(['vehicle.rentalSetting', 'customer.customerDocuments', 'payment']);

        // ✅ Flow Baru: berkas penyewa hanya diekspos ke agency saat masa sewa (sudah diserahkan)
        $documents = $rental->customer->customerDocuments;
        $released = !is_null($rental->documents_released_at);

        $data = $rental->toArray();
        $data['documents'] = [
            'released' => $released,
            'released_at' => $rental->documents_released_at?->toDateTimeString(),
            'selfie_photo' => $released ? ($documents->selfie_photo ?? null) : null,
            'ktp' => $released ? [
                'number' => $documents->ktp_number ?? null,
                'photo' => $documents->ktp_photo ?? null,
                'verified' => (bool) ($documents->ktp_verified ?? false),
            ] : null,
            'sim' => $released ? [
                'number' => $documents->sim_number ?? null,
                'photo' => $documents->sim_photo ?? null,
                'verified' => (bool) ($documents->sim_verified ?? false),
            ] : null,
            'npwp' => $released ? [
                'number' => $documents->npwp_number ?? null,
                'photo' => $documents->npwp_photo ?? null,
                'verified' => (bool) ($documents->npwp_verified ?? false),
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Detail rental berhasil diambil.',
            'data' => $data,
            'meta' => null,
        ]);
    }

    /**
     * Setup kendaraan untuk rental
     */
    public function setupVehicle(Request $request, Vehicle $vehicle): JsonResponse
    {
        $agency = $request->user()->agency;

        if ($vehicle->agency_id !== $agency->id) {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan bukan milik agency Anda.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        $request->validate([
            'description' => ['nullable', 'string', 'max:2000'],
            'specifications' => ['nullable', 'array'],
            'price_per_hour' => ['nullable', 'numeric', 'min:0'],
            'price_per_day' => ['nullable', 'numeric', 'min:0'],
            'allow_self_drive' => ['nullable', 'boolean'],
            'allow_with_driver' => ['nullable', 'boolean'],
            'driver_fee_per_hour' => ['nullable', 'numeric', 'min:0'],
            'driver_fee_per_day' => ['nullable', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['string', 'in:midtrans,ots'],
            'requirements' => ['nullable', 'array'],
            'photos' => ['nullable', 'array'],
            'terms_conditions' => ['nullable', 'array'],
        ]);

        try {
            $setting = $this->rentalService->setupVehicleForRental($vehicle, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Kendaraan berhasil disetup untuk rental.',
                'data' => $setting,
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

    /**
     * Daftar kendaraan yang bisa disewakan
     */
    public function rentableVehicles(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        $vehicles = Vehicle::with('rentalSetting')
            ->where('agency_id', $agency->id)
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar kendaraan berhasil diambil.',
            'data' => $vehicles,
            'meta' => ['total' => $vehicles->count()],
        ]);
    }

    /**
     * Verifikasi pengambilan mobil
     */
    public function verifyPickup(Rental $rental): JsonResponse
    {
        $agency = request()->user()->agency;

        if ($rental->agency_id !== $agency->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        try {
            $rental = $this->rentalService->verifyPickup($rental);

            return response()->json([
                'success' => true,
                'message' => 'Pengambilan mobil berhasil diverifikasi.',
                'data' => $rental,
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

    /**
     * Verifikasi pengembalian mobil
     */
    public function verifyReturn(Rental $rental): JsonResponse
    {
        $agency = request()->user()->agency;

        if ($rental->agency_id !== $agency->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        try {
            $rental = $this->rentalService->verifyReturn($rental);

            return response()->json([
                'success' => true,
                'message' => 'Pengembalian mobil berhasil diverifikasi.',
                'data' => $rental,
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

    /**
     * Selesaikan rental
     */
    public function complete(Rental $rental): JsonResponse
    {
        $agency = request()->user()->agency;

        if ($rental->agency_id !== $agency->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        try {
            $rental = $this->rentalService->completeRental($rental);

            return response()->json([
                'success' => true,
                'message' => 'Rental berhasil diselesaikan.',
                'data' => $rental,
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