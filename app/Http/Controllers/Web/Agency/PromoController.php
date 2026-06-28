<?php
// File: app/Http/Controllers/Web/Agency/PromoController.php
// Deskripsi: Web Controller untuk promo dari sisi agency

namespace App\Http\Controllers\Web\Agency;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use App\Models\Schedule;
use App\Services\PromoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromoController extends Controller
{
    public function __construct(
        private readonly PromoService $promoService,
    ) {}

    /**
     * Daftar promo selektif yang tersedia untuk agency
     */
    public function index(): View
    {
        $agency = auth()->user()->agency;
        $promos = Promo::active()->selective()->latest()->get();
        
        // Jadwal agency yang bisa dipasangi promo
        $schedules = $agency->schedules()
            ->where('departure_date', '>=', now()->toDateString())
            ->where('is_active', true)
            ->with(['route', 'promos'])
            ->latest()
            ->limit(20)
            ->get();

        return view('agency.promos.index', compact('promos', 'schedules'));
    }

    /**
     * Aktifkan promo untuk jadwal tertentu
     */
    public function attachToSchedule(Request $request): RedirectResponse
    {
        $request->validate([
            'promo_id' => ['required', 'integer', 'exists:promos,id'],
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
        ]);

        $schedule = Schedule::where('agency_id', auth()->user()->agency->id)
            ->findOrFail($request->schedule_id);

        $this->promoService->attachPromoToSchedule($request->promo_id, $request->schedule_id);

        return back()->with('success', 'Promo berhasil diaktifkan untuk jadwal!');
    }

    /**
     * Nonaktifkan promo dari jadwal
     */
    public function detachFromSchedule(Schedule $schedule, Promo $promo): RedirectResponse
    {
        if ($schedule->agency_id !== auth()->user()->agency->id) abort(403);

        $schedule->promos()->detach($promo->id);

        return back()->with('success', 'Promo dinonaktifkan dari jadwal.');
    }
}

// End of file