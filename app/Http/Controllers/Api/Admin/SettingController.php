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
}

// End of file