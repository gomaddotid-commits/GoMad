<?php

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
     */
    public function setup(): View|RedirectResponse
    {
        $agent = auth()->user()->paymentAgent;
        $isReset = request()->has('reset');
        
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
            'pin' => ['required', 'string', 'size:6', 'regex:/^[0-9]+$/'],
            'owner_name' => ['required', 'string', 'max:100'],
            'owner_phone' => ['required', 'string', 'max:20'],
            'guard_name' => ['nullable', 'string', 'max:100'],
            'guard_phone' => ['nullable', 'string', 'max:20'],
            'province_code' => ['required', 'string', 'size:2'],
            'city_code' => ['required', 'string', 'size:4'],
            'district_code' => ['nullable', 'string', 'max:7'],
            'maps_link' => ['nullable', 'url', 'max:500'],
        ], [
            'province_code.required' => 'Provinsi harus dipilih.',
            'city_code.required' => 'Kabupaten/Kota harus dipilih.',
            'pin.size' => 'PIN harus 6 digit.',
            'pin.regex' => 'PIN hanya boleh angka.',
        ]);

        $user = auth()->user();
        $agent = $user->paymentAgent;
        
        // Update nomor HP user (WhatsApp)
        $user->update(['phone' => $request->owner_phone]);
        
        if (!$agent) {
            $agent = $user->paymentAgent()->create([
                'agent_name' => $request->agent_name,
                'address' => $request->address,
                'province_code' => $request->province_code,
                'city_code' => $request->city_code,
                'district_code' => $request->district_code,
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
                'province_code' => $request->province_code,
                'city_code' => $request->city_code,
                'district_code' => $request->district_code,
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

        return redirect()->route('payment-agent.dashboard')
            ->with('success', 'Data warung berhasil disimpan! Admin akan memverifikasi dalam 1-3 hari kerja.');
    }
}