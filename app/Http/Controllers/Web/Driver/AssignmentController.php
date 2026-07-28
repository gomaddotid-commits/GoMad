<?php

namespace App\Http\Controllers\Web\Driver;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\Schedule;
use App\Services\DriverService;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly DriverService $driverService,
    ) {}

    public function index(): View
    {
        $driver = auth()->user();

        // ========== TRAVEL ==========
        $upcomingSchedules = $this->driverService->getDriverUpcomingSchedules($driver, 30);

        $recentCompletedSchedules = Schedule::with(['route', 'vehicle'])
            ->where('driver_id', $driver->id)
            ->whereNotNull('finished_at')
            ->orderBy('finished_at', 'desc')
            ->limit(10)
            ->get();

        // ========== RENTAL ==========
        $upcomingRentals = Rental::with(['vehicle', 'customer', 'agency'])
            ->where('driver_id', $driver->id)
            ->where('type', 'with_driver')
            ->whereIn('status', ['paid'])
            ->orderBy('start_datetime')
            ->get();

        $activeRentals = Rental::with(['vehicle', 'customer', 'agency'])
            ->where('driver_id', $driver->id)
            ->where('type', 'with_driver')
            ->where('status', 'active')
            ->orderBy('start_datetime')
            ->get();

        $recentCompletedRentals = Rental::with(['vehicle', 'customer'])
            ->where('driver_id', $driver->id)
            ->where('type', 'with_driver')
            ->whereIn('status', ['returned', 'completed'])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        return view('driver.assignments', compact(
            'upcomingSchedules',
            'recentCompletedSchedules',
            'upcomingRentals',
            'activeRentals',
            'recentCompletedRentals'
        ));
    }
}