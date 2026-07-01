<?php
// File: app/Http/Controllers/Web/Admin/RouteController.php
// Deskripsi: Web Controller untuk manajemen rute admin (FULL dengan foto)

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\RouteStop;
use App\Services\RouteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class RouteController extends Controller
{
    public function __construct(
        private readonly RouteService $routeService,
    ) {}

    public function index(): View
    {
        $routes = Route::withCount('stops')->latest()->get();
        return view('admin.routes.index', compact('routes'));
    }

    public function create(): View
    {
        return view('admin.routes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'route_name' => ['required', 'string', 'max:100'],
            'origin_city' => ['required', 'string', 'max:100'],
            'destination_city' => ['required', 'string', 'max:100', 'different:origin_city'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'estimated_duration' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'cod_min_deposit' => ['nullable', 'numeric', 'min:0'],
            'cod_available' => ['nullable', 'boolean'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['in:midtrans,cash,cod'],
            'description' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'stops' => ['required', 'array', 'min:2'],
            'stops.*.city_name' => ['required', 'string', 'max:100'],
            'stops.*.stop_order' => ['nullable', 'integer', 'min:1'],
            'stops.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'stops.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'stops.*.distance_from_origin' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data = $request->except('photo');
        
        // Upload foto
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('routes', 'public');
        }

        // Proses payment_methods
        if ($request->has('payment_methods') && !empty($request->payment_methods)) {
            $data['payment_methods'] = implode(',', $request->payment_methods);
        } else {
            $data['payment_methods'] = null;
        }

        $this->routeService->createRoute($data);

        return redirect()->route('admin.routes.index')
            ->with('success', 'Rute berhasil dibuat!');
    }

    public function show(Route $route): View
    {
        $route->load('stops');
        return view('admin.routes.show', compact('route'));
    }

    public function edit(Route $route): View
    {
        $route->load('stops');
        return view('admin.routes.edit', compact('route'));
    }

    public function update(Request $request, Route $route): RedirectResponse
    {
        $request->validate([
            'route_name' => ['required', 'string', 'max:100'],
            'origin_city' => ['required', 'string', 'max:100'],
            'destination_city' => ['required', 'string', 'max:100'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'estimated_duration' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'cod_min_deposit' => ['nullable', 'numeric', 'min:0'],
            'cod_available' => ['nullable', 'boolean'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['in:midtrans,cash,cod'],
        ]);

        $data = $request->except('photo');

        // Upload foto baru jika ada
        if ($request->hasFile('photo')) {
            // Hapus foto lama
            if ($route->photo && Storage::disk('public')->exists($route->photo)) {
                Storage::disk('public')->delete($route->photo);
            }
            $data['photo'] = $request->file('photo')->store('routes', 'public');
        }

        if ($request->has('payment_methods')) {
            $data['payment_methods'] = !empty($request->payment_methods) 
                ? implode(',', $request->payment_methods) 
                : null;
        }

        $this->routeService->updateRoute($route, $data);

        return redirect()->route('admin.routes.index')
            ->with('success', 'Rute berhasil diupdate!');
    }

    public function destroy(Route $route): RedirectResponse
    {
        $hasSchedules = $route->schedules()->exists();
        if ($hasSchedules) {
            return back()->with('error', 'Rute memiliki jadwal, tidak dapat dihapus. Nonaktifkan saja.');
        }

        // Hapus foto
        if ($route->photo && Storage::disk('public')->exists($route->photo)) {
            Storage::disk('public')->delete($route->photo);
        }

        $route->stops()->delete();
        $route->delete();

        return redirect()->route('admin.routes.index')
            ->with('success', 'Rute berhasil dihapus!');
    }

    public function addStop(Request $request, Route $route): RedirectResponse
    {
        $request->validate([
            'city_name' => ['required', 'string', 'max:100'],
            'stop_order' => ['nullable', 'integer', 'min:1'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'distance_from_origin' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->routeService->addStop($route, $request->all());

        return back()->with('success', 'Stop berhasil ditambahkan!');
    }

    public function removeStop(Route $route, RouteStop $stop): RedirectResponse
    {
        if ($stop->route_id !== $route->id) {
            return back()->with('error', 'Stop tidak ditemukan di rute ini.');
        }

        try {
            $this->routeService->removeStop($stop);
            return back()->with('success', 'Stop berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

// End of file