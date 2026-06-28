<?php
// File: app/Http/Controllers/Web/Customer/HomeController.php
// Deskripsi: Web Controller untuk halaman utama customer

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Services\RouteService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly RouteService $routeService,
    ) {}

    public function index(): View
    {
        $popularRoutes = Route::where('is_active', true)
            ->withCount(['schedules' => function ($query) {
                $query->where('departure_date', '>=', now()->toDateString())
                    ->where('is_active', true);
            }])
            ->orderByDesc('schedules_count')
            ->limit(5)
            ->get();

        $cities = $this->routeService->getAllCities();

        return view('customer.home', compact('popularRoutes', 'cities'));
    }

    public function search(Request $request): View
    {
        $cities = $this->routeService->getAllCities();
        $schedules = collect();

        if ($request->filled(['origin', 'destination', 'date'])) {
            $schedules = $this->routeService->searchSchedules(
                $request->origin,
                $request->destination,
                \Carbon\Carbon::parse($request->date),
                $request->only(['travel_class'])
            );
        }

        return view('customer.search', compact('cities', 'schedules', 'request'));
    }
}

// End of file