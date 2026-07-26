<?php
// File: app/Http/Middleware/Api/ApiAuthenticate.php
// Deskripsi: Middleware untuk autentikasi Sanctum token pada API

namespace App\Http\Middleware\Api;
use Illuminate\Support\Facades\Log;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // ⚡ Validasi token tidak kosong
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Token tidak valid atau kadaluarsa.',
                'data' => null,
                'meta' => null,
            ], 401);
        }

        // ⚡ Cek apakah user di-ban
        if ($user->banned_at) {
            // Log untuk audit
            Log::warning('Banned user attempted API access', [
                'user_id' => $user->id,
                'email' => $user->email,
                'banned_reason' => $user->banned_reason,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Revoke token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dibanned: ' . ($user->banned_reason ?? 'Tidak ada alasan'),
                'data' => null,
                'meta' => null,
            ], 403);
        }

        // ⚡ Cek user aktif
        if (!$user->is_active) {
            Log::warning('Inactive user attempted API access', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dinonaktifkan. Hubungi admin.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        // ⚡ Rate limit check berdasarkan user ID (opsional, via token ability)
        // Ini mencegah 1 user melakukan spam request
        $token = $user->currentAccessToken();
        if ($token && $token->created_at->diffInHours(now()) > 720) { // 30 hari
            $token->delete();
            return response()->json([
                'success' => false,
                'message' => 'Token kadaluarsa. Silakan login ulang.',
                'data' => null,
                'meta' => null,
            ], 401);
        }

        return $next($request);
    }
}

// End of file