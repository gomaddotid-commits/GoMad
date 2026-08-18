<?php

namespace App\Http\Controllers\Api\PaymentAgent;

use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    // Dashboard kasir (ringkasan hari ini)
    public function dashboard(Request $request): JsonResponse
    {
        $paymentAgent = $request->user()->paymentAgent;

        if (!$paymentAgent) {
            return response()->json(['success' => false, 'message' => 'Akun bukan payment agent.'], 403);
        }

        $today = now()->toDateString();

        $todayTransactions = PosTransaction::where('payment_agent_id', $paymentAgent->id)
            ->whereDate('created_at', $today)
            ->where('status', 'completed');

        return response()->json([
            'success' => true,
            'data' => [
                'total_transactions' => $todayTransactions->count(),
                'total_revenue' => (float) $todayTransactions->sum('total'),
                'today_date' => $today,
                'low_stock_products' => Product::where('payment_agent_id', $paymentAgent->id)
                    ->whereColumn('stock', '<=', 'min_stock')
                    ->count(),
            ],
        ]);
    }

    // Buat transaksi baru (kasir)
    public function store(Request $request): JsonResponse
    {
        $paymentAgent = $request->user()->paymentAgent;

        if (!$paymentAgent) {
            return response()->json(['success' => false, 'message' => 'Akun bukan payment agent.'], 403);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'discount' => 'nullable|numeric|min:0',
            'paid' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|in:cash,qris,transfer',
            'customer_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $discount = $validated['discount'] ?? 0;
        $items = [];
        $subtotal = 0;

        DB::beginTransaction();
        try {
            // Hitung subtotal & validasi stok
            foreach ($validated['items'] as $item) {
                $product = Product::where('id', $item['product_id'])
                    ->where('payment_agent_id', $paymentAgent->id)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Produk ID {$item['product_id']} tidak ditemukan.",
                    ], 404);
                }

                if ($product->stock < $item['qty']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stok {$product->name} tidak cukup. Tersedia: {$product->stock}",
                    ], 422);
                }

                $itemSubtotal = $product->price * $item['qty'];
                $subtotal += $itemSubtotal;
                $items[] = [
                    'product' => $product,
                    'qty' => $item['qty'],
                    'price' => $product->price,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $total = $subtotal - $discount;
            if ($validated['paid'] < $total) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Pembayaran kurang. Total: Rp {$total}, Dibayar: Rp {$validated['paid']}",
                ], 422);
            }

            // Buat transaksi
            $transaction = PosTransaction::create([
                'payment_agent_id' => $paymentAgent->id,
                'type' => 'sale',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid' => $validated['paid'],
                'change' => $validated['paid'] - $total,
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'customer_name' => $validated['customer_name'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Buat item transaksi & kurangi stok
            foreach ($items as $item) {
                $transaction->items()->create([
                    'product_id' => $item['product']->id,
                    'item_name' => $item['product']->name,
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                    'item_type' => 'product',
                ]);

                $item['product']->decreaseStock($item['qty'], $transaction->invoice_no);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil.',
                'data' => [
                    'invoice_no' => $transaction->invoice_no,
                    'total' => (float) $transaction->total,
                    'paid' => (float) $transaction->paid,
                    'change' => (float) $transaction->change,
                    'items_count' => count($items),
                    'created_at' => $transaction->created_at,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Riwayat transaksi
    public function history(Request $request): JsonResponse
    {
        $paymentAgent = $request->user()->paymentAgent;

        if (!$paymentAgent) {
            return response()->json(['success' => false, 'message' => 'Akun bukan payment agent.'], 403);
        }

        $transactions = PosTransaction::with('items')
            ->where('payment_agent_id', $paymentAgent->id)
            ->when($request->date, fn($q) => $q->whereDate('created_at', $request->date))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    // Detail transaksi
    public function show(Request $request, PosTransaction $transaction): JsonResponse
    {
        $paymentAgent = $request->user()->paymentAgent;

        if (!$paymentAgent || $transaction->payment_agent_id !== $paymentAgent->id) {
            return response()->json(['success' => false, 'message' => 'Transaksi tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction->load('items.product'),
        ]);
    }

    // Void transaksi (kembalikan stok)
    public function void(Request $request, PosTransaction $transaction): JsonResponse
    {
        $paymentAgent = $request->user()->paymentAgent;

        if (!$paymentAgent || $transaction->payment_agent_id !== $paymentAgent->id) {
            return response()->json(['success' => false, 'message' => 'Transaksi tidak ditemukan.'], 404);
        }

        if ($transaction->status === 'voided') {
            return response()->json(['success' => false, 'message' => 'Transaksi sudah di-void.'], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($transaction->items()->where('item_type', 'product')->get() as $item) {
                if ($item->product) {
                    $item->product->increaseStock($item->qty, "Void {$transaction->invoice_no}");
                }
            }
            $transaction->update(['status' => 'voided']);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil di-void. Stok dikembalikan.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal void transaksi: ' . $e->getMessage(),
            ], 500);
        }
    }
}
