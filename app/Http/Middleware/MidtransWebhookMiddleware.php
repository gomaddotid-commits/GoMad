<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MidtransWebhookMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya di production — development/local bebas akses
        if (app()->environment('production')) {
            // Daftar IP Midtrans (dari dokumentasi resmi)
            $midtransIps = [
                '103.228.118.74',
                '103.228.118.75',
                '103.228.118.76',
            ];

            $clientIp = $request->ip();

            if (!in_array($clientIp, $midtransIps)) {
                \Log::warning('Midtrans webhook: unauthorized IP', ['ip' => $clientIp]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }
        }

        return $next($request);
    }
}