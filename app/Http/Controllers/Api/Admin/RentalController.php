<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\CustomerDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    /**
     * Daftar semua rental
     */
    public function index(Request $request): JsonResponse
    {
        $query = Rental::with(['vehicle.rentalSetting', 'agency', 'customer', 'payment']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->agency_id) {
            $query->where('agency_id', $request->agency_id);
        }

        $rentals = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Daftar rental berhasil diambil.',
            'data' => $rentals->items(),
            'meta' => [
                'current_page' => $rentals->currentPage(),
                'last_page' => $rentals->lastPage(),
                'total' => $rentals->total(),
            ],
        ]);
    }

    /**
     * Detail rental
     */
    public function show(Rental $rental): JsonResponse
    {
        $rental->load(['vehicle.rentalSetting', 'agency.user', 'customer', 'payment']);

        return response()->json([
            'success' => true,
            'message' => 'Detail rental berhasil diambil.',
            'data' => $rental,
            'meta' => null,
        ]);
    }

    /**
     * Daftar dokumen customer yang perlu diverifikasi
     */
    public function pendingDocuments(): JsonResponse
    {
        $documents = CustomerDocument::with('user')
            ->where('verification_status', 'pending')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Daftar dokumen pending berhasil diambil.',
            'data' => $documents->items(),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    /**
     * Verifikasi dokumen customer
     */
    public function verifyDocument(Request $request, CustomerDocument $document): JsonResponse
    {
        $request->validate([
            'ktp_verified' => ['nullable', 'boolean'],
            'sim_verified' => ['nullable', 'boolean'],
            'npwp_verified' => ['nullable', 'boolean'],
        ]);

        $updateData = [];
        if ($request->has('ktp_verified')) {
            $updateData['ktp_verified'] = $request->ktp_verified;
        }
        if ($request->has('sim_verified')) {
            $updateData['sim_verified'] = $request->sim_verified;
        }
        if ($request->has('npwp_verified')) {
            $updateData['npwp_verified'] = $request->npwp_verified;
        }

        // Cek apakah semua dokumen wajib sudah verified
        $document->update($updateData);
        
        if ($document->ktp_verified && $document->sim_verified) {
            $document->update([
                'verification_status' => 'verified',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dokumen berhasil diverifikasi.',
            'data' => $document->fresh(),
            'meta' => null,
        ]);
    }

    /**
     * Tolak dokumen customer
     */
    public function rejectDocument(Request $request, CustomerDocument $document): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $document->update([
            'verification_status' => 'rejected',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'rejection_reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dokumen ditolak.',
            'data' => $document->fresh(),
            'meta' => null,
        ]);
    }
}