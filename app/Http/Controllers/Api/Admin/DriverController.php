<?php
// File: app/Http/Controllers/Api/Admin/DriverController.php
// Deskripsi: API Controller untuk monitoring driver oleh admin

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::where('role', 'driver')
            ->with('driverAgency');

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->agency_id) {
            $query->where('agency_id', $request->agency_id);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            });
        }

        $drivers = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar driver berhasil diambil.',
            'data' => $drivers->map(function ($driver) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'email' => $driver->email,
                    'phone' => $driver->phone,
                    'agency' => $driver->driverAgency ? [
                        'id' => $driver->driverAgency->id,
                        'name' => $driver->driverAgency->agency_name,
                    ] : null,
                    'is_active' => $driver->is_active,
                    'total_trips' => $driver->driverSchedules()->count(),
                    'created_at' => $driver->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'current_page' => $drivers->currentPage(),
                'last_page' => $drivers->lastPage(),
                'total' => $drivers->total(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        if ($user->role !== 'driver') {
            return response()->json([
                'success' => false,
                'message' => 'User bukan driver.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        $user->load(['driverAgency', 'driverSchedules' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return response()->json([
            'success' => true,
            'message' => 'Detail driver berhasil diambil.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'agency' => $user->driverAgency ? [
                    'id' => $user->driverAgency->id,
                    'name' => $user->driverAgency->agency_name,
                ] : null,
                'is_active' => $user->is_active,
                'total_trips' => $user->driverSchedules()->count(),
                'recent_trips' => $user->driverSchedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'route_name' => $schedule->route->route_name,
                        'departure_date' => $schedule->departure_date->format('Y-m-d'),
                        'travel_class' => $schedule->travel_class,
                    ];
                }),
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            ],
            'meta' => null,
        ]);
    }
}

// End of file