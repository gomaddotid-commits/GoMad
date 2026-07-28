<?php

namespace App\Http\Controllers\Web\Driver;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $driver = auth()->user();
        $today = Carbon::today();

        // ========== TRAVEL ==========
        $todaySchedule = Schedule::with([
                'route.stops',
                'vehicle',
                'agency',
                'bookings' => function ($q) {
                    $q->whereNotIn('status', ['cancelled'])
                        ->with(['originStop', 'destinationStop', 'passengers', 'customer', 'payment']);
                },
            ])
            ->where('driver_id', $driver->id)
            ->where('departure_date', $today->toDateString())
            ->where('is_active', true)
            ->first();

        // ========== RENTAL ==========
        $activeRental = Rental::with(['vehicle', 'customer', 'agency'])
            ->where('driver_id', $driver->id)
            ->where('type', 'with_driver')
            ->whereIn('status', ['paid', 'active'])
            ->where(function ($q) use ($today) {
                $q->whereDate('start_datetime', $today)
                    ->orWhere('status', 'active');
            })
            ->orderBy('start_datetime')
            ->first();

        // ========== STATISTIK ==========
        $totalTrips = Schedule::where('driver_id', $driver->id)->count();
        $completedTrips = Schedule::where('driver_id', $driver->id)
            ->whereNotNull('finished_at')
            ->count();
        $totalPassengers = \App\Models\Booking::whereHas('schedule', function ($q) use ($driver) {
                $q->where('driver_id', $driver->id);
            })
            ->where('status', 'completed')
            ->sum('total_passengers');

        $totalRentals = Rental::where('driver_id', $driver->id)
            ->where('type', 'with_driver')
            ->count();
        $completedRentals = Rental::where('driver_id', $driver->id)
            ->where('type', 'with_driver')
            ->whereIn('status', ['completed'])
            ->count();

        $averageRating = \App\Models\Review::whereHas('booking.schedule', function ($q) use ($driver) {
                $q->where('driver_id', $driver->id);
            })->avg('rating') ?? 0;

        return view('driver.dashboard', compact(
            'todaySchedule',
            'activeRental',
            'totalTrips',
            'completedTrips',
            'totalPassengers',
            'totalRentals',
            'completedRentals',
            'averageRating'
        ));
    }
}