<?php
// File: app/Http/Controllers/Api/Agency/ReportController.php
// Deskripsi: API Controller untuk laporan agency

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        $currentMonthStats = $this->getMonthlyStats($agency->id, $thisMonth);
        $lastMonthStats = $this->getMonthlyStats($agency->id, $lastMonth);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil diambil.',
            'data' => [
                'current_month' => $currentMonthStats,
                'last_month' => $lastMonthStats,
                'comparison' => [
                    'revenue_change' => $this->calculateChange(
                        $lastMonthStats['total_revenue'],
                        $currentMonthStats['total_revenue']
                    ),
                    'bookings_change' => $this->calculateChange(
                        $lastMonthStats['total_bookings'],
                        $currentMonthStats['total_bookings']
                    ),
                ],
            ],
            'meta' => null,
        ]);
    }

    public function revenue(Request $request): JsonResponse
    {
        $request->validate([
            'period' => ['nullable', 'in:daily,monthly,yearly'],
            'year' => ['nullable', 'integer', 'min:2024'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $agency = $request->user()->agency;
        $year = $request->year ?? now()->year;
        $month = $request->month ?? now()->month;
        $period = $request->period ?? 'monthly';

        $revenueData = [];

        if ($period === 'monthly') {
            for ($m = 1; $m <= 12; $m++) {
                $startDate = Carbon::create($year, $m, 1)->startOfMonth();
                $endDate = Carbon::create($year, $m, 1)->endOfMonth();

                $revenue = Booking::whereHas('schedule', function ($q) use ($agency) {
                    $q->where('agency_id', $agency->id);
                })
                ->where('status', 'completed')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->sum('total_price');

                $revenueData[] = [
                    'month' => $startDate->format('M Y'),
                    'revenue' => (float) $revenue,
                ];
            }
        } elseif ($period === 'daily') {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            $revenues = Booking::whereHas('schedule', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(completed_at) as date'), DB::raw('SUM(total_price) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            foreach ($revenues as $rev) {
                $revenueData[] = [
                    'date' => $rev->date,
                    'revenue' => (float) $rev->total,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Data revenue berhasil diambil.',
            'data' => [
                'period' => $period,
                'year' => $year,
                'month' => $month,
                'data' => $revenueData,
            ],
            'meta' => null,
        ]);
    }

    public function bookings(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        $bookingStats = [
            'total' => Booking::whereHas('schedule', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })->count(),
            'completed' => Booking::whereHas('schedule', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })->where('status', 'completed')->count(),
            'cancelled' => Booking::whereHas('schedule', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })->where('status', 'cancelled')->count(),
            'pending' => Booking::whereHas('schedule', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })->whereIn('status', ['pending', 'confirmed'])->count(),
            'on_going' => Booking::whereHas('schedule', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })->where('status', 'on_going')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Statistik booking berhasil diambil.',
            'data' => $bookingStats,
            'meta' => null,
        ]);
    }

    private function getMonthlyStats(int $agencyId, Carbon $startDate): array
    {
        $endDate = $startDate->copy()->endOfMonth();

        $totalBookings = Booking::whereHas('schedule', function ($q) use ($agencyId) {
            $q->where('agency_id', $agencyId);
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->count();

        $completedBookings = Booking::whereHas('schedule', function ($q) use ($agencyId) {
            $q->where('agency_id', $agencyId);
        })
        ->where('status', 'completed')
        ->whereBetween('completed_at', [$startDate, $endDate])
        ->count();

        $totalRevenue = Booking::whereHas('schedule', function ($q) use ($agencyId) {
            $q->where('agency_id', $agencyId);
        })
        ->where('status', 'completed')
        ->whereBetween('completed_at', [$startDate, $endDate])
        ->sum('total_price');

        $totalPassengers = Booking::whereHas('schedule', function ($q) use ($agencyId) {
            $q->where('agency_id', $agencyId);
        })
        ->where('status', 'completed')
        ->whereBetween('completed_at', [$startDate, $endDate])
        ->sum('total_passengers');

        $totalSchedules = Schedule::where('agency_id', $agencyId)
            ->whereBetween('departure_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->count();

        return [
            'period' => $startDate->format('M Y'),
            'total_bookings' => $totalBookings,
            'completed_bookings' => $completedBookings,
            'total_revenue' => (float) $totalRevenue,
            'total_revenue_formatted' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
            'total_passengers' => $totalPassengers,
            'total_schedules' => $totalSchedules,
        ];
    }

    private function calculateChange(float $oldValue, float $newValue): array
    {
        if ($oldValue == 0) {
            return [
                'percentage' => $newValue > 0 ? 100 : 0,
                'direction' => $newValue > 0 ? 'up' : 'same',
            ];
        }

        $change = (($newValue - $oldValue) / $oldValue) * 100;

        return [
            'percentage' => round(abs($change), 2),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'same'),
        ];
    }
}

// End of file