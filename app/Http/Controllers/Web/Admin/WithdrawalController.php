<?php
// File: app/Http/Controllers/Web/Admin/WithdrawalController.php
// Deskripsi: Web Controller untuk manajemen withdrawal oleh admin

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $withdrawalService,
    ) {}

    public function index(Request $request): View
    {
        $query = Withdrawal::with(['agency.user', 'approver']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.withdrawals.index', compact('withdrawals'));
    }

    public function approve(Withdrawal $withdrawal): RedirectResponse
    {
        try {
            $this->withdrawalService->approveWithdrawal($withdrawal, auth()->user());
            return back()->with('success', 'Withdrawal disetujui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);
        try {
            $this->withdrawalService->rejectWithdrawal($withdrawal, auth()->user(), $request->reason);
            return back()->with('success', 'Withdrawal ditolak.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

// End of file