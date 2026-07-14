<?php

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Models\VehicleRentalSetting;
use App\Services\RentalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RentalController extends Controller
{
    public function __construct(
        private readonly RentalService $rentalService,
    ) {}

    /**
     * Halaman daftar rental (public)
     */
    public function index(Request $request): View
    {
        $query = VehicleRentalSetting::with(['vehicle.agency', 'vehicle.rentals' => function ($q) {
                $q->whereNotIn('status', ['cancelled'])
                  ->where('end_datetime', '>=', now());
            }])
            ->where('is_available_for_rental', true)
            ->whereHas('vehicle', function ($q) {
                $q->where('is_active', true);
            })
            ->whereHas('vehicle.agency', function ($q) {
                $q->where('is_verified', true);
            });

        // Filter tipe
        if ($request->type) {
            match ($request->type) {
                'self_drive' => $query->where('allow_self_drive', true),
                'with_driver' => $query->where('allow_with_driver', true),
                default => null,
            };
        }

        // Filter agency
        if ($request->agency_id) {
            $query->whereHas('vehicle', function ($q) use ($request) {
                $q->where('agency_id', $request->agency_id);
            });
        }

        // Filter tanggal (kendaraan yang tersedia di tanggal tertentu)
        if ($request->date) {
            $date = $request->date;
            $query->whereDoesntHave('vehicle.rentals', function ($q) use ($date) {
                $q->whereNotIn('status', ['cancelled'])
                  ->whereDate('start_datetime', '<=', $date)
                  ->whereDate('end_datetime', '>=', $date);
            });
        }

        $vehicles = $query->orderBy('created_at', 'desc')->paginate(12);

        $agencies = \App\Models\Agency::where('is_verified', true)
            ->orderBy('agency_name')
            ->get();

        return view('public-pages.rental', compact('vehicles', 'agencies'));
    }

    /**
     * Detail kendaraan rental (public)
     */
    public function show(VehicleRentalSetting $vehicleSetting): View
    {
        $vehicleSetting->load('vehicle.agency');
        
        // Dapatkan booked dates
        $rentalService = app(\App\Services\RentalService::class);
        $bookedDates = $rentalService->getBookedDates($vehicleSetting->vehicle_id);

        return view('public-pages.rental-show', compact('vehicleSetting', 'bookedDates'));
    }
}