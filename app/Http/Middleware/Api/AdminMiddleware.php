<?php
// File: app/Http/Middleware/Api/AdminMiddleware.php
// Deskripsi: Middleware API untuk role admin

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Hanya admin yang dapat mengakses.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        return $next($request);
    }
}

// End of file