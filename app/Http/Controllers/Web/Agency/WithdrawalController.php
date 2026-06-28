<?php
// File: app/Http/Controllers/Web/Agency/WithdrawalController.php
// Deskripsi: Web Controller untuk penarikan dana agency

namespace App\Http\Controllers\Web\Agency;

use App\Http\Controllers\Controller;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $withdrawalService,
    ) {}

    public function index(): View
    {
        $withdrawals = $this->withdrawalService->getAgencyWithdrawals(auth()->user()->agency);
        return view('agency.withdrawals.index', compact('withdrawals'));
    }

    public function create(): View
    {
        return view('agency.wallet.withdraw');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:' . config('gomad.minimal_withdrawal', 100000)],
            'bank_name' => ['required', 'string'],
            'bank_account_number' => ['required', 'string'],
            'bank_account_name' => ['required', 'string'],
        ]);

        try {
            $this->withdrawalService->createWithdrawal(auth()->user()->agency, $request->all());
            return redirect()->route('agency.withdrawals.index')->with('success', 'Penarikan berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}

// End of file