<?php
// File: app/Http/Controllers/Web/PaymentAgent/SettlementController.php
// Deskripsi: Web Controller untuk settlement payment agent (FULL)

namespace App\Http\Controllers\Web\PaymentAgent;

use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Services\SettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettlementController extends Controller
{
    public function __construct(
        private readonly SettlementService $settlementService,
    ) {}

    public function index(): View
    {
        $agent = auth()->user()->paymentAgent;
        $settlements = $this->settlementService->getAgentSettlements($agent->id);
        
        // Generate snap token untuk settlement yang pending
        $snapTokens = [];
        foreach ($settlements as $settlement) {
            if (in_array($settlement->status, ['pending', 'overdue'])) {
                try {
                    $snapTokens[$settlement->id] = $this->settlementService->paySettlement($settlement);
                } catch (\Exception $e) {
                    $snapTokens[$settlement->id] = null;
                }
            }
        }
        
        return view('payment-agent.settlements', compact('settlements', 'snapTokens'));
    }

    public function paySettlement(Settlement $settlement): RedirectResponse
    {
        $agent = auth()->user()->paymentAgent;
        
        if ($settlement->payment_agent_id !== $agent->id) {
            abort(403);
        }

        try {
            $snapToken = $this->settlementService->paySettlement($settlement);
            
            return redirect()->route('payment-agent.settlements')
                ->with('snap_token', $snapToken)
                ->with('settlement_id', $settlement->id)
                ->with('success', 'Silakan selesaikan pembayaran.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat pembayaran: ' . $e->getMessage());
        }
    }
}

// End of file