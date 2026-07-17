<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\RouteStop;
use App\Services\CloudinaryService;
use App\Services\RouteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class RouteController extends Controller
{
    public function __construct(
        private readonly RouteService $routeService,
        private readonly CloudinaryService $cloudinaryService,
    ) {}

    /**
     * Daftar rute
     */
    public function index(): View
    {
        $routes = Route::with(['stops.city', 'originCity', 'destinationCity'])
            ->withCount('stops')
            ->latest()
            ->get();

        return view('admin.routes.index', compact('routes'));
    }

    /**
     * Form tambah rute
     */
    public function create(): View
    {
        $cities = \App\Models\City::with('province')
            ->orderBy('name')
            ->get();

        return view('admin.routes.create', compact('cities'));
    }

    /**
     * Simpan rute baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'origin_city_code' => ['required', 'string', 'exists:indonesia_cities,code'],
            'destination_city_code' => ['required', 'string', 'different:origin_city_code', 'exists:indonesia_cities,code'],
            'route_name' => ['nullable', 'string', 'max:255'],
            'stop_city_codes' => ['nullable', 'array'],
            'stop_city_codes.*' => ['string', 'exists:indonesia_cities,code'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'cod_available' => ['nullable', 'boolean'],
            'cod_min_deposit' => ['nullable', 'numeric', 'min:0'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['in:midtrans,cash,cod'],
            'description' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $data = $request->except('photo');

            // Upload foto via Cloudinary
            if ($request->hasFile('photo')) {
                $result = $this->cloudinaryService->upload($request->file('photo'), 'routes');
                $data['photo'] = $result['url'];
            }

            // Proses payment_methods
            if ($request->has('payment_methods') && !empty($request->payment_methods)) {
                $data['payment_methods'] = implode(',', $request->payment_methods);
            }

            $route = $this->routeService->createRoute($data);

            Log::info('Route created via web', ['route_id' => $route->id, 'user_id' => auth()->id()]);

            return redirect()->route('admin.routes.index')
                ->with('success', 'Rute berhasil dibuat!');
        } catch (\Exception $e) {
            Log::error('Failed to create route', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal membuat rute: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Detail rute
     */
    public function show(Route $route): View
    {
        $route->load(['stops.city', 'originCity', 'destinationCity']);
        return view('admin.routes.show', compact('route'));
    }

    /**
     * Form edit rute
     */
    public function edit(Route $route): View
    {
        $route->load(['stops.city', 'originCity', 'destinationCity']);
        $cities = \App\Models\City::with('province')->orderBy('name')->get();
        
        // Dapatkan daftar city_code yang sudah jadi stop
        $existingStopCodes = $route->stops->pluck('city_code')->toArray();
        
        // Dapatkan kota yang tersedia untuk stop (di antara origin & destination)
        $availableStopCities = $this->routeService->getAvailableStops(
            $route->origin_city_code,
            $route->destination_city_code
        );
        
        // Filter: exclude kota yang sudah jadi stop
        $availableStopCities = $availableStopCities->filter(function($city) use ($existingStopCodes) {
            return !in_array($city->code, $existingStopCodes);
        })->values();

        return view('admin.routes.edit', compact('route', 'cities', 'availableStopCities'));
    }

    /**
     * Update rute
     */
    public function update(Request $request, Route $route): RedirectResponse
    {
        $validated = $request->validate([
            'route_name' => ['required', 'string', 'max:255'],
            'origin_city_code' => ['required', 'string', 'exists:indonesia_cities,code'],
            'destination_city_code' => ['required', 'string', 'different:origin_city_code', 'exists:indonesia_cities,code'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'cod_available' => ['nullable', 'boolean'],
            'cod_min_deposit' => ['nullable', 'numeric', 'min:0'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['in:midtrans,cash,cod'],
            'description' => ['nullable', 'string', 'max:500'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $data = $request->except(['_token', '_method', 'photo']);

            // Upload foto via Cloudinary
            if ($request->hasFile('photo')) {
                $result = $this->cloudinaryService->upload($request->file('photo'), 'routes');
                $data['photo'] = $result['url'];
            }

            // Proses payment_methods
            if ($request->has('payment_methods')) {
                $data['payment_methods'] = !empty($request->payment_methods)
                    ? implode(',', $request->payment_methods)
                    : null;
            } else {
                // Jika tidak ada checkbox yang dicentang, set null
                $data['payment_methods'] = null;
            }

            // Proses boolean fields
            $data['cod_available'] = $request->boolean('cod_available');
            $data['is_active'] = $request->boolean('is_active');

            Log::info('Updating route via web', [
                'route_id' => $route->id,
                'user_id' => auth()->id(),
                'data' => $data,
            ]);

            $route = $this->routeService->updateRoute($route, $data);

            return redirect()->route('admin.routes.index')
                ->with('success', 'Rute berhasil diupdate!');
        } catch (\Exception $e) {
            Log::error('Failed to update route', [
                'route_id' => $route->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()
                ->with('error', 'Gagal mengupdate rute: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Hapus rute
     */
    public function destroy(Route $route): RedirectResponse
    {
        $hasSchedules = $route->schedules()->exists();
        if ($hasSchedules) {
            return back()->with('error', 'Rute memiliki jadwal, tidak dapat dihapus. Nonaktifkan saja.');
        }

        try {
            $route->stops()->delete();
            $route->delete();

            Log::info('Route deleted', ['route_id' => $route->id, 'user_id' => auth()->id()]);

            return redirect()->route('admin.routes.index')
                ->with('success', 'Rute berhasil dihapus!');
        } catch (\Exception $e) {
            Log::error('Failed to delete route', ['route_id' => $route->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Gagal menghapus rute: ' . $e->getMessage());
        }
    }

    /**
     * Tambah stop ke rute
     */
    public function addStop(Request $request, Route $route): RedirectResponse
    {
        $request->validate([
            'city_code' => ['required', 'string', 'exists:indonesia_cities,code'],
        ]);

        try {
            $stop = $this->routeService->addStop($route, $request->all());

            Log::info('Stop added via web', [
                'route_id' => $route->id,
                'stop_id' => $stop->id,
                'user_id' => auth()->id(),
            ]);

            return back()->with('success', "Stop {$stop->city_name} berhasil ditambahkan!");
        } catch (\Exception $e) {
            Log::error('Failed to add stop', [
                'route_id' => $route->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal menambah stop: ' . $e->getMessage());
        }
    }

    /**
     * Hapus stop dari rute
     */
    public function removeStop(Route $route, RouteStop $stop): RedirectResponse
    {
        if ($stop->route_id !== $route->id) {
            return back()->with('error', 'Stop tidak ditemukan di rute ini.');
        }

        try {
            $cityName = $stop->city_name;
            $this->routeService->removeStop($stop);

            Log::info('Stop removed via web', [
                'route_id' => $route->id,
                'city_name' => $cityName,
                'user_id' => auth()->id(),
            ]);

            return back()->with('success', "Stop {$cityName} berhasil dihapus!");
        } catch (\Exception $e) {
            Log::error('Failed to remove stop', [
                'route_id' => $route->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal menghapus stop: ' . $e->getMessage());
        }
    }

    /**
     * API: Dapatkan kota yang tersedia untuk stop
     */
    public function availableStops(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'origin' => ['required', 'string', 'exists:indonesia_cities,code'],
            'destination' => ['required', 'string', 'exists:indonesia_cities,code'],
        ]);

        try {
            $stops = $this->routeService->getAvailableStops(
                $request->origin,
                $request->destination
            );

            return response()->json([
                'success' => true,
                'data' => $stops,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}