<?php
// File: app/Http/Controllers/Api/Public/ScheduleController.php
// Deskripsi: API Controller untuk akses jadwal public (dropoffs)

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
    ) {}

    /**
     * Get available dropoff stops untuk origin tertentu (PUBLIC)
     */
    public function availableDropoffs(Schedule $schedule, int $originStopId): JsonResponse
    {
        try {
            $dropoffs = $this->scheduleService->getAvailableDropoffStops($schedule, $originStopId);

            return response()->json([
                'success' => true,
                'message' => 'Daftar kota tujuan berhasil diambil.',
                'data' => $dropoffs,
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data: ' . $e->getMessage(),
                'data' => [],
                'meta' => null,
            ], 500);
        }
    }
}

// End of file