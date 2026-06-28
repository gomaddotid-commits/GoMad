<?php
// File: app/Http/Controllers/Web/Admin/PaymentAgentController.php
// Deskripsi: Web Controller untuk manajemen payment agent admin

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentAgent;
use App\Services\VerificationService;
use App\Services\PaymentAgentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentAgentController extends Controller
{
    public function __construct(
        private readonly VerificationService $verificationService,
        private readonly PaymentAgentService $paymentAgentService,
    ) {}

    public function index(Request $request): View
    {
        $query = PaymentAgent::with('user');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('agent_name', 'like', '%' . $request->search . '%')
                    ->orWhere('address', 'like', '%' . $request->search . '%')
                    ->orWhere('owner_name', 'like', '%' . $request->search . '%');
            });
        }

        $agents = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.payment-agents.index', compact('agents'));
    }

    public function show(PaymentAgent $agent): View
    {
        $agent->load(['user', 'settlements' => function ($query) {
            $query->latest()->limit(5);
        }]);

        $stats = $this->paymentAgentService->getAgentStats($agent);
        return view('admin.payment-agents.show', compact('agent', 'stats'));
    }

    public function verify(PaymentAgent $agent): RedirectResponse
    {
        $this->verificationService->verifyPaymentAgent($agent, auth()->user());
        return back()->with('success', 'Payment agent berhasil diverifikasi!');
    }

    public function reject(Request $request, PaymentAgent $agent): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->verificationService->rejectPaymentAgent($agent, auth()->user(), $request->reason);
        return back()->with('success', 'Pengajuan warung ditolak. Alasan telah dikirim ke pemilik warung.');
    }

    public function toggleActive(PaymentAgent $agent): RedirectResponse
    {
        $this->paymentAgentService->toggleActive($agent);
        return back()->with('success', 'Status warung berhasil diubah.');
    }
}

// End of file