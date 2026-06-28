<?php
// File: app/Http/Controllers/Api/Customer/ScheduleController.php
// Deskripsi: API Controller untuk jadwal dari sisi customer

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
    ) {}

    public function availableSchedules(): JsonResponse
    {
        $schedules = Schedule::with(['route', 'agency', 'vehicle'])
            ->where('is_active', true)
            ->where('departure_date', '>=', now()->toDateString())
            ->orderBy('departure_date')
            ->orderBy('departure_time')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal tersedia berhasil diambil.',
            'data' => \App\Http\Resources\Api\ScheduleResource::collection($schedules),
            'meta' => ['total' => $schedules->count()],
        ]);
    }

    public function scheduleStops(Schedule $schedule): JsonResponse
    {
        $scheduleData = $this->scheduleService->getScheduleWithPricing($schedule);

        return response()->json([
            'success' => true,
            'message' => 'Stop dan harga berhasil diambil.',
            'data' => $scheduleData,
            'meta' => null,
        ]);
    }

    public function schedulePricing(Schedule $schedule): JsonResponse
    {
        $pricingService = app(\App\Services\PricingService::class);
        $pricingMatrix = $pricingService->getAllPricingForSchedule($schedule);

        return response()->json([
            'success' => true,
            'message' => 'Matrix harga berhasil diambil.',
            'data' => [
                'pricing_matrix' => $pricingMatrix,
                'min_price' => $pricingService->getMinimumPrice($schedule),
                'max_price' => $pricingService->getMaximumPrice($schedule),
            ],
            'meta' => null,
        ]);
    }

    /**
     * Get available dropoff stops untuk origin tertentu
     */
    public function availableDropoffs(Schedule $schedule, int $originStopId): JsonResponse
    {
        try {
            $dropoffs = $this->scheduleService->getAvailableDropoffStops($schedule, $originStopId);

            return response()->json([
                'success' => true,
                'message' => 'Daftar kota tujuan berhasil diambil.',
                'data' => $dropoffs,
                'meta' => [
                    'total' => count($dropoffs),
                    'origin_stop_id' => $originStopId,
                    'schedule_id' => $schedule->id,
                ],
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