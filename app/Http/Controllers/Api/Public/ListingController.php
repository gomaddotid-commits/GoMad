<?php
// File: app/Http/Controllers/Api/Public/ListingController.php
// Deskripsi: API Controller untuk listing agency

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AgencyResource;
use App\Models\Agency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'sort' => ['nullable', 'in:rating,bookings,newest'],
            'search' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Agency::where('is_verified', true);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('agency_name', 'like', '%' . $request->search . '%')
                    ->orWhere('address', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $sort = $request->sort ?? 'rating';
        match ($sort) {
            'rating' => $query->orderByDesc('rating'),
            'bookings' => $query->orderByDesc('total_bookings'),
            'newest' => $query->latest(),
            default => $query->orderByDesc('rating'),
        };

        $agencies = $query->paginate($request->limit ?? 12);

        return response()->json([
            'success' => true,
            'message' => 'Daftar agency berhasil diambil.',
            'data' => AgencyResource::collection($agencies),
            'meta' => [
                'current_page' => $agencies->currentPage(),
                'last_page' => $agencies->lastPage(),
                'total' => $agencies->total(),
            ],
        ]);
    }
}

// End of file