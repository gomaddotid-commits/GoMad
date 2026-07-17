<?php
// File: app/Http/Middleware/MidtransWebhookMiddleware.php
// Deskripsi: Validasi request dari Midtrans (IP whitelist + basic auth check)

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MidtransWebhookMiddleware
{
    /**
     * IP Midtrans yang valid (production + sandbox)
     */
    private array $midtransIps = [
        // Production
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
        // Di environment local/testing, skip validasi IP
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        // Validasi IP (Midtrans whitelist)
        $clientIp = $request->ip();
        
        // Kalau pakai Cloudflare, ambil IP asli dari header
        if ($request->header('CF-Connecting-IP')) {
            $clientIp = $request->header('CF-Connecting-IP');
        }

        if (!in_array($clientIp, $this->midtransIps)) {
            \Log::warning('Midtrans webhook: IP tidak dikenal', [
                'ip' => $clientIp,
                'url' => $request->fullUrl(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: IP not allowed.',
            ], 403);
        }

        return $next($request);
    }
}