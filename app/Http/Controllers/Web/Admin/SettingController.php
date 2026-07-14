<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Halaman pengaturan
     */
    public function index(): View
    {
        $settings = PlatformSetting::getAllSettings();
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update pengaturan
     */
    public function update(Request $request): RedirectResponse
    {
        // Validasi input
        $request->validate([
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'warung_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_timeout' => ['nullable', 'integer', 'min:1'],
            'schedule_min_days' => ['nullable', 'integer', 'min:1'],
            'minimal_withdrawal' => ['nullable', 'numeric', 'min:0'],
            'withdrawal_admin_fee' => ['nullable', 'numeric', 'min:0'],
            'auto_approve_limit' => ['nullable', 'numeric', 'min:0'],
            'topup_admin_fee' => ['nullable', 'numeric', 'min:0'],
            'service_fee' => ['nullable', 'numeric', 'min:0'],
            'platform_fee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'payment_code_expiry_hours' => ['nullable', 'integer', 'min:1'],
            'support_phone' => ['nullable', 'string', 'max:20'],
            'support_email' => ['nullable', 'email', 'max:100'],
        ]);

        $updatedCount = 0;

        foreach ($request->except(['_token', '_method']) as $key => $value) {
            // Skip empty values (biarkan nilai existing)
            if ($value === null || $value === '') {
                continue;
            }

            PlatformSetting::setValue($key, $value, auth()->id());
            $updatedCount++;
        }

        // Clear cache
        PlatformSetting::clearCache();

        return redirect()->route('admin.settings')
            ->with('success', "✅ {$updatedCount} pengaturan berhasil disimpan!");
    }

    public function testWhatsApp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:15'],
            'message' => ['required', 'string', 'max:500'],
        ]);

        try {
            app(\App\Services\NotificationService::class)->sendWhatsApp($request->phone, $request->message);

            return response()->json([
                'success' => true,
                'message' => 'Pesan test berhasil dikirim ke ' . $request->phone,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . $e->getMessage(),
            ], 500);
        }
    }
}