<?php
// File: app/Http/Controllers/Web/PaymentAgent/PaymentController.php
// Deskripsi: Web Controller untuk pembayaran payment agent

namespace App\Http\Controllers\Web\PaymentAgent;

use App\Http\Controllers\Controller;
use App\Services\CashPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly CashPaymentService $cashPaymentService,
    ) {}

    public function index(): View
    {
        return view('payment-agent.payments');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'payment_code' => ['required', 'string'],
            'pin' => ['required', 'string', 'size:6'],
        ]);

        try {
            $agent = auth()->user()->paymentAgent;
            $this->cashPaymentService->confirmCashPayment($request->payment_code, $agent->id, $request->pin);
            return back()->with('success', 'Pembayaran berhasil dikonfirmasi!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

// End of file