<?php
// File: app/Http/Controllers/Api/Customer/AgencyController.php
// Deskripsi: API Controller untuk melihat daftar agency oleh customer

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AgencyResource;
use App\Models\Agency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Agency::where('is_verified', true);

        if ($request->search) {
            $query->where('agency_name', 'like', '%' . $request->search . '%');
        }

        $agencies = $query->orderByDesc('rating')
            ->orderByDesc('total_bookings')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar agency berhasil diambil.',
            'data' => AgencyResource::collection($agencies),
            'meta' => [
                'total' => $agencies->count(),
            ],
        ]);
    }
}

// End of file