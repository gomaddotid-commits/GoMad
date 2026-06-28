<?php
// File: app/Http/Middleware/Api/ApiAuthenticate.php
// Deskripsi: Middleware untuk autentikasi Sanctum token pada API

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Token tidak valid atau kadaluarsa.',
                'data' => null,
                'meta' => null,
            ], 401);
        }

        if (!$request->user()->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Hubungi admin.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        if ($request->user()->banned_at) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dibanned: ' . ($request->user()->banned_reason ?? 'Tidak ada alasan'),
                'data' => null,
                'meta' => null,
            ], 403);
        }

        return $next($request);
    }
}

// End of file