<?php
// File: app/Http/Middleware/Api/AgencyMiddleware.php
// Deskripsi: Middleware API untuk role agency

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'agency') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Hanya agency yang dapat mengakses.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        $agency = $user->agency;
        if (!$agency || !$agency->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Agency belum diverifikasi. Lengkapi profil dan ajukan verifikasi.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        return $next($request);
    }
}

// End of file