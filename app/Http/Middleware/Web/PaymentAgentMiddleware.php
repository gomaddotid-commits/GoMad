<?php
// File: app/Http/Middleware/Web/PaymentAgentMiddleware.php
// Deskripsi: Middleware untuk akses payment agent dashboard

namespace App\Http\Middleware\Web;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PaymentAgentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $user = Auth::user();

        if ($user->role !== 'payment_agent') {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Anda tidak memiliki akses payment agent.');
        }

        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Akun Anda dinonaktifkan.');
        }

        $agent = $user->paymentAgent;
        $currentRoute = optional($request->route())->getName();

        if (!$agent || empty($agent->agent_name)) {
            $allowedSetup = ['payment-agent.setup', 'payment-agent.setup.save', 'logout'];
            if (!in_array($currentRoute, $allowedSetup)) {
                return redirect()->route('payment-agent.setup')
                    ->with('warning', 'Silakan lengkapi data warung Anda terlebih dahulu.');
            }
            return $next($request);
        }

        if (!$agent->is_verified) {
            $allowedUnverified = [
                'payment-agent.setup',
                'payment-agent.setup.save',
                'payment-agent.profile',
                'payment-agent.dashboard',
                'logout',
            ];
            if (!in_array($currentRoute, $allowedUnverified)) {
                return redirect()->route('payment-agent.dashboard')
                    ->with('warning', 'Warung Anda belum diverifikasi. Hubungi admin GoMad.');
            }
        }

        return $next($request);
    }
}

// End of file