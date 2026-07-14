<?php

namespace App\Http\Controllers\Web\Agency;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\Vehicle;
use App\Services\RentalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RentalController extends Controller
{
    public function __construct(
        private readonly RentalService $rentalService,
    ) {}

    /**
     * Dashboard rental agency
     */
    public function dashboard(): View
    {
        $agency = auth()->user()->agency;
        $rentals = $this->rentalService->getAgencyRentals($agency);
        
        $stats = [
            'total' => $rentals->count(),
            'active' => $rentals->where('status', 'active')->count(),
            'pending' => $rentals->where('status', 'pending')->count(),
            'completed' => $rentals->where('status', 'completed')->count(),
            'total_revenue' => $rentals->where('status', 'completed')->sum('total_price'),
        ];

        $recentRentals = $rentals->take(10);

        return view('agency.rental.dashboard', compact('stats', 'recentRentals'));
    }

    /**
     * Daftar rental agency
     */
    public function index(Request $request): View
    {
        $agency = auth()->user()->agency;
        $rentals = $this->rentalService->getAgencyRentals($agency, $request->status);

        return view('agency.rental.index', compact('rentals'));
    }

    /**
     * Detail rental
     */
    public function show(Rental $rental): View
    {
        $rental->load(['vehicle.rentalSetting', 'customer', 'payment']);
        return view('agency.rental.show', compact('rental'));
    }

    /**
     * Halaman setup kendaraan rental
     */
    public function vehicleSetup(Vehicle $vehicle): View
    {
        $vehicle->load('rentalSetting');
        return view('agency.rental.vehicle-setup', compact('vehicle'));
    }

    /**
     * Simpan setup kendaraan rental
     */
    public function saveVehicleSetup(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $agency = auth()->user()->agency;

        if ($vehicle->agency_id !== $agency->id) {
            abort(403);
        }

        $request->validate([
            'description' => ['nullable', 'string', 'max:2000'],
            'specifications' => ['nullable', 'array'],
            'price_per_hour' => ['nullable', 'numeric', 'min:0'],
            'price_per_day' => ['nullable', 'numeric', 'min:0'],
            'allow_self_drive' => ['nullable', 'boolean'],
            'allow_with_driver' => ['nullable', 'boolean'],
            'driver_fee_per_hour' => ['nullable', 'numeric', 'min:0'],
            'driver_fee_per_day' => ['nullable', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'requirements' => ['nullable', 'array'],
            // 👇 TAMBAHKAN VALIDASI
            'use_system_terms' => ['nullable', 'boolean'],
            'use_system_refund' => ['nullable', 'boolean'],
            'terms_conditions' => ['nullable', 'array'],
            'terms_conditions.*' => ['nullable', 'string'],
            'refund_policy' => ['nullable', 'array'],
            'refund_policy.*' => ['nullable', 'string'],
        ]);

        try {
            $data = $request->all();
            
            // Set boolean
            $data['use_system_terms'] = $request->boolean('use_system_terms');
            $data['use_system_refund'] = $request->boolean('use_system_refund');
            
            // Filter terms & refund (hapus item kosong)
            if (isset($data['terms_conditions']) && is_array($data['terms_conditions'])) {
                $data['terms_conditions'] = array_values(array_filter($data['terms_conditions'], function($item) {
                    return !empty(trim($item));
                }));
            }
            
            if (isset($data['refund_policy']) && is_array($data['refund_policy'])) {
                $data['refund_policy'] = array_values(array_filter($data['refund_policy'], function($item) {
                    return !empty(trim($item));
                }));
            }

            $this->rentalService->setupVehicleForRental($vehicle, $data);

            return redirect()->route('agency.rental.vehicles')
                ->with('success', 'Kendaraan berhasil disetup untuk rental!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Daftar kendaraan rental
     */
    public function vehicles(): View
    {
        $agency = auth()->user()->agency;
        $vehicles = Vehicle::with('rentalSetting')
            ->where('agency_id', $agency->id)
            ->where('is_active', true)
            ->get();

        return view('agency.rental.vehicles', compact('vehicles'));
    }

    /**
     * Verifikasi pengambilan
     */
    public function verifyPickup(Rental $rental): RedirectResponse
    {
        try {
            $this->rentalService->verifyPickup($rental);
            return back()->with('success', 'Pengambilan mobil berhasil diverifikasi.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Verifikasi pengembalian
     */
    public function verifyReturn(Rental $rental): RedirectResponse
    {
        try {
            $this->rentalService->verifyReturn($rental);
            return back()->with('success', 'Pengembalian mobil berhasil diverifikasi.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Selesaikan rental
     */
    public function complete(Rental $rental): RedirectResponse
    {
        try {
            $this->rentalService->completeRental($rental);
            return back()->with('success', 'Rental berhasil diselesaikan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Assign supir ke rental
     */
    public function assignDriver(Request $request, Rental $rental): RedirectResponse
    {
        $agency = auth()->user()->agency;

        if ($rental->agency_id !== $agency->id) {
            abort(403);
        }

        $request->validate([
            'driver_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            $driver = \App\Models\User::where('id', $request->driver_id)
                ->where('role', 'driver')
                ->where('agency_id', $agency->id)
                ->firstOrFail();

            $this->rentalService->assignDriver($rental, $driver);

            return back()->with('success', 'Supir berhasil ditugaskan!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}