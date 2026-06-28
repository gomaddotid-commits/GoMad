<?php
// File: app/Http/Controllers/Web/Admin/AgencyController.php
// Deskripsi: Web Controller untuk manajemen agency admin

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgencyController extends Controller
{
    public function __construct(
        private readonly VerificationService $verificationService,
    ) {}

    public function index(): View
    {
        $agencies = Agency::with('user')->latest()->paginate(15);
        return view('admin.agencies.index', compact('agencies'));
    }

    public function show(Agency $agency): View
    {
        $agency->load(['user', 'wallet', 'vehicles', 'drivers', 'verifications.verifier']);
        return view('admin.agencies.show', compact('agency'));
    }

    public function verify(Agency $agency): RedirectResponse
    {
        try {
            $this->verificationService->approveVerification($agency, auth()->user());
            return back()->with('success', 'Agency berhasil diverifikasi!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, Agency $agency): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->verificationService->rejectVerification($agency, auth()->user(), $request->reason);
            return back()->with('success', 'Pengajuan agency ditolak. Alasan telah dikirim ke agency.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

// End of file