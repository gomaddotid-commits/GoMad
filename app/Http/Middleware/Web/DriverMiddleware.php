<?php
// File: app/Http/Middleware/Web/DriverMiddleware.php
// Deskripsi: Middleware untuk akses driver dashboard

namespace App\Http\Middleware\Web;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DriverMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $user = Auth::user();

        if ($user->role !== 'driver') {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Anda tidak memiliki akses driver.');
        }

        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Akun Anda dinonaktifkan.');
        }

        if (!$user->agency_id) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Anda tidak terdaftar di agency manapun.');
        }

        return $next($request);
    }
}

// End of file