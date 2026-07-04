<?php
// File: app/Http/Controllers/Web/Agency/VehicleController.php
// Deskripsi: Web Controller untuk manajemen kendaraan agency (FULL)

namespace App\Http\Controllers\Web\Agency;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class VehicleController extends Controller
{
    public function index(): View
    {
        $vehicles = Vehicle::where('agency_id', auth()->user()->agency->id)
            ->latest()
            ->get();
        return view('agency.vehicles.index', compact('vehicles'));
    }

    public function create(): View
    {
        return view('agency.vehicles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles,plate_number'],
            'brand' => ['required', 'string', 'max:50'],
            'model' => ['required', 'string', 'max:50'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
            'capacity' => ['required', 'integer', 'min:4', 'max:20'],
            'type' => ['required', 'in:economy,premium'],
            'vehicle_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $data = $request->only(['plate_number', 'brand', 'model', 'year', 'capacity', 'type']);
        $data['agency_id'] = auth()->user()->agency->id;
        $data['is_active'] = true;

        // Upload foto kendaraan
        if ($request->hasFile('vehicle_image')) {
            $path = $request->file('vehicle_image')->store('vehicles', 'public');
            $data['vehicle_image'] = $path;
        }

        Vehicle::create($data);
        auth()->user()->agency->increment('fleet_size');

        return redirect()->route('agency.vehicles.index')
            ->with('success', 'Kendaraan berhasil ditambahkan!');
    }

    public function edit(Vehicle $vehicle): View
    {
        if ($vehicle->agency_id !== auth()->user()->agency->id) abort(403);
        return view('agency.vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        if ($vehicle->agency_id !== auth()->user()->agency->id) abort(403);

        $request->validate([
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles,plate_number,' . $vehicle->id],
            'brand' => ['required', 'string', 'max:50'],
            'model' => ['required', 'string', 'max:50'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
            'capacity' => ['required', 'integer', 'min:4', 'max:20'],
            'type' => ['required', 'in:economy,premium'],
            'vehicle_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $data = $request->only(['plate_number', 'brand', 'model', 'year', 'capacity', 'type']);

        // Upload foto baru jika ada
        if ($request->hasFile('vehicle_image')) {
            // Hapus foto lama
            if ($vehicle->vehicle_image && Storage::disk('public')->exists($vehicle->vehicle_image)) {
                Storage::disk('public')->delete($vehicle->vehicle_image);
            }
            $data['vehicle_image'] = $request->file('vehicle_image')->store('vehicles', 'public');
        }

        $vehicle->update($data);

        return redirect()->route('agency.vehicles.index')
            ->with('success', 'Kendaraan berhasil diupdate!');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        if ($vehicle->agency_id !== auth()->user()->agency->id) abort(403);

        $hasActiveSchedules = $vehicle->schedules()
            ->where('departure_date', '>=', now()->toDateString())
            ->where('is_active', true)
            ->exists();

        if ($hasActiveSchedules) {
            return back()->with('error', 'Kendaraan masih memiliki jadwal aktif.');
        }

        // Hapus foto
        if ($vehicle->vehicle_image && Storage::disk('public')->exists($vehicle->vehicle_image)) {
            Storage::disk('public')->delete($vehicle->vehicle_image);
        }

        $vehicle->update(['is_active' => false]);
        $vehicle->delete();
        auth()->user()->agency->decrement('fleet_size');

        return back()->with('success', 'Kendaraan berhasil dihapus.');
    }
}

// End of file