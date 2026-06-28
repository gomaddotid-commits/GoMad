<?php
// File: app/Http/Controllers/Web/Customer/ProfileController.php
// Deskripsi: Web Controller untuk profil customer

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = auth()->user();
        return view('customer.profile', compact('user'));
    }

    /**
     * Halaman setup profil customer
     */
    public function setup(): View|RedirectResponse
    {
        $user = auth()->user();
        
        // Jika sudah lengkap profil, redirect ke home
        if ($user->phone) {
            return redirect()->route('customer.home')
                ->with('warning', 'Profil Anda sudah lengkap.');
        }
        
        return view('customer.profile-setup', compact('user'));
    }

    public function saveSetup(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = auth()->user();
        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        return redirect()->route('customer.home')
            ->with('success', 'Profil berhasil dilengkapi! Selamat datang di GoMad, ' . $user->name . '!');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        auth()->user()->update($request->only(['name', 'phone']));

        return back()->with('success', 'Profil berhasil diupdate.');
    }

    public function skip(): RedirectResponse
    {
        return redirect()->route('customer.home')
            ->with('success', 'Anda dapat melengkapi profil kapan saja.');
    }
}

// End of file