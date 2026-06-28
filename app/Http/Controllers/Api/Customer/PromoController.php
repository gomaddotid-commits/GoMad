<?php
// File: app/Http/Controllers/Api/Customer/PromoController.php
// Deskripsi: API Controller untuk promo customer

namespace App\Http\Controllers\Api\Customer;

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

    /**
     * Dapatkan promo yang tersedia untuk customer
     */
    public function available(Request $request): JsonResponse
    {
        $user = $request->user();
        $promos = $this->promoService->getAvailablePromosForCustomer($user);

        // Jika ada schedule_id, tambahkan promo selektif
        if ($request->schedule_id) {
            $schedulePromos = Promo::whereHas('schedules', function($q) use ($request) {
                $q->where('schedule_id', $request->schedule_id);
            })->active()->get();
            $promos = $promos->merge($schedulePromos);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar promo tersedia.',
            'data' => $promos->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'type' => $p->type,
                'description' => $p->description,
                'discount_percent' => (float) $p->discount_percent,
                'max_discount' => (float) $p->max_discount,
                'min_purchase' => (float) $p->min_purchase,
                'start_date' => $p->start_date->format('Y-m-d'),
                'end_date' => $p->end_date->format('Y-m-d'),
            ]),
            'meta' => ['total' => $promos->count()],
        ]);
    }

    /**
     * Hitung diskon dari promo
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'promo_id' => ['required', 'integer', 'exists:promos,id'],
            'total_price' => ['required', 'numeric', 'min:0'],
        ]);

        $promo = Promo::findOrFail($request->promo_id);
        $discount = $this->promoService->calculateDiscount($promo, (float) $request->total_price);
        $finalPrice = max(0, (float) $request->total_price - $discount);

        return response()->json([
            'success' => true,
            'message' => 'Perhitungan diskon.',
            'data' => [
                'original_price' => (float) $request->total_price,
                'discount_amount' => $discount,
                'final_price' => $finalPrice,
                'promo_name' => $promo->name,
                'discount_percent' => (float) $promo->discount_percent,
            ],
            'meta' => null,
        ]);
    }
}

// End of file