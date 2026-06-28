<?php
// File: app/Http/Controllers/Api/Admin/AgencyController.php
// Deskripsi: API Controller untuk manajemen agency oleh admin

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AgencyDetailResource;
use App\Models\Agency;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
    public function __construct(
        private readonly VerificationService $verificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Agency::with('user');

        if ($request->has('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('agency_name', 'like', '%' . $request->search . '%')
                    ->orWhere('address', 'like', '%' . $request->search . '%');
            });
        }

        $agencies = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar agency berhasil diambil.',
            'data' => AgencyDetailResource::collection($agencies),
            'meta' => [
                'current_page' => $agencies->currentPage(),
                'last_page' => $agencies->lastPage(),
                'total' => $agencies->total(),
            ],
        ]);
    }

    public function show(Agency $agency): JsonResponse
    {
        $agency->load(['user', 'wallet', 'vehicles', 'drivers', 'verifications.verifier']);

        return response()->json([
            'success' => true,
            'message' => 'Detail agency berhasil diambil.',
            'data' => new AgencyDetailResource($agency),
            'meta' => null,
        ]);
    }

    public function verify(Agency $agency): JsonResponse
    {
        try {
            $this->verificationService->approveVerification($agency, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Agency berhasil diverifikasi.',
                'data' => null,
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 422);
        }
    }

    public function reject(Request $request, Agency $agency): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->verificationService->rejectVerification($agency, request()->user(), $request->reason);

            return response()->json([
                'success' => true,
                'message' => 'Verifikasi agency ditolak.',
                'data' => null,
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 422);
        }
    }

    public function toggleActive(Agency $agency): JsonResponse
    {
        $agency->user->update(['is_active' => !$agency->user->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Status agency berhasil diubah.',
            'data' => [
                'is_active' => $agency->user->fresh()->is_active,
            ],
            'meta' => null,
        ]);
    }
}

// End of file