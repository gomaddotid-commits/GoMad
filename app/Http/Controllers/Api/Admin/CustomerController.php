<?php
// File: app/Http/Controllers/Api/Admin/CustomerController.php
// Deskripsi: API Controller untuk manajemen customer oleh admin

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::where('role', 'customer');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar customer berhasil diambil.',
            'data' => $customers->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'is_active' => $customer->is_active,
                    'banned_at' => $customer->banned_at?->format('Y-m-d H:i:s'),
                    'banned_reason' => $customer->banned_reason,
                    'total_bookings' => $customer->customerBookings()->count(),
                    'created_at' => $customer->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        if ($user->role !== 'customer') {
            return response()->json([
                'success' => false,
                'message' => 'User bukan customer.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        $bookings = $user->customerBookings()
            ->with(['schedule.route', 'originStop', 'destinationStop'])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Detail customer berhasil diambil.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'banned_at' => $user->banned_at?->format('Y-m-d H:i:s'),
                'banned_reason' => $user->banned_reason,
                'total_bookings' => $user->customerBookings()->count(),
                'recent_bookings' => $bookings->map(function ($booking) {
                    return [
                        'id' => $booking->id,
                        'booking_code' => $booking->booking_code,
                        'route' => $booking->originStop->city_name . ' → ' . $booking->destinationStop->city_name,
                        'total_price' => (float) $booking->total_price,
                        'status' => $booking->status,
                        'created_at' => $booking->created_at->format('Y-m-d'),
                    ];
                }),
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            ],
            'meta' => null,
        ]);
    }

    public function toggleActive(User $user): JsonResponse
    {
        if ($user->role !== 'customer') {
            return response()->json([
                'success' => false,
                'message' => 'User bukan customer.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        $user->update(['is_active' => !$user->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Status customer berhasil diubah.',
            'data' => ['is_active' => $user->is_active],
            'meta' => null,
        ]);
    }

    public function ban(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($user->role !== 'customer') {
            return response()->json([
                'success' => false,
                'message' => 'User bukan customer.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        $user->update([
            'banned_at' => now(),
            'banned_reason' => $request->reason,
            'is_active' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer berhasil dibanned.',
            'data' => null,
            'meta' => null,
        ]);
    }

    public function unban(User $user): JsonResponse
    {
        if ($user->role !== 'customer') {
            return response()->json([
                'success' => false,
                'message' => 'User bukan customer.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        $user->update([
            'banned_at' => null,
            'banned_reason' => null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Customer berhasil di-unbanned.',
            'data' => null,
            'meta' => null,
        ]);
    }
}

// End of file