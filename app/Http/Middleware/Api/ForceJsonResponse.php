<?php
// File: app/Http/Middleware/Api/ForceJsonResponse.php
// Deskripsi: Middleware untuk memaksa response JSON pada API

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');
        
        $response = $next($request);
        
        if ($response instanceof \Illuminate\Http\Response || 
            $response instanceof \Illuminate\Http\JsonResponse) {
            $response->headers->set('Content-Type', 'application/json');
        }
        
        return $response;
    }
}

// End of file