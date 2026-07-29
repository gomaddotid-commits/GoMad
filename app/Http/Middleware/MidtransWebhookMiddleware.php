<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MidtransWebhookMiddleware
{
    /**
     * IP Midtrans yang valid (production + sandbox)
     * ✅ Update dengan IP terbaru dari dokumentasi Midtrans
     */
    private array $midtransIps = [
        // Production (✅ Update terbaru dari docs.midtrans.com)
        '13.76.145.123',
        '13.76.144.123',
        '20.198.128.61',
        '20.198.128.62',
        '20.198.128.63',
        '20.198.128.64',
        '20.198.128.65',
        '20.198.128.66',
        '20.198.128.67',
        '20.198.128.68',
        // Sandbox
        '103.213.73.10',
        '103.213.73.11',
        '103.213.73.12',
        '103.213.73.13',
        '103.213.73.14',
        '103.213.73.15',
        '103.213.73.16',
        '103.213.73.17',
        '103.213.73.18',
        '103.213.73.19',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // ✅ TAMBAHKAN: Always log webhook attempts
        \Log::info('Midtrans webhook received', [
            'ip' => $request->ip(),
            'cf_ip' => $request->header('CF-Connecting-IP'),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
        ]);

        // Di environment local/testing, skip validasi IP tapi tetap LOG
        if (app()->environment('local', 'testing')) {
            \Log::info('Midtrans webhook: IP validation skipped (local/testing)');
            return $next($request);
        }

        // Validasi IP
        $clientIp = $request->ip();
        
        // ✅ ENHANCED: Cek multiple Cloudflare headers
        if ($request->header('CF-Connecting-IP')) {
            $clientIp = $request->header('CF-Connecting-IP');
        } elseif ($request->header('X-Forwarded-For')) {
            // Ambil IP pertama dari X-Forwarded-For
            $forwardedIps = explode(',', $request->header('X-Forwarded-For'));
            $clientIp = trim($forwardedIps[0]);
        }

        if (!in_array($clientIp, $this->midtransIps)) {
            \Log::warning('Midtrans webhook: IP tidak dikenal', [
                'ip' => $clientIp,
                'url' => $request->fullUrl(),
                'headers' => $request->headers->all(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: IP not allowed.',
            ], 403);
        }

        \Log::info('Midtrans webhook: IP validated successfully', ['ip' => $clientIp]);
        
        return $next($request);
    }
}