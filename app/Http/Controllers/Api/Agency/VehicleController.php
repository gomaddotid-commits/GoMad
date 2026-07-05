<?php
// File: app/Http/Controllers/Api/Agency/VehicleController.php
// Deskripsi: API Controller untuk manajemen kendaraan oleh agency

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        $vehicles = Vehicle::where('agency_id', $agency->id)
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar kendaraan berhasil diambil.',
            'data' => $vehicles->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'plate_number' => $vehicle->plate_number,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'year' => $vehicle->year,
                    'capacity' => $vehicle->capacity,
                    'type' => $vehicle->type,
                    'vehicle_image' => $vehicle->vehicle_image ?? null,
                    'is_active' => $vehicle->is_active,
                    'created_at' => $vehicle->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'total' => $vehicles->count(),
            ],
        ]);
    }

    public function store(VehicleRequest $request): JsonResponse
    {
        $agency = $request->user()->agency;

        $vehicle = Vehicle::create([
            'agency_id' => $agency->id,
            'plate_number' => $request->plate_number,
            'brand' => $request->brand,
            'model' => $request->model,
            'year' => $request->year,
            'capacity' => $request->capacity,
            'type' => $request->type,
            'is_active' => true,
        ]);

        $agency->increment('fleet_size');

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan berhasil ditambahkan.',
            'data' => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'capacity' => $vehicle->capacity,
                'type' => $vehicle->type,
            ],
            'meta' => null,
        ], 201);
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail kendaraan berhasil diambil.',
            'data' => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'year' => $vehicle->year,
                'capacity' => $vehicle->capacity,
                'type' => $vehicle->type,
                'vehicle_image' => $vehicle->vehicle_image ?? null,
                'is_active' => $vehicle->is_active,
                'created_at' => $vehicle->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $vehicle->updated_at->format('Y-m-d H:i:s'),
            ],
            'meta' => null,
        ]);
    }

    public function update(VehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $vehicle->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan berhasil diupdate.',
            'data' => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'capacity' => $vehicle->capacity,
                'type' => $vehicle->type,
            ],
            'meta' => null,
        ]);
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $hasActiveSchedules = $vehicle->schedules()
            ->where('departure_date', '>=', now()->toDateString())
            ->where('is_active', true)
            ->exists();

        if ($hasActiveSchedules) {
            return response()->json([
                'success' => false,
                'message' => 'Kendaraan masih memiliki jadwal aktif.',
                'data' => null,
                'meta' => null,
            ], 422);
        }

        $vehicle->update(['is_active' => false]);
        $vehicle->delete();

        $vehicle->agency->decrement('fleet_size');

        return response()->json([
            'success' => true,
            'message' => 'Kendaraan berhasil dihapus.',
            'data' => null,
            'meta' => null,
        ]);
    }
}

// End of file