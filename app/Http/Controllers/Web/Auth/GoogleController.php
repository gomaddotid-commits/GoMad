<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect ke Google
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Callback dari Google
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Cari user by email
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // Buat user baru sebagai customer
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'phone' => null, // Google tidak kasih nomor HP
                    'avatar_url' => $googleUser->getAvatar(),
                    'password' => bcrypt(uniqid()),
                    'role' => 'customer',
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]);

                // Generate referral code
                app(\App\Services\PromoService::class)->generateReferralCode($user);

                // Login
                Auth::login($user, true);

                // Wajib isi nomor HP untuk user baru dari Google
                return redirect()->route('customer.setup')
                    ->with('warning', 'Akun Google berhasil terhubung! Silakan lengkapi nomor WhatsApp Anda.');
            }

            // User sudah ada
            // Update avatar kalau belum ada
            if (!$user->avatar_url) {
                $user->update(['avatar_url' => $googleUser->getAvatar()]);
            }

            // Ban check
            if ($user->banned_at) {
                return redirect()->route('login')
                    ->with('error', 'Akun Anda dibanned: ' . ($user->banned_reason ?? 'Tidak ada alasan'));
            }

            if (!$user->is_active) {
                return redirect()->route('login')
                    ->with('error', 'Akun Anda dinonaktifkan. Hubungi admin.');
            }

            // Role check — hanya customer yang bisa login via Google
            if ($user->role !== 'customer') {
                return redirect()->route('login')
                    ->with('error', 'Akun ini terdaftar sebagai ' . $user->role . '. Silakan login dengan email & password.');
            }

            Auth::login($user, true);

            // Cek apakah nomor HP sudah diisi
            if (empty($user->phone)) {
                return redirect()->route('customer.setup')
                    ->with('warning', 'Silakan lengkapi nomor WhatsApp Anda sebelum melanjutkan.');
            }

            return redirect()->route('customer.home')
                ->with('success', 'Login dengan Google berhasil! Selamat datang, ' . $user->name . '!');

        } catch (\Exception $e) {
            \Log::error('Google login error: ' . $e->getMessage());

            return redirect()->route('login')
                ->with('error', 'Gagal login dengan Google. Silakan coba lagi.');
        }
    }
}
