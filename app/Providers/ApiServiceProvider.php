<?php
// File: app/Providers/ApiServiceProvider.php
// Deskripsi: Service provider untuk konfigurasi API

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\JsonResponse;

class ApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Response::macro('apiSuccess', function (mixed $data = null, string $message = 'Success', int $code = 200, mixed $meta = null): JsonResponse {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data,
                'meta' => $meta,
            ], $code);
        });

        Response::macro('apiError', function (string $message = 'Error', int $code = 400, mixed $data = null, mixed $meta = null): JsonResponse {
            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => $data,
                'meta' => $meta,
            ], $code);
        });

        Response::macro('apiPaginated', function ($paginator, string $message = 'Success'): JsonResponse {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        });
    }
}

// End of file