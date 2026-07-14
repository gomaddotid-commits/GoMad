<?php
// File: app/Http/Middleware/Web/AgencyMiddleware.php
// Deskripsi: Middleware untuk akses agency dashboard

namespace App\Http\Middleware\Web;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AgencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $user = Auth::user();

        if ($user->role !== 'agency') {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Anda tidak memiliki akses agency.');
        }

        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Akun Anda dinonaktifkan.');
        }

        $agency = $user->agency;

        // Jika belum punya data agency, arahkan ke setup
        if (!$agency || !$agency->agency_name) {
            $allowedSetupRoutes = ['agency.setup', 'agency.setup.save', 'logout'];
            $currentRoute = optional($request->route())->getName();
            
            if (!in_array($currentRoute, $allowedSetupRoutes)) {
                return redirect()->route('agency.setup')
                    ->with('warning', 'Silakan lengkapi data agency Anda terlebih dahulu.');
            }
            return $next($request);
        }

        // Jika agency belum verified, izinkan akses terbatas
        if (!$agency->is_verified) {
            $allowedRoutes = [
                'agency.setup',
                'agency.setup.save',
                'agency.profile.edit',
                'agency.profile.update',
                'agency.profile.logo',
                'agency.profile.cover',
                'agency.profile.license',
                'agency.profile.gallery.add',
                'agency.profile.gallery.remove',
                'agency.profile.verify',
                'agency.dashboard',
                'agency.drivers.index', 'agency.drivers.create',  // 👈 TAMBAH INI
                'agency.drivers.store', 'agency.drivers.edit',     // 👈 TAMBAH INI
                'agency.drivers.update', 'agency.drivers.destroy', // 👈 TAMBAH INI
                'logout',
            ];

            $currentRoute = optional($request->route())->getName();

            if (!in_array($currentRoute, $allowedRoutes)) {
                return redirect()->route('agency.dashboard')
                    ->with('warning', 'Agency Anda belum diverifikasi. Lengkapi profil dan ajukan verifikasi.');
            }
        }

        return $next($request);
    }
}

// End of file