<?php
// File: app/Http/Controllers/Web/Driver/ProfileController.php
// Deskripsi: Web Controller untuk profil driver

namespace App\Http\Controllers\Web\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('driver.profile');
    }

    public function update(Request $request): RedirectResponse
    {
        auth()->user()->update($request->only(['name', 'phone']));
        return back()->with('success', 'Profil berhasil diupdate.');
    }
}

// End of file