<?php

namespace App\Http\Controllers\Web\Agency;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use App\Models\Vehicle;
use App\Models\VehicleRentalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RentalPromoController extends Controller
{
    /**
     * Halaman promo rental — daftar promo + attach ke kendaraan
     */
    public function index(): View
    {
        $agency = auth()->user()->agency;
        
        // Promo yang tersedia untuk rental (module = rental atau all)
        $promos = Promo::active()
            ->where(function ($q) {
                $q->where('module', 'rental')
                  ->orWhere('module', 'all');
            })
            ->latest()
            ->get();
        
        // Kendaraan rental milik agency ini
        $vehicles = Vehicle::with('rentalSetting', 'rentalPromos')
            ->where('agency_id', $agency->id)
            ->where('is_active', true)
            ->whereHas('rentalSetting', function ($q) {
                $q->where('is_available_for_rental', true);
            })
            ->get();

        return view('agency.rental.promos', compact('promos', 'vehicles'));
    }

    /**
     * Attach promo ke kendaraan rental
     */
    public function attach(Request $request): RedirectResponse
    {
        $request->validate([
            'promo_id' => ['required', 'integer', 'exists:promos,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
        ]);

        $agency = auth()->user()->agency;
        $vehicle = Vehicle::where('agency_id', $agency->id)
            ->findOrFail($request->vehicle_id);
        
        $promo = Promo::findOrFail($request->promo_id);
        
        // Validasi promo harus untuk rental atau all
        if (!in_array($promo->module, ['rental', 'all'])) {
            return back()->with('error', 'Promo ini bukan untuk modul Rental.');
        }

        // Attach
        $vehicle->rentalPromos()->syncWithoutDetaching([$promo->id]);

        return back()->with('success', "Promo \"{$promo->name}\" berhasil dipasang ke {$vehicle->plate_number}!");
    }

    /**
     * Detach promo dari kendaraan rental
     */
    public function detach(Vehicle $vehicle, Promo $promo): RedirectResponse
    {
        $agency = auth()->user()->agency;
        
        if ($vehicle->agency_id !== $agency->id) {
            abort(403);
        }

        $vehicle->rentalPromos()->detach($promo->id);

        return back()->with('success', 'Promo berhasil dilepas dari kendaraan.');
    }
}