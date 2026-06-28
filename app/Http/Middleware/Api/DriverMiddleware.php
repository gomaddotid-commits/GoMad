<?php
// File: app/Http/Middleware/Api/DriverMiddleware.php
// Deskripsi: Middleware API untuk role driver

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DriverMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'driver') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Hanya driver yang dapat mengakses.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        if (!$user->agency_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak terdaftar di agency manapun.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        return $next($request);
    }
}

// End of file