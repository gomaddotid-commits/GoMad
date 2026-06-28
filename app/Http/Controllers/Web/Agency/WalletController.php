<?php
// File: app/Http/Controllers/Web/Agency/WalletController.php
// Deskripsi: Web Controller untuk wallet agency

namespace App\Http\Controllers\Web\Agency;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $agency = auth()->user()->agency;
        $walletService = app(\App\Services\WalletService::class);
        $balance = $walletService->getBalance($agency);
        $balanceSummary = $walletService->getBalanceSummary($agency);
        $transactions = $walletService->getMutationHistory($agency, $request->type, 50);
        
        return view('agency.wallet.index', compact('balance', 'balanceSummary', 'transactions'));
    }

    public function topUpPage(): View
    {
        return view('agency.wallet.topup');
    }

    public function processTopUp(Request $request): RedirectResponse
    {
        $request->validate(['amount' => ['required', 'numeric', 'min:10000']]);
        $amount = (float) $request->amount;
        $agency = auth()->user()->agency;
        $walletService = app(\App\Services\WalletService::class);
        $result = $walletService->createTopUpTransaction($agency, $amount);
        if ($result['success'] && !empty($result['snap_token'])) {
            return redirect()->route('agency.wallet.index')
                ->with('snap_token', $result['snap_token'])
                ->with('success', 'Silakan selesaikan pembayaran top up.');
        }
        return back()->with('error', $result['message'] ?? 'Gagal membuat top up.');
    }

    public function transferToDeposit(Request $request): RedirectResponse
    {
        $request->validate(['amount' => ['required', 'numeric', 'min:10000']]);
        try {
            $walletService = app(\App\Services\WalletService::class);
            $walletService->transferToDeposit(auth()->user()->agency, (float) $request->amount);
            return back()->with('success', 'Saldo berhasil ditransfer ke Saldo Deposit!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

// End of file