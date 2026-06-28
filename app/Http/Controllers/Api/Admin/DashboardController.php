<?php
// File: app/Http/Controllers/Api/Admin/DashboardController.php
// Deskripsi: API Controller untuk dashboard admin

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\PaymentAgent;
use App\Models\User;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $thisMonth = Carbon::now()->startOfMonth();
        $today = Carbon::today();

        $stats = [
            'total_customers' => User::where('role', 'customer')->count(),
            'total_agencies' => Agency::count(),
            'total_verified_agencies' => Agency::where('is_verified', true)->count(),
            'total_drivers' => User::where('role', 'driver')->count(),
            'total_payment_agents' => PaymentAgent::where('is_verified', true)->count(),
            'total_bookings' => Booking::whereMonth('created_at', $thisMonth->month)->count(),
            'total_revenue' => (float) Booking::where('status', 'completed')
                ->whereMonth('completed_at', $thisMonth->month)
                ->sum('total_price'),
            'pending_verifications' => Agency::where('is_verified', false)
                ->whereHas('verifications', function ($q) {
                    $q->where('status', 'pending');
                })->count(),
            'pending_withdrawals' => Withdrawal::where('status', 'pending')
                ->where('amount', '>=', 5000000)
                ->count(),
        ];

        $recentBookings = Booking::with(['schedule.agency', 'customer'])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data dashboard berhasil diambil.',
            'data' => [
                'stats' => $stats,
                'recent_bookings' => $recentBookings->map(function ($booking) {
                    return [
                        'id' => $booking->id,
                        'booking_code' => $booking->booking_code,
                        'customer_name' => $booking->customer->name,
                        'agency_name' => $booking->schedule->agency->agency_name,
                        'total_price' => (float) $booking->total_price,
                        'status' => $booking->status,
                        'created_at' => $booking->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
            ],
            'meta' => null,
        ]);
    }

    public function stats(): JsonResponse
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            $months[] = [
                'month' => $date->format('M Y'),
                'bookings' => Booking::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
                'revenue' => (float) Booking::where('status', 'completed')
                    ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])
                    ->sum('total_price'),
                'new_customers' => User::where('role', 'customer')
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Statistik berhasil diambil.',
            'data' => $months,
            'meta' => null,
        ]);
    }
}

// End of file