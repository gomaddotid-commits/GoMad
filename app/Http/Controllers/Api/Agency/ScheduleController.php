<?php
// File: app/Http/Controllers/Api/Agency/ScheduleController.php
// Deskripsi: API Controller untuk manajemen jadwal oleh agency

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateScheduleRequest;
use App\Http\Resources\Api\ScheduleResource;
use App\Models\Schedule;
use App\Services\ScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        $query = Schedule::with(['route', 'vehicle', 'driver'])
            ->where('agency_id', $agency->id);

        if ($request->date) {
            $query->where('departure_date', $request->date);
        }

        if ($request->status === 'upcoming') {
            $query->where('departure_date', '>=', now()->toDateString());
        } elseif ($request->status === 'past') {
            $query->where('departure_date', '<', now()->toDateString());
        }

        $schedules = $query->orderBy('departure_date', $request->status === 'past' ? 'desc' : 'asc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar jadwal berhasil diambil.',
            'data' => ScheduleResource::collection($schedules),
            'meta' => [
                'current_page' => $schedules->currentPage(),
                'last_page' => $schedules->lastPage(),
                'total' => $schedules->total(),
            ],
        ]);
    }

    public function store(CreateScheduleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['agency_id'] = $request->user()->agency->id;

        $schedule = $this->scheduleService->createSchedule($data);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil dibuat.',
            'data' => new ScheduleResource($schedule),
            'meta' => null,
        ], 201);
    }

    public function show(Schedule $schedule): JsonResponse
    {
        $scheduleData = $this->scheduleService->getScheduleWithPricing($schedule);

        return response()->json([
            'success' => true,
            'message' => 'Detail jadwal berhasil diambil.',
            'data' => $scheduleData,
            'meta' => null,
        ]);
    }

    public function update(CreateScheduleRequest $request, Schedule $schedule): JsonResponse
    {
        try {
            $schedule = $this->scheduleService->updateSchedule($schedule, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil diupdate.',
                'data' => new ScheduleResource($schedule),
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

    public function destroy(Schedule $schedule): JsonResponse
    {
        if ($schedule->bookings()->whereNotIn('status', ['cancelled'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal sudah memiliki booking aktif, tidak dapat dihapus.',
                'data' => null,
                'meta' => null,
            ], 422);
        }

        $schedule->update(['is_active' => false]);
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil dihapus.',
            'data' => null,
            'meta' => null,
        ]);
    }

    public function assignDriver(Request $request, Schedule $schedule): JsonResponse
    {
        $request->validate([
            'driver_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            $driver = \App\Models\User::findOrFail($request->driver_id);
            $this->scheduleService->assignDriver($schedule, $driver);

            return response()->json([
                'success' => true,
                'message' => 'Driver berhasil ditugaskan.',
                'data' => new ScheduleResource($schedule->fresh()),
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

    public function pricing(Schedule $schedule): JsonResponse
    {
        $pricingService = app(\App\Services\PricingService::class);
        $pricingMatrix = $pricingService->getAllPricingForSchedule($schedule);

        return response()->json([
            'success' => true,
            'message' => 'Matrix harga berhasil diambil.',
            'data' => $pricingMatrix,
            'meta' => null,
        ]);
    }

    public function requiredPairs(Schedule $schedule): JsonResponse
    {
        $pairs = $this->scheduleService->generateRequiredPairs($schedule);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pasangan wajib berhasil diambil.',
            'data' => $pairs,
            'meta' => [
                'total_pairs' => count($pairs),
            ],
        ]);
    }

    /**
     * Get stop configuration untuk form konfigurasi
     */
    public function stopConfig(Schedule $schedule): JsonResponse
    {
        $stops = $this->scheduleService->getStopConfiguration($schedule);
        $existingPricing = RoutePricing::where('schedule_id', $schedule->id)
            ->get()
            ->map(function($p) {
                return [
                    'origin_stop_id' => $p->origin_stop_id,
                    'destination_stop_id' => $p->destination_stop_id,
                    'price' => (float) $p->price,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi stop berhasil diambil.',
            'data' => [
                'stops' => $stops,
                'existing_pricing' => $existingPricing,
            ],
            'meta' => null,
        ]);
    }

    /**
     * Toggle Pickup/Dropoff per stop - dipanggil via AJAX
     * Return pairs yang perlu diisi harganya
     */
    public function toggleStop(Request $request, Schedule $schedule): JsonResponse
    {
        $request->validate([
            'route_stop_id' => ['required', 'integer'],
            'type' => ['required', 'in:pickup,dropoff'],
            'enabled' => ['required', 'boolean'],
        ]);

        // Update schedule stop
        $field = $request->type === 'pickup' ? 'is_pickup_available' : 'is_dropoff_available';
        
        ScheduleStop::where('schedule_id', $schedule->id)
            ->where('route_stop_id', $request->route_stop_id)
            ->update([$field => $request->enabled]);

        // Refresh schedule stops
        $schedule->load('scheduleStops.routeStop');

        // Generate pairs yang perlu diisi
        $newPairs = $this->scheduleService->generatePairsForStopToggle(
            $schedule,
            $request->route_stop_id,
            $request->type,
            $request->enabled
        );

        // Get existing pricing untuk pairs ini
        $existingPrices = RoutePricing::where('schedule_id', $schedule->id)
            ->where(function($q) use ($newPairs) {
                foreach ($newPairs as $pair) {
                    $q->orWhere(function($sq) use ($pair) {
                        $sq->where('origin_stop_id', $pair['origin_stop_id'])
                        ->where('destination_stop_id', $pair['destination_stop_id']);
                    });
                }
            })
            ->get()
            ->keyBy(function($p) {
                return $p->origin_stop_id . '-' . $p->destination_stop_id;
            });

        // Filter pairs yang belum ada harganya
        $pairsNeedPrice = [];
        foreach ($newPairs as $pair) {
            $key = $pair['origin_stop_id'] . '-' . $pair['destination_stop_id'];
            if (!isset($existingPrices[$key])) {
                $pairsNeedPrice[] = array_merge($pair, [
                    'current_price' => null,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Stop berhasil diupdate.',
            'data' => [
                'pairs_need_price' => $pairsNeedPrice,
                'total_new_pairs' => count($pairsNeedPrice),
            ],
            'meta' => null,
        ]);
    }

    /**
     * Simpan pricing untuk pairs yang diisi
     */
    public function savePricing(Request $request, Schedule $schedule): JsonResponse
    {
        $request->validate([
            'pricing' => ['required', 'array'],
            'pricing.*.origin_stop_id' => ['required', 'integer'],
            'pricing.*.destination_stop_id' => ['required', 'integer'],
            'pricing.*.price' => ['required', 'numeric', 'min:1000'],
        ]);

        foreach ($request->pricing as $priceItem) {
            RoutePricing::updateOrCreate(
                [
                    'schedule_id' => $schedule->id,
                    'origin_stop_id' => $priceItem['origin_stop_id'],
                    'destination_stop_id' => $priceItem['destination_stop_id'],
                ],
                [
                    'price' => $priceItem['price'],
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Harga berhasil disimpan.',
            'data' => null,
            'meta' => null,
        ]);
    }
}

// End of file