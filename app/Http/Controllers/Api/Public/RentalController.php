<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\RentalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    public function __construct(
        private readonly RentalService $rentalService,
    ) {}

    public function availability(Request $request, Vehicle $vehicle): JsonResponse
    {
        $request->validate([
            'start_datetime' => ['nullable', 'date'],
            'end_datetime' => ['nullable', 'date'],
        ]);

        $bookedDates = $this->rentalService->getBookedDates($vehicle->id);

        $isAvailable = true;
        if ($request->start_datetime && $request->end_datetime) {
            $isAvailable = $this->rentalService->isVehicleAvailable(
                $vehicle->id,
                $request->start_datetime,
                $request->end_datetime
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Data ketersediaan berhasil diambil.',
            'data' => [
                'vehicle_id' => $vehicle->id,
                'is_available' => $isAvailable,
                'booked_dates' => $bookedDates,
            ],
            'meta' => null,
        ]);
    }
}