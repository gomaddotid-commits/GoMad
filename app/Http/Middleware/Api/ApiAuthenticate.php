<?php

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

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Token tidak valid atau kadaluarsa.',
                'data' => null,
                'meta' => null,
            ], 401);
        }

        // ✅ Check banned
        if ($user->banned_at) {
            Log::warning('Banned user attempted API access', [
                'user_id' => $user->id,
                'email' => $user->email,
                'banned_reason' => $user->banned_reason,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => false,
                'message' => 'Akun Anda dibanned: ' . ($user->banned_reason ?? 'Tidak ada alasan'),
                'data' => null,
                'meta' => null,
            ], 403);
        }

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

        // ✅ ENHANCED: Token expiry check dengan grace period
        $token = $user->currentAccessToken();
        if ($token) {
            $tokenAgeInHours = $token->created_at->diffInHours(now());
            $maxTokenAge = (int) config('sanctum.expiration', 43200) / 60; // Convert to hours
            
            // ✅ Jika token hampir expired (< 24 jam tersisa), beri warning header
            if ($tokenAgeInHours > ($maxTokenAge - 24) && $tokenAgeInHours <= $maxTokenAge) {
                // Token masih valid tapi hampir expired
                // Client sebaiknya refresh token
            }
            
            // ✅ Jika token sudah expired
            if ($tokenAgeInHours > $maxTokenAge) {
                $token->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Token kadaluarsa. Silakan login ulang.',
                    'data' => null,
                    'meta' => ['token_expired' => true],
                ], 401);
            }
        }

        return $next($request);
    }
}