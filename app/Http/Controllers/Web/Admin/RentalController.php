<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\CustomerDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RentalController extends Controller
{
    /**
     * Dashboard rental admin
     */
    public function dashboard(): View
    {
        return view('admin.rental.dashboard');
    }

    /**
     * Daftar semua rental
     */
    public function index(Request $request): View
    {
        return view('admin.rental.index');
    }

    /**
     * Detail rental
     */
    public function show(Rental $rental): View
    {
        $rental->load(['vehicle.rentalSetting', 'agency', 'customer.customerDocuments', 'payment']);
        return view('admin.rental.show', compact('rental'));
    }

    /**
     * Halaman verifikasi dokumen
     */
    public function documents(Request $request): View
    {
        return view('admin.rental.documents');
    }

    /**
     * Verifikasi dokumen customer
     */
    public function verifyDocument(Request $request, CustomerDocument $document): RedirectResponse
    {
        $updateData = [];
        
        if ($request->has('ktp_verified')) {
            $updateData['ktp_verified'] = true;
        }
        if ($request->has('sim_verified')) {
            $updateData['sim_verified'] = true;
        }
        if ($request->has('npwp_verified')) {
            $updateData['npwp_verified'] = true;
        }

        // Cek apakah semua dokumen wajib sudah verified
        $document->update($updateData);
        
        if ($document->ktp_verified && $document->sim_verified) {
            $document->update([
                'verification_status' => 'verified',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
        }

        return back()->with('success', 'Dokumen berhasil diverifikasi.');
    }

    /**
     * Tolak dokumen customer
     */
    public function rejectDocument(Request $request, CustomerDocument $document): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $document->update([
            'verification_status' => 'rejected',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'rejection_reason' => $request->reason,
        ]);

        return back()->with('success', 'Dokumen ditolak.');
    }
}