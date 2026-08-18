<?php

namespace App\Http\Controllers\Web\PaymentAgent;

use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    // Kasir — halaman scan + transaksi
    public function index(): View
    {
        $agent = auth()->user()->paymentAgent;
        if (!$agent || !$agent->is_verified) {
            return redirect()->route('payment-agent.dashboard');
        }

        $products = Product::where('payment_agent_id', $agent->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $today = now()->toDateString();
        $todayStats = [
            'transactions' => PosTransaction::where('payment_agent_id', $agent->id)
                ->whereDate('created_at', $today)->count(),
            'revenue' => (int) PosTransaction::where('payment_agent_id', $agent->id)
                ->whereDate('created_at', $today)->sum('total'),
        ];

        return view('payment-agent.pos.index', compact('products', 'todayStats', 'agent'));
    }

    // Kelola produk
    public function products(Request $request): View
    {
        $agent = auth()->user()->paymentAgent;
        if (!$agent || !$agent->is_verified) {
            return redirect()->route('payment-agent.dashboard');
        }

        $products = Product::where('payment_agent_id', $agent->id)
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(20);

        return view('payment-agent.pos.products', compact('products', 'agent'));
    }

    // Laporan penjualan
    public function reports(Request $request): View
    {
        $agent = auth()->user()->paymentAgent;
        if (!$agent || !$agent->is_verified) {
            return redirect()->route('payment-agent.dashboard');
        }

        $date = $request->date ?? now()->toDateString();

        $transactions = PosTransaction::where('payment_agent_id', $agent->id)
            ->whereDate('created_at', $date)
            ->latest()
            ->get();

        $summary = [
            'total_transactions' => $transactions->count(),
            'total_revenue' => (int) $transactions->sum('total'),
            'total_items' => (int) $transactions->sum(fn($t) => $t->items()->count()),
            'cash' => (int) $transactions->where('payment_method', 'cash')->sum('total'),
            'qris' => (int) $transactions->where('payment_method', 'qris')->sum('total'),
        ];

        return view('payment-agent.pos.reports', compact('transactions', 'summary', 'date', 'agent'));
    }
}
