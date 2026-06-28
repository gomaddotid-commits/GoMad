<?php
// File: app/Http/Middleware/Api/PaymentAgentMiddleware.php
// Deskripsi: Middleware API untuk role payment_agent

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentAgentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'payment_agent') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Hanya payment agent yang dapat mengakses.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        $paymentAgent = $user->paymentAgent;
        
        if (!$paymentAgent || !$paymentAgent->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Warung belum diverifikasi. Hubungi admin untuk verifikasi.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        if (!$paymentAgent->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun warung dinonaktifkan. Hubungi admin.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        return $next($request);
    }
}

// End of file