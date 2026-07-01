<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $routes = Route::where('is_active', true)
            ->with('stops')
            ->orderBy('route_name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar rute berhasil diambil.',
            'data' => $routes,
            'meta' => [
                'total' => $routes->count(),
            ],
        ]);
    }
}