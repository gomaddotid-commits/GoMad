<?php
// File: app/Http/Controllers/Api/Admin/ReportController.php
// Deskripsi: API Controller untuk laporan admin

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\PaymentAgent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        $currentStats = $this->getStats($thisMonth);
        $lastStats = $this->getStats($lastMonth);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil diambil.',
            'data' => [
                'current_month' => $currentStats,
                'last_month' => $lastStats,
            ],
            'meta' => null,
        ]);
    }

    public function revenue(Request $request): JsonResponse
    {
        $request->validate([
            'year' => ['nullable', 'integer', 'min:2024'],
        ]);

        $year = $request->year ?? now()->year;
        $revenueData = [];

        for ($m = 1; $m <= 12; $m++) {
            $startDate = Carbon::create($year, $m, 1)->startOfMonth();
            $endDate = Carbon::create($year, $m, 1)->endOfMonth();

            $revenue = Booking::where('status', 'completed')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->sum('total_price');

            $commission = Booking::where('status', 'completed')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->sum(DB::raw('total_price * 0.05'));

            $revenueData[] = [
                'month' => $startDate->format('M Y'),
                'revenue' => (float) $revenue,
                'commission' => (float) $commission,
                'bookings' => Booking::whereBetween('created_at', [$startDate, $endDate])->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Data revenue berhasil diambil.',
            'data' => $revenueData,
            'meta' => ['year' => $year],
        ]);
    }

    public function bookings(Request $request): JsonResponse
    {
        $statuses = ['pending', 'confirmed', 'paid', 'on_going', 'completed', 'cancelled'];
        $stats = [];

        foreach ($statuses as $status) {
            $stats[$status] = Booking::where('status', $status)->count();
        }

        $stats['total'] = array_sum($stats);

        return response()->json([
            'success' => true,
            'message' => 'Statistik booking berhasil diambil.',
            'data' => $stats,
            'meta' => null,
        ]);
    }

    public function agencies(Request $request): JsonResponse
    {
        $agencies = Agency::withCount(['bookings', 'schedules'])
            ->orderByDesc('total_bookings')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Laporan agency berhasil diambil.',
            'data' => $agencies->map(function ($agency) {
                return [
                    'id' => $agency->id,
                    'agency_name' => $agency->agency_name,
                    'is_verified' => $agency->is_verified,
                    'rating' => (float) $agency->rating,
                    'total_bookings' => $agency->total_bookings,
                    'total_schedules' => $agency->schedules_count,
                ];
            }),
            'meta' => null,
        ]);
    }

    private function getStats(Carbon $startDate): array
    {
        $endDate = $startDate->copy()->endOfMonth();

        return [
            'period' => $startDate->format('M Y'),
            'total_bookings' => Booking::whereBetween('created_at', [$startDate, $endDate])->count(),
            'completed_bookings' => Booking::where('status', 'completed')
                ->whereBetween('completed_at', [$startDate, $endDate])->count(),
            'total_revenue' => (float) Booking::where('status', 'completed')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->sum('total_price'),
            'platform_commission' => (float) Booking::where('status', 'completed')
                ->whereBetween('completed_at', [$startDate, $endDate])
                ->sum(DB::raw('total_price * 0.05')),
            'new_customers' => User::where('role', 'customer')
                ->whereBetween('created_at', [$startDate, $endDate])->count(),
            'new_agencies' => Agency::whereBetween('created_at', [$startDate, $endDate])->count(),
        ];
    }
}

// End of file