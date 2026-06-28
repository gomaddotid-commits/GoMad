<?php
// File: app/Http/Controllers/Api/Agency/DashboardController.php
// Deskripsi: API Controller untuk dashboard agency

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Schedule;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $todaySchedules = Schedule::where('agency_id', $agency->id)
            ->where('departure_date', $today->toDateString())
            ->where('is_active', true)
            ->count();

        $todayBookings = Booking::whereHas('schedule', function ($q) use ($agency, $today) {
            $q->where('agency_id', $agency->id)
                ->where('departure_date', $today->toDateString());
        })->whereNotIn('status', ['cancelled'])->count();

        $monthBookings = Booking::whereHas('schedule', function ($q) use ($agency, $thisMonth) {
            $q->where('agency_id', $agency->id)
                ->where('departure_date', '>=', $thisMonth->toDateString());
        })->whereNotIn('status', ['cancelled'])->count();

        $monthRevenue = Booking::whereHas('schedule', function ($q) use ($agency, $thisMonth) {
            $q->where('agency_id', $agency->id)
                ->where('departure_date', '>=', $thisMonth->toDateString());
        })->where('status', 'completed')->sum('total_price');

        $walletBalance = $this->walletService->getBalance($agency);

        $activeVehicles = $agency->vehicles()->where('is_active', true)->count();
        $activeDrivers = $agency->drivers()->where('is_active', true)->count();

        $recentBookings = Booking::whereHas('schedule', function ($q) use ($agency) {
            $q->where('agency_id', $agency->id);
        })->with(['schedule.route', 'customer', 'originStop', 'destinationStop'])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data dashboard berhasil diambil.',
            'data' => [
                'stats' => [
                    'today_schedules' => $todaySchedules,
                    'today_bookings' => $todayBookings,
                    'month_bookings' => $monthBookings,
                    'month_revenue' => (float) $monthRevenue,
                    'month_revenue_formatted' => 'Rp ' . number_format($monthRevenue, 0, ',', '.'),
                    'active_vehicles' => $activeVehicles,
                    'active_drivers' => $activeDrivers,
                ],
                'wallet' => $walletBalance,
                'recent_bookings' => \App\Http\Resources\Api\BookingResource::collection($recentBookings),
            ],
            'meta' => null,
        ]);
    }
}

// End of file