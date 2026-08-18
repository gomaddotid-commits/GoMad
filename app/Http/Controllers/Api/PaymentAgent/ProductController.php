<?php

namespace App\Http\Controllers\Api\PaymentAgent;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Daftar produk warung
    public function index(Request $request): JsonResponse
    {
        $paymentAgent = $request->user()->paymentAgent;

        if (!$paymentAgent) {
            return response()->json(['success' => false, 'message' => 'Akun bukan payment agent.'], 403);
        }

        $products = Product::with('category')
            ->where('payment_agent_id', $paymentAgent->id)
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->low_stock, fn($q) => $q->whereColumn('stock', '<=', 'min_stock'))
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
            'meta' => [
                'total' => $products->count(),
                'low_stock' => $products->filter(fn($p) => $p->needsRestock())->count(),
            ],
        ]);
    }

    // Detail produk
    public function show(Request $request, Product $product): JsonResponse
    {
        $paymentAgent = $request->user()->paymentAgent;

        if (!$paymentAgent || $product->payment_agent_id !== $paymentAgent->id) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product->load('category', 'stockMovements'),
        ]);
    }

    // Tambah produk
    public function store(Request $request): JsonResponse
    {
        $paymentAgent = $request->user()->paymentAgent;

        if (!$paymentAgent) {
            return response()->json(['success' => false, 'message' => 'Akun bukan payment agent.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:100|unique:products,barcode',
            'sku' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'nullable|integer|min:1',
            'unit' => 'nullable|string|max:20',
            'category_id' => 'nullable|exists:product_categories,id',
            'image' => 'nullable|string',
        ]);

        $product = $paymentAgent->products()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan.',
            'data' => $product,
        ], 201);
    }

    // Update produk
    public function update(Request $request, Product $product): JsonResponse
    {
        $paymentAgent = $request->user()->paymentAgent;

        if (!$paymentAgent || $product->payment_agent_id !== $paymentAgent->id) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'barcode' => 'sometimes|nullable|string|max:100|unique:products,barcode,' . $product->id,
            'sku' => 'sometimes|nullable|string|max:50',
            'price' => 'sometimes|numeric|min:0',
            'cost_price' => 'sometimes|nullable|numeric|min:0',
            'min_stock' => 'sometimes|integer|min:1',
            'unit' => 'sometimes|nullable|string|max:20',
            'category_id' => 'sometimes|nullable|exists:product_categories,id',
            'image' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diupdate.',
            'data' => $product,
        ]);
    }

    // Hapus produk
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $paymentAgent = $request->user()->paymentAgent;

        if (!$paymentAgent || $product->payment_agent_id !== $paymentAgent->id) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus.',
        ]);
    }

    // Scan barcode → cari produk
    public function scanBarcode(Request $request, string $barcode): JsonResponse
    {
        $paymentAgent = $request->user()->paymentAgent;

        if (!$paymentAgent) {
            return response()->json(['success' => false, 'message' => 'Akun bukan payment agent.'], 403);
        }

        $product = Product::where('payment_agent_id', $paymentAgent->id)
            ->where('barcode', $barcode)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk dengan barcode ini tidak ditemukan.',
                'data' => ['barcode' => $barcode],
            ], 404);
        }

        if ($product->stock <= 0) {
            return response()->json([
                'success' => false,
                'message' => "Stok {$product->name} habis! Silakan restock.",
                'data' => ['product' => $product, 'stock' => 0],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'price' => (float) $product->price,
                'stock' => $product->stock,
                'unit' => $product->unit,
            ],
        ]);
    }

    // Restock produk
    public function restock(Request $request, Product $product): JsonResponse
    {
        $paymentAgent = $request->user()->paymentAgent;

        if (!$paymentAgent || $product->payment_agent_id !== $paymentAgent->id) {
            return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'qty' => 'required|integer|min:1',
            'cost_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $product->increaseStock($validated['qty'], $validated['notes'] ?? 'Restock manual');

        if (isset($validated['cost_price'])) {
            $product->update(['cost_price' => $validated['cost_price']]);
        }

        return response()->json([
            'success' => true,
            'message' => "Stok {$product->name} bertambah {$validated['qty']}. Total: {$product->stock}",
            'data' => ['stock_now' => $product->stock],
        ]);
    }
}
