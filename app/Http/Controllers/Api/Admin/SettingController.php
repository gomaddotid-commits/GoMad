<?php
// File: app/Http/Controllers/Api/Admin/SettingController.php
// Deskripsi: API Controller untuk pengaturan platform oleh admin

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = PlatformSetting::getAllSettings();

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan berhasil diambil.',
            'data' => $settings,
            'meta' => null,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:100'],
            'settings.*.value' => ['required'],
        ]);

        foreach ($request->settings as $setting) {
            PlatformSetting::setValue(
                $setting['key'],
                $setting['value'],
                $request->user()->id
            );
        }

        PlatformSetting::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan berhasil diupdate.',
            'data' => PlatformSetting::getAllSettings(),
            'meta' => null,
        ]);
    }

    /**
     * Test kirim WhatsApp
     */
    public function testWhatsApp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:15'],
            'message' => ['required', 'string', 'max:500'],
        ]);

        try {
            $notificationService = app(\App\Services\NotificationService::class);
            $notificationService->sendWhatsApp($request->phone, $request->message);

            return response()->json([
                'success' => true,
                'message' => 'Pesan test berhasil dikirim ke ' . $request->phone,
                'data' => [
                    'phone' => $request->phone,
                    'driver' => config('gomad.whatsapp.driver', 'log'),
                    'sent_at' => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim: ' . $e->getMessage(),
            ], 500);
        }
    }
}

// End of file