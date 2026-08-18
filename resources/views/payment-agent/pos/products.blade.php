@extends('layouts.payment-agent')
@section('title', 'Produk')
@section('content')

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#111827]">📦 Kelola Produk</h1>
            <p class="text-sm text-gray-500 font-light">{{ $products->total() }} produk terdaftar</p>
        </div>
        <button onclick="document.getElementById('addProductModal').classList.remove('hidden')" 
                class="px-5 py-2.5 bg-[#BA1826] text-white rounded-xl font-semibold hover:bg-[#9A1420] transition">
            + Tambah Produk
        </button>
    </div>

    {{-- Search --}}
    <form class="mb-4">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari produk..." 
               class="w-full px-4 py-3 rounded-xl border border-[#E5E7EB] bg-white focus:ring-2 focus:ring-[#BA1826] outline-none">
    </form>

    {{-- Product List --}}
    <div class="bg-white rounded-2xl border border-[#E5E7EB] overflow-hidden">
        <table class="w-full">
            <thead>
                <tr class="bg-[#F9FAFB] text-left text-sm text-gray-500">
                    <th class="p-4 font-medium">Produk</th>
                    <th class="p-4 font-medium">Barcode</th>
                    <th class="p-4 font-medium text-right">Harga</th>
                    <th class="p-4 font-medium text-right">Modal</th>
                    <th class="p-4 font-medium text-center">Stok</th>
                    <th class="p-4 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#E5E7EB]">
                @forelse($products as $product)
                <tr class="hover:bg-[#F9FAFB] transition {{ $product->stock <= $product->min_stock ? 'bg-red-50' : '' }}">
                    <td class="p-4">
                        <p class="font-semibold text-[#111827]">{{ $product->name }}</p>
                        @if($product->stock <= $product->min_stock)
                            <span class="text-xs text-red-600 font-medium">⚠ Stok rendah!</span>
                        @endif
                    </td>
                    <td class="p-4 text-sm text-gray-400 font-mono">{{ $product->barcode ?? '-' }}</td>
                    <td class="p-4 text-right font-bold text-[#BA1826]">Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                    <td class="p-4 text-right text-sm text-gray-500">Rp {{ number_format($product->cost_price ?? 0, 0, ',', '.') }}</td>
                    <td class="p-4 text-center">
                        <span class="inline-block px-3 py-1 rounded-lg text-sm font-bold {{ $product->stock > 10 ? 'bg-green-100 text-green-700' : ($product->stock > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                            {{ $product->stock }}
                        </span>
                    </td>
                    <td class="p-4 text-right">
                        <button onclick="restockProduct({{ $product->id }}, '{{ addslashes($product->name) }}')" 
                                class="text-xs px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 mr-1">
                            + Restock
                        </button>
                        <button onclick="editProduct({{ $product->id }})" 
                                class="text-xs px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200">
                            Edit
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="p-12 text-center text-gray-400">Belum ada produk. Tambah produk pertama!</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>
</div>

{{-- ADD PRODUCT MODAL --}}
<div id="addProductModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <h3 class="text-lg font-bold mb-4">Tambah Produk Baru</h3>
        <form onsubmit="return addProduct(event)" class="space-y-3">
            <input type="text" name="name" placeholder="Nama produk" required class="w-full px-4 py-2.5 rounded-xl border border-[#E5E7EB]">
            <input type="text" name="barcode" placeholder="Barcode (opsional)" class="w-full px-4 py-2.5 rounded-xl border border-[#E5E7EB]">
            <div class="grid grid-cols-2 gap-3">
                <input type="number" name="price" placeholder="Harga jual" required class="px-4 py-2.5 rounded-xl border border-[#E5E7EB]">
                <input type="number" name="cost_price" placeholder="Harga modal" class="px-4 py-2.5 rounded-xl border border-[#E5E7EB]">
            </div>
            <input type="number" name="stock" placeholder="Stok awal" required class="w-full px-4 py-2.5 rounded-xl border border-[#E5E7EB]">
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('addProductModal').classList.add('hidden')" 
                        class="flex-1 py-2.5 border border-[#E5E7EB] rounded-xl font-medium">Batal</button>
                <button type="submit" class="flex-1 py-2.5 bg-[#BA1826] text-white rounded-xl font-semibold">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- RESTOCK MODAL --}}
<div id="restockModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm">
        <h3 class="text-lg font-bold mb-2">+ Restock</h3>
        <p id="restockProductName" class="text-gray-500 mb-4"></p>
        <form onsubmit="return doRestock(event)" class="space-y-3">
            <input type="hidden" id="restockProductId">
            <input type="number" name="qty" placeholder="Jumlah stok" required min="1" class="w-full px-4 py-2.5 rounded-xl border border-[#E5E7EB]">
            <input type="number" name="cost_price" placeholder="Harga modal baru (opsional)" class="w-full px-4 py-2.5 rounded-xl border border-[#E5E7EB]">
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('restockModal').classList.add('hidden')" 
                        class="flex-1 py-2.5 border border-[#E5E7EB] rounded-xl font-medium">Batal</button>
                <button type="submit" class="flex-1 py-2.5 bg-blue-600 text-white rounded-xl font-semibold">Restock</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
const token = '{{ request()->user()->createToken("pos")->plainTextToken }}';

async function addProduct(e) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));
    try {
        const r = await fetch('/api/v1/payment-agent/products', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
            body: JSON.stringify(data)
        });
        const d = await r.json();
        if (d.success) { location.reload(); }
        else { alert(d.message); }
    } catch (err) { alert('Gagal: ' + err.message); }
}

function restockProduct(id, name) {
    document.getElementById('restockProductId').value = id;
    document.getElementById('restockProductName').textContent = name;
    document.getElementById('restockModal').classList.remove('hidden');
}

async function doRestock(e) {
    e.preventDefault();
    const form = e.target;
    const id = document.getElementById('restockProductId').value;
    const data = Object.fromEntries(new FormData(form));
    try {
        const r = await fetch(`/api/v1/payment-agent/products/${id}/restock`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
            body: JSON.stringify(data)
        });
        const d = await r.json();
        if (d.success) { location.reload(); }
        else { alert(d.message); }
    } catch (err) { alert('Gagal: ' + err.message); }
}
</script>
@endpush
