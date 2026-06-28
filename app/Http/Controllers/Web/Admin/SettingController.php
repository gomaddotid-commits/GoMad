<?php
// File: app/Http/Controllers/Web/Admin/SettingController.php
// Deskripsi: Web Controller untuk pengaturan platform

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = PlatformSetting::getAllSettings();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        foreach ($request->except('_token', '_method') as $key => $value) {
            PlatformSetting::setValue($key, $value, auth()->id());
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}

// End of file