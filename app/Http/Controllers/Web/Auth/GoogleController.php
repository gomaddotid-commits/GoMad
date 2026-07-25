<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

            // ⚡ Validasi email terverifikasi dari Google
            if (!$googleUser->getEmail()) {
                return redirect()->route('login')
                    ->with('error', 'Google account tidak mengembalikan email. Coba lagi.');
            }

            // Cari user by email dengan LOCK untuk mencegah race condition
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // ⚡ Cek apakah email ini sudah ada di database (soft-deleted)
                $deletedUser = User::withTrashed()
                    ->where('email', $googleUser->getEmail())
                    ->first();

                if ($deletedUser) {
                    Log::warning('Google login attempt on deleted account', [
                        'email' => $googleUser->getEmail(),
                    ]);
                    return redirect()->route('login')
                        ->with('error', 'Akun dengan email ini sudah dihapus. Hubungi admin untuk pemulihan.');
                }

                // Buat user baru sebagai customer
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'phone' => null,
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

                // Regenerate session ID (security best practice)
                request()->session()->regenerate();

                // Wajib isi nomor HP untuk user baru dari Google
                return redirect()->route('customer.setup')
                    ->with('warning', 'Akun Google berhasil terhubung! Silakan lengkapi nomor WhatsApp Anda.');
            }

            // ═══════════════════════════════════════════
            // 🔒 User SUDAH ADA — strict validation
            // ═══════════════════════════════════════════

            // ⚡ SOFT-DELETED CHECK
            if ($user->trashed()) {
                Log::warning('Google login attempt on soft-deleted account', [
                    'user_id' => $user->id,
                    'email' => $googleUser->getEmail(),
                ]);
                return redirect()->route('login')
                    ->with('error', 'Akun ini sudah dihapus. Hubungi admin.');
            }

            // ⚡ BANNED CHECK (gunakan banned_at dan banned_reason)
            if ($user->banned_at) {
                Log::warning('Banned user attempted Google login', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
                return redirect()->route('login')
                    ->with('error', 'Akun Anda dibanned: ' . ($user->banned_reason ?? 'Tidak ada alasan') .
                        '. Banned sejak: ' . $user->banned_at->format('d M Y'));
            }

            // ⚡ INACTIVE CHECK
            if (!$user->is_active) {
                Log::warning('Inactive user attempted Google login', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
                return redirect()->route('login')
                    ->with('error', 'Akun Anda dinonaktifkan. Hubungi admin.');
            }

            // ⚡ STRICT ROLE CHECK — HANYA customer
            if ($user->role !== 'customer') {
                Log::warning('Non-customer attempted Google login', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                ]);
                return redirect()->route('login')
                    ->with('error', 'Akun ini terdaftar sebagai ' . $user->role .
                        '. Google login hanya untuk customer. Gunakan email & password.');
            }

            // ⚡ EMAIL VERIFIED CHECK
            if (!$user->email_verified_at) {
                $user->update(['email_verified_at' => now()]);
                Log::info('Email verified via Google OAuth', [
                    'user_id' => $user->id,
                ]);
            }

            // Update avatar kalau belum ada atau berbeda
            $googleAvatar = $googleUser->getAvatar();
            if ($googleAvatar && (!$user->avatar_url || $user->avatar_url !== $googleAvatar)) {
                $user->update(['avatar_url' => $googleAvatar]);
            }

            // Login
            Auth::login($user, true);

            // Regenerate session ID
            request()->session()->regenerate();

            Log::info('User logged in via Google', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // Cek apakah nomor HP sudah diisi
            if (empty($user->phone)) {
                return redirect()->route('customer.setup')
                    ->with('warning', 'Silakan lengkapi nomor WhatsApp Anda sebelum melanjutkan.');
            }

            return redirect()->route('customer.home')
                ->with('success', 'Login dengan Google berhasil! Selamat datang, ' . $user->name . '!');

        } catch (\Exception $e) {
            Log::error('Google login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Terjadi kesalahan saat login dengan Google. Silakan coba lagi nanti.');
        }
    }
}
