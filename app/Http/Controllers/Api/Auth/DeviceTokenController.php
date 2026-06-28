<?php
// File: app/Http/Controllers/Api/Auth/DeviceTokenController.php
// Deskripsi: API Controller untuk registrasi device token FCM

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\DeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function __construct(
        private readonly DeviceService $deviceService,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'device_token' => ['required', 'string'],
            'platform' => ['required', 'in:ios,android'],
        ]);

        $device = $this->deviceService->registerDevice(
            $request->user(),
            $request->device_token,
            $request->platform
        );

        return response()->json([
            'success' => true,
            'message' => 'Device token berhasil diregistrasi.',
            'data' => [
                'id' => $device->id,
                'platform' => $device->platform,
                'is_active' => $device->is_active,
            ],
            'meta' => null,
        ]);
    }

    public function unregister(Request $request): JsonResponse
    {
        $request->validate([
            'device_token' => ['required', 'string'],
        ]);

        $this->deviceService->unregisterDevice($request->device_token);

        return response()->json([
            'success' => true,
            'message' => 'Device token berhasil dihapus.',
            'data' => null,
            'meta' => null,
        ]);
    }
}

// End of file