<?php
// File: app/Http/Controllers/Api/Driver/LocationController.php
// Deskripsi: API Controller untuk update lokasi driver

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\DriverLocation;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $driver = $request->user();

        $schedule = Schedule::where('id', $request->schedule_id)
            ->where('driver_id', $driver->id)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal tidak ditemukan atau Anda tidak ditugaskan.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        $location = DriverLocation::create([
            'schedule_id' => $schedule->id,
            'driver_id' => $driver->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lokasi berhasil diupdate.',
            'data' => [
                'latitude' => (float) $location->latitude,
                'longitude' => (float) $location->longitude,
                'recorded_at' => $location->recorded_at->format('Y-m-d H:i:s'),
            ],
            'meta' => null,
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        $driver = $request->user();

        $schedule = Schedule::where('driver_id', $driver->id)
            ->where('departure_date', now()->toDateString())
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada jadwal aktif.',
                'data' => null,
                'meta' => null,
            ]);
        }

        $latestLocation = DriverLocation::where('schedule_id', $schedule->id)
            ->where('driver_id', $driver->id)
            ->latest('recorded_at')
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Lokasi terkini berhasil diambil.',
            'data' => $latestLocation ? [
                'latitude' => (float) $latestLocation->latitude,
                'longitude' => (float) $latestLocation->longitude,
                'recorded_at' => $latestLocation->recorded_at->format('Y-m-d H:i:s'),
                'schedule_id' => $schedule->id,
            ] : null,
            'meta' => null,
        ]);
    }
}

// End of file