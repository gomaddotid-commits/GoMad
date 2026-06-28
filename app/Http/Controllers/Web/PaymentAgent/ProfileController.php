<?php
// File: app/Http/Controllers/Web/PaymentAgent/ProfileController.php
// Deskripsi: Web Controller untuk profil payment agent (FIXED)

namespace App\Http\Controllers\Web\PaymentAgent;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $agent = auth()->user()->paymentAgent;
        return view('payment-agent.profile', compact('agent'));
    }

    /**
     * Halaman setup profil warung
     * Jika ada parameter ?reset=1, tampilkan form setup dari awal
     */
    public function setup(): View|RedirectResponse
    {
        $agent = auth()->user()->paymentAgent;
        $isReset = request()->has('reset');
        
        // Jika bukan reset dan profil sudah lengkap, redirect ke dashboard
        if (!$isReset && $agent && $agent->agent_name && $agent->address) {
            return redirect()->route('payment-agent.dashboard')
                ->with('warning', 'Profil warung Anda sudah lengkap.');
        }
        
        return view('payment-agent.profile-setup', compact('agent'));
    }

    /**
     * Simpan setup profil warung
     */
    public function saveSetup(Request $request): RedirectResponse
    {
        $request->validate([
            'agent_name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
            'maps_link' => ['nullable', 'url', 'max:500'],
            'owner_name' => ['required', 'string', 'max:100'],
            'owner_phone' => ['required', 'string', 'max:20'],
            'guard_name' => ['nullable', 'string', 'max:100'],
            'guard_phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = auth()->user();
        $agent = $user->paymentAgent;
        
        if (!$agent) {
            $agent = $user->paymentAgent()->create([
                'agent_name' => $request->agent_name,
                'address' => $request->address,
                'kecamatan' => $request->kecamatan,
                'pin' => Hash::make($request->pin),
                'maps_link' => $request->maps_link,
                'owner_name' => $request->owner_name,
                'owner_phone' => $request->owner_phone,
                'guard_name' => $request->guard_name,
                'guard_phone' => $request->guard_phone,
                'is_active' => true,
                'is_verified' => false,
                'commission_rate' => 2.00,
            ]);
        } else {
            $data = [
                'agent_name' => $request->agent_name,
                'address' => $request->address,
                'kecamatan' => $request->kecamatan,
                'maps_link' => $request->maps_link,
                'owner_name' => $request->owner_name,
                'owner_phone' => $request->owner_phone,
                'guard_name' => $request->guard_name,
                'guard_phone' => $request->guard_phone,
            ];
            
            // Update PIN hanya jika diisi baru
            if ($request->filled('pin')) {
                $data['pin'] = Hash::make($request->pin);
            }
            
            $agent->update($data);
        }

        // Payment agent tidak perlu auto-submit verifikasi seperti agency
        // Admin yang akan memverifikasi secara manual

        return redirect()->route('payment-agent.dashboard')
            ->with('success', 'Data warung berhasil disimpan! Admin akan memverifikasi dalam 1-3 hari kerja.');
    }
}

// End of file