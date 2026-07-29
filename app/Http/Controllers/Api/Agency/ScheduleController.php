<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateScheduleRequest;
use App\Http\Resources\Api\ScheduleResource;
use App\Models\RoutePricing;
use App\Models\Schedule;
use App\Models\ScheduleStop;
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

        $query = Schedule::with(['route', 'vehicle', 'driver', 'ppSchedule', 'childSchedule'])
            ->where('agency_id', $agency->id)
            ->whereNull('parent_schedule_id'); // Hanya schedule pergi

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

        // ✅ TAMBAHKAN: Validasi PP pricing wajib diisi jika PP diaktifkan
        if (!empty($data['is_pp']) && $data['is_pp'] == '1') {
            if (empty($data['pp_pricing'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Harga untuk jadwal PP wajib diisi jika PP diaktifkan.',
                    'data' => null,
                    'meta' => null,
                ], 422);
            }
            if (empty($data['pp_stop_config'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Konfigurasi stop untuk jadwal PP wajib diisi.',
                    'data' => null,
                    'meta' => null,
                ], 422);
            }
            if (empty($data['pp_date'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tanggal PP harus diisi.',
                    'data' => null,
                    'meta' => null,
                ], 422);
            }
        }

        try {
            $schedule = $this->scheduleService->createSchedule($data);

            $message = 'Jadwal berhasil dibuat.';
            if (!empty($data['is_pp']) && $data['is_pp'] == '1') {
                $message = 'Jadwal Pergi & Pulang (PP) berhasil dibuat!';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => new ScheduleResource($schedule),
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

    public function show(Schedule $schedule): JsonResponse
    {
        $schedule->load(['ppSchedule', 'childSchedule', 'parentSchedule']);
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

        // Release COD deposit
        if ($schedule->allow_cod && $schedule->cod_min_balance > 0) {
            $walletService = app(\App\Services\WalletService::class);
            $walletService->releaseCodDeposit(
                $schedule->agency,
                $schedule->cod_min_balance,
                $schedule->id
            );
        }

        // Hapus juga schedule PP jika ada
        if ($schedule->ppSchedule) {
            $ppSchedule = $schedule->ppSchedule;
            if ($ppSchedule->allow_cod && $ppSchedule->cod_min_balance > 0) {
                $walletService = app(\App\Services\WalletService::class);
                $walletService->releaseCodDeposit(
                    $ppSchedule->agency,
                    $ppSchedule->cod_min_balance,
                    $ppSchedule->id
                );
            }
            $ppSchedule->update(['is_active' => false]);
            $ppSchedule->delete();
        }

        $schedule->update(['is_active' => false]);
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil dihapus. Saldo deposit COD telah dikembalikan.',
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
            'meta' => ['total_pairs' => count($pairs)],
        ]);
    }

    public function stopConfig(Schedule $schedule): JsonResponse
    {
        $stops = $this->scheduleService->getStopConfiguration($schedule);
        $existingPricing = RoutePricing::where('schedule_id', $schedule->id)
            ->get()
            ->map(function ($p) {
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

    public function toggleStop(Request $request, Schedule $schedule): JsonResponse
    {
        $request->validate([
            'route_stop_id' => ['required', 'integer'],
            'type' => ['required', 'in:pickup,dropoff'],
            'enabled' => ['required', 'boolean'],
        ]);

        $field = $request->type === 'pickup' ? 'is_pickup_available' : 'is_dropoff_available';

        ScheduleStop::where('schedule_id', $schedule->id)
            ->where('route_stop_id', $request->route_stop_id)
            ->update([$field => $request->enabled]);

        $schedule->load('scheduleStops.routeStop');

        $newPairs = $this->scheduleService->generatePairsForStopToggle(
            $schedule,
            $request->route_stop_id,
            $request->type,
            $request->enabled
        );

        $existingPrices = RoutePricing::where('schedule_id', $schedule->id)
            ->where(function ($q) use ($newPairs) {
                foreach ($newPairs as $pair) {
                    $q->orWhere(function ($sq) use ($pair) {
                        $sq->where('origin_stop_id', $pair['origin_stop_id'])
                           ->where('destination_stop_id', $pair['destination_stop_id']);
                    });
                }
            })
            ->get()
            ->keyBy(function ($p) {
                return $p->origin_stop_id . '-' . $p->destination_stop_id;
            });

        $pairsNeedPrice = [];
        foreach ($newPairs as $pair) {
            $key = $pair['origin_stop_id'] . '-' . $pair['destination_stop_id'];
            if (!isset($existingPrices[$key])) {
                $pairsNeedPrice[] = array_merge($pair, ['current_price' => null]);
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
                ['price' => $priceItem['price']]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Harga berhasil disimpan.',
            'data' => null,
            'meta' => null,
        ]);
    }

    /**
     * Agency klik Mulai - serahkan data ke driver
     */
    public function startSchedule(Schedule $schedule): JsonResponse
    {
        $agency = request()->user()->agency;

        if ($schedule->agency_id !== $agency->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke jadwal ini.',
            ], 403);
        }

        if ($schedule->started_at) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal sudah dimulai.',
            ], 400);
        }

        if ($schedule->departure_date->toDateString() !== now()->toDateString()) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal hanya bisa dimulai pada tanggal keberangkatan (' . $schedule->departure_date->format('d M Y') . ').',
            ], 400);
        }

        $schedule->update(['started_at' => now()]);

        // Mulai juga schedule PP jika ada
        if ($schedule->ppSchedule && !$schedule->ppSchedule->started_at) {
            $schedule->ppSchedule->update(['started_at' => now()]);
        }

        // Notifikasi driver
        if ($schedule->driver) {
            app(\App\Services\NotificationService::class)->sendWhatsApp(
                $schedule->driver->phone,
                "🚀 *Jadwal Dimulai!*\n\n" .
                "Rute: {$schedule->route->route_name}\n" .
                "Tanggal: {$schedule->departure_date->format('d M Y')}\n" .
                "Jam: {$schedule->departure_time}\n\n" .
                "Data penumpang sudah bisa diakses. Silakan cek aplikasi."
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Jadwal dimulai! Data penumpang telah diserahkan ke driver.',
            'data' => new ScheduleResource($schedule->fresh()),
            'meta' => null,
        ]);
    }
}