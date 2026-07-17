<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:customer,agency,payment_agent'],
            'referral_code' => ['nullable', 'string', 'exists:referral_codes,code'],
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'is_active' => true,
                'email_verified_at' => null, // Belum verified
            ]);

            // Generate referral code untuk user baru
            $promoService = app(\App\Services\PromoService::class);
            $promoService->generateReferralCode($user);

            // Proses referral jika ada kode
            if ($request->filled('referral_code')) {
                $promoService->processReferralRegistration($user, strtoupper($request->referral_code));
            }

            DB::commit();

            // Kirim email verifikasi
            event(new Registered($user));

            Auth::login($user);

            // Redirect ke halaman verifikasi email
            return redirect()->route('verification.notice')
                ->with('success', 'Akun berhasil dibuat! Silakan cek email Anda untuk verifikasi.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Registration failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal mendaftar: ' . $e->getMessage())->withInput();
        }
    }
}