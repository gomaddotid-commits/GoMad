<?php

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use App\Models\Schedule;
use App\Services\PromoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function __construct(
        private readonly PromoService $promoService,
    ) {}

    public function available(Request $request): JsonResponse
    {
        $promos = Promo::active()->selective()->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar promo selektif tersedia.',
            'data' => $promos,
            'meta' => ['total' => $promos->count()],
        ]);
    }

    public function attach(Request $request): JsonResponse
    {
        $request->validate([
            'promo_id' => ['required', 'integer', 'exists:promos,id'],
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
        ]);

        $this->promoService->attachPromoToSchedule($request->promo_id, $request->schedule_id);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil dipasang ke jadwal.',
            'data' => null,
            'meta' => null,
        ]);
    }

    public function detach(Schedule $schedule, Promo $promo): JsonResponse
    {
        $schedule->promos()->detach($promo->id);

        return response()->json([
            'success' => true,
            'message' => 'Promo berhasil dilepas dari jadwal.',
            'data' => null,
            'meta' => null,
        ]);
    }
}