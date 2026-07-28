<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Services\RentalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    public function __construct(
        private readonly RentalService $rentalService,
    ) {}

    /**
     * Daftar tugas rental driver (hanya with_driver)
     */
    public function index(Request $request): JsonResponse
    {
        $driver = $request->user();

        $rentals = Rental::with([
                'vehicle.rentalSetting',
                'customer',
                'agency',
            ])
            ->where('driver_id', $driver->id)
            ->where('type', 'with_driver')
            ->whereIn('status', ['paid', 'active'])
            ->orderBy('start_datetime')
            ->get()
            ->map(function ($rental) {
                $vehicle = $rental->vehicle;
                $pickupAddress = $rental->pickup_address;
                $destinationAddress = $rental->destination_address;

                return [
                    'id' => $rental->id,
                    'rental_code' => $rental->rental_code,
                    'type' => $rental->type,
                    'type_label' => 'Dengan Supir',
                    'status' => $rental->status,
                    'status_label' => $rental->status_label,
                    'start_datetime' => $rental->start_datetime->format('Y-m-d H:i:s'),
                    'start_datetime_formatted' => $rental->start_datetime->format('d M Y H:i'),
                    'end_datetime' => $rental->end_datetime->format('Y-m-d H:i:s'),
                    'end_datetime_formatted' => $rental->end_datetime->format('d M Y H:i'),
                    'duration' => $rental->duration,
                    'duration_unit' => $rental->duration_unit,
                    'total_price' => (float) $rental->total_price,
                    'vehicle' => [
                        'id' => $vehicle->id,
                        'plate_number' => $vehicle->plate_number,
                        'brand' => $vehicle->brand,
                        'model' => $vehicle->model,
                        'year' => $vehicle->year,
                        'vehicle_image' => $vehicle->vehicle_image,
                    ],
                    'customer' => [
                        'id' => $rental->customer->id ?? null,
                        'name' => $rental->customer->name ?? '-',
                        'phone' => $rental->customer->phone ?? '-',
                    ],
                    'agency' => [
                        'id' => $rental->agency->id ?? null,
                        'name' => $rental->agency->agency_name ?? '-',
                        'phone' => $rental->agency->contact_alternate ?? '-',
                    ],
                    'pickup_address' => $pickupAddress,
                    'pickup_maps_url' => $rental->pickup_maps_link
                        ?: 'https://www.google.com/maps/search/?api=1&query=' . urlencode($pickupAddress),
                    'pickup_nav_url' => 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($pickupAddress),
                    'destination_address' => $destinationAddress,
                    'destination_maps_url' => $destinationAddress
                        ? ($rental->destination_maps_link
                            ?: 'https://www.google.com/maps/search/?api=1&query=' . urlencode($destinationAddress))
                        : null,
                    'destination_nav_url' => $destinationAddress
                        ? 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($destinationAddress)
                        : null,
                    'notes' => $rental->notes,
                    'started_at' => $rental->started_at?->format('Y-m-d H:i:s'),
                    'returned_at' => $rental->returned_at?->format('Y-m-d H:i:s'),
                    'can_verify_pickup' => $rental->status === 'paid',
                    'can_verify_return' => $rental->status === 'active',
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Daftar tugas rental berhasil diambil.',
            'data' => $rentals,
            'meta' => ['total' => $rentals->count()],
        ]);
    }

    /**
     * Detail satu tugas rental
     */
    public function show(Request $request, Rental $rental): JsonResponse
    {
        $driver = $request->user();

        if ($rental->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak bertugas di rental ini.',
            ], 403);
        }

        $rental->load(['vehicle.rentalSetting', 'customer', 'agency']);

        $pickupAddress = $rental->pickup_address;
        $destinationAddress = $rental->destination_address;

        return response()->json([
            'success' => true,
            'message' => 'Detail rental berhasil diambil.',
            'data' => [
                'id' => $rental->id,
                'rental_code' => $rental->rental_code,
                'type' => $rental->type,
                'type_label' => 'Dengan Supir',
                'status' => $rental->status,
                'status_label' => $rental->status_label,
                'start_datetime' => $rental->start_datetime->format('Y-m-d H:i:s'),
                'end_datetime' => $rental->end_datetime->format('Y-m-d H:i:s'),
                'duration' => $rental->duration,
                'duration_unit' => $rental->duration_unit,
                'total_price' => (float) $rental->total_price,
                'vehicle' => [
                    'plate_number' => $rental->vehicle->plate_number,
                    'brand' => $rental->vehicle->brand,
                    'model' => $rental->vehicle->model,
                    'year' => $rental->vehicle->year,
                    'vehicle_image' => $rental->vehicle->vehicle_image,
                ],
                'customer' => [
                    'name' => $rental->customer->name ?? '-',
                    'phone' => $rental->customer->phone ?? '-',
                ],
                'agency' => [
                    'name' => $rental->agency->agency_name ?? '-',
                    'phone' => $rental->agency->contact_alternate ?? '-',
                ],
                'pickup_address' => $pickupAddress,
                'pickup_maps_url' => $rental->pickup_maps_link
                    ?: 'https://www.google.com/maps/search/?api=1&query=' . urlencode($pickupAddress),
                'pickup_nav_url' => 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($pickupAddress),
                'destination_address' => $destinationAddress,
                'destination_maps_url' => $destinationAddress
                    ? ($rental->destination_maps_link
                        ?: 'https://www.google.com/maps/search/?api=1&query=' . urlencode($destinationAddress))
                    : null,
                'destination_nav_url' => $destinationAddress
                    ? 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($destinationAddress)
                    : null,
                'notes' => $rental->notes,
                'started_at' => $rental->started_at?->format('Y-m-d H:i:s'),
                'returned_at' => $rental->returned_at?->format('Y-m-d H:i:s'),
                'can_verify_pickup' => $rental->status === 'paid',
                'can_verify_return' => $rental->status === 'active',
            ],
        ]);
    }

    /**
     * Driver verifikasi pengambilan mobil
     */
    public function verifyPickup(Request $request, Rental $rental): JsonResponse
    {
        $driver = $request->user();

        if ($rental->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak bertugas di rental ini.',
            ], 403);
        }

        if ($rental->type !== 'with_driver') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya rental dengan supir.',
            ], 400);
        }

        if ($rental->status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Rental harus dalam status Siap Diambil.',
            ], 400);
        }

        try {
            $this->rentalService->verifyPickup($rental);

            return response()->json([
                'success' => true,
                'message' => 'Pengambilan mobil berhasil diverifikasi.',
                'data' => [
                    'status' => $rental->fresh()->status,
                    'started_at' => $rental->started_at->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Driver verifikasi pengembalian mobil
     */
    public function verifyReturn(Request $request, Rental $rental): JsonResponse
    {
        $driver = $request->user();

        if ($rental->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak bertugas di rental ini.',
            ], 403);
        }

        if ($rental->type !== 'with_driver') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya rental dengan supir.',
            ], 400);
        }

        if ($rental->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Rental harus dalam status Sedang Disewa.',
            ], 400);
        }

        try {
            $this->rentalService->verifyReturn($rental);

            return response()->json([
                'success' => true,
                'message' => 'Pengembalian mobil berhasil diverifikasi.',
                'data' => [
                    'status' => $rental->fresh()->status,
                    'returned_at' => $rental->returned_at->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}