<?php

namespace App\Http\Controllers\Web\Driver;

use App\Http\Controllers\Controller;
use App\Models\Rental;
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
     * Daftar tugas rental driver (hanya with_driver, status paid/active)
     */
    public function index(): View
    {
        $driver = auth()->user();

        $rentals = Rental::with([
                'vehicle.rentalSetting',
                'customer',
                'agency',
            ])
            ->where('driver_id', $driver->id)
            ->where('type', 'with_driver')
            ->whereIn('status', ['paid', 'active'])
            ->orderBy('start_datetime')
            ->get();

        // Kelompokkan: hari ini & mendatang
        $todayRentals = $rentals->filter(function ($r) {
            return $r->start_datetime->isToday();
        });

        $upcomingRentals = $rentals->filter(function ($r) {
            return $r->start_datetime->isFuture();
        });

        $pastRentals = Rental::with(['vehicle', 'customer'])
            ->where('driver_id', $driver->id)
            ->where('type', 'with_driver')
            ->whereIn('status', ['returned', 'completed'])
            ->orderBy('start_datetime', 'desc')
            ->limit(10)
            ->get();

        return view('driver.rental.index', compact(
            'todayRentals',
            'upcomingRentals',
            'pastRentals'
        ));
    }

    /**
     * Detail satu tugas rental
     */
    public function show(Rental $rental): View
    {
        $driver = auth()->user();

        if ($rental->driver_id !== $driver->id) {
            abort(403);
        }

        $rental->load([
            'vehicle.rentalSetting',
            'customer',
            'agency',
        ]);

        // Siapkan alamat & maps URL
        $pickupAddress = $rental->pickup_address;
        $pickupMapsUrl = $rental->pickup_maps_link
            ?: 'https://www.google.com/maps/search/?api=1&query=' . urlencode($pickupAddress);
        $pickupNavUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($pickupAddress);

        $destinationAddress = $rental->destination_address;
        $destMapsUrl = $rental->destination_maps_link
            ?: ($destinationAddress
                ? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($destinationAddress)
                : null);
        $destNavUrl = $destinationAddress
            ? 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($destinationAddress)
            : null;

        return view('driver.rental.show', compact(
            'rental',
            'pickupAddress',
            'pickupMapsUrl',
            'pickupNavUrl',
            'destinationAddress',
            'destMapsUrl',
            'destNavUrl'
        ));
    }

    /**
     * Driver verifikasi pengambilan mobil (jemput customer)
     */
    public function verifyPickup(Rental $rental): RedirectResponse
    {
        $driver = auth()->user();

        if ($rental->driver_id !== $driver->id) {
            abort(403);
        }

        if ($rental->type !== 'with_driver') {
            return back()->with('error', 'Hanya rental dengan supir yang bisa diverifikasi oleh driver.');
        }

        if ($rental->status !== 'paid') {
            return back()->with('error', 'Rental harus dalam status Siap Diambil.');
        }

        try {
            $this->rentalService->verifyPickup($rental);

            return back()->with('success', '✅ Pengambilan mobil berhasil diverifikasi. Customer telah dijemput.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    /**
     * Driver verifikasi pengembalian mobil (customer selesai)
     */
    public function verifyReturn(Rental $rental): RedirectResponse
    {
        $driver = auth()->user();

        if ($rental->driver_id !== $driver->id) {
            abort(403);
        }

        if ($rental->type !== 'with_driver') {
            return back()->with('error', 'Hanya rental dengan supir yang bisa diverifikasi oleh driver.');
        }

        if ($rental->status !== 'active') {
            return back()->with('error', 'Rental harus dalam status Sedang Disewa.');
        }

        try {
            $this->rentalService->verifyReturn($rental);

            return back()->with('success', '🔄 Pengembalian mobil berhasil diverifikasi. Menunggu verifikasi agency.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}