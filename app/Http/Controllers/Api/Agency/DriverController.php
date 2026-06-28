<?php
// File: app/Http/Controllers/Api/Agency/DriverController.php
// Deskripsi: API Controller untuk manajemen driver oleh agency

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateDriverRequest;
use App\Http\Resources\Api\DriverResource;
use App\Models\User;
use App\Services\DriverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function __construct(
        private readonly DriverService $driverService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        $drivers = $this->driverService->getAgencyDrivers($agency);

        return response()->json([
            'success' => true,
            'message' => 'Daftar driver berhasil diambil.',
            'data' => DriverResource::collection($drivers),
            'meta' => [
                'total' => $drivers->count(),
            ],
        ]);
    }

    public function store(CreateDriverRequest $request): JsonResponse
    {
        $agency = $request->user()->agency;

        $driver = $this->driverService->createDriver($agency, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Driver berhasil ditambahkan.',
            'data' => new DriverResource($driver),
            'meta' => null,
        ], 201);
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

        $stats = $this->driverService->getDriverStats($user);

        return response()->json([
            'success' => true,
            'message' => 'Detail driver berhasil diambil.',
            'data' => array_merge(
                (new DriverResource($user))->toArray(request()),
                ['stats' => $stats]
            ),
            'meta' => null,
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        if ($user->role !== 'driver') {
            return response()->json([
                'success' => false,
                'message' => 'User bukan driver.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
            'phone' => ['sometimes', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $driver = $this->driverService->updateDriver($user, $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Driver berhasil diupdate.',
            'data' => new DriverResource($driver),
            'meta' => null,
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->role !== 'driver') {
            return response()->json([
                'success' => false,
                'message' => 'User bukan driver.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        try {
            $this->driverService->deleteDriver($user);

            return response()->json([
                'success' => true,
                'message' => 'Driver berhasil dihapus.',
                'data' => null,
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 422);
        }
    }
}

// End of file