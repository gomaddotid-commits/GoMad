<?php
// File: app/Http/Controllers/Web/Auth/RegisterController.php
// Deskripsi: Web Controller untuk registrasi (FIXED)

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'], // 👈 TAMBAH unique
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:customer,agency,payment_agent'],
            'referral_code' => ['nullable', 'string', 'exists:referral_codes,code'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
        ]);

        // Generate referral code untuk user baru
        $promoService = app(\App\Services\PromoService::class);
        $promoService->generateReferralCode($user);

        // Proses referral jika ada kode
        if ($request->filled('referral_code')) {
            $promoService->processReferralRegistration($user, strtoupper($request->referral_code));
            // Logout dulu, lalu login dengan user yang sudah diupdate
        }

        try {
            match ($user->role) {
                'customer' => app(\App\Services\NotificationService::class)->welcomeCustomer($user),
                'agency' => app(\App\Services\NotificationService::class)->welcomeAgency($user, $user->agency),
                'payment_agent' => app(\App\Services\NotificationService::class)->welcomePaymentAgent($user, $user->paymentAgent),
                default => null,
            };
        } catch (\Exception $e) {
            \Log::error('Welcome WhatsApp failed: ' . $e->getMessage());
        }

        Auth::login($user);

        // Redirect ke setup profile
        return match ($user->role) {
            'customer' => redirect()->route('customer.setup')
                ->with('success', 'Akun berhasil dibuat!'),
            'agency' => redirect()->route('agency.setup')
                ->with('success', 'Akun agency berhasil dibuat!'),
            'payment_agent' => redirect()->route('payment-agent.setup')
                ->with('success', 'Akun warung berhasil dibuat!'),
            default => redirect()->route('home'),
        };
    }
}

// End of file