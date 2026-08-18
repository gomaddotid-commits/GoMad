@extends('layouts.payment-agent')
@section('title', 'Kasir')
@push('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush
@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- LEFT: Scanner + Cart --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-[#111827]">🧾 Kasir</h1>
                <p class="text-sm text-gray-500 font-light">Scan barcode atau cari produk</p>
            </div>
            <div class="flex gap-2 text-sm">
                <span class="px-3 py-1.5 bg-green-50 text-green-700 rounded-lg font-medium border border-green-200">
                    {{ $todayStats['transactions'] }} tx hari ini
                </span>
                <span class="px-3 py-1.5 bg-[#BA1826]/5 text-[#BA1826] rounded-lg font-bold border border-[#BA1826]/20">
                    Rp {{ number_format($todayStats['revenue'], 0, ',', '.') }}
                </span>
            </div>
        </div>

        {{-- Barcode Input --}}
        <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6" x-data="scanner()">
            <div class="flex gap-3">
                <div class="flex-1 relative">
                    <input type="text" placeholder="Scan barcode / cari produk..." 
                           x-model="barcode"
                           @keydown.enter="scanBarcode()"
                           class="w-full px-4 py-3.5 rounded-xl border border-[#E5E7EB] bg-[#F9FAFB] text-lg focus:ring-2 focus:ring-[#BA1826] focus:border-[#BA1826] outline-none transition"
                           autofocus>
                    <button @click="barcode = ''; focus()" x-show="barcode" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <button @click="scanBarcode()" class="px-6 py-3.5 bg-[#BA1826] text-white rounded-xl font-semibold hover:bg-[#9A1420] transition flex items-center gap-2">
                    <span>🔍</span> Cari
                </button>
            </div>
            
            {{-- Error message --}}
            <div x-show="error" x-text="error" class="mt-3 text-sm text-red-600 bg-red-50 px-4 py-2 rounded-lg" x-cloak></div>
        </div>

        {{-- Cart --}}
        <div class="bg-white rounded-2xl border border-[#E5E7EB]" x-data="cart()">
            <div class="p-6 border-b border-[#E5E7EB]">
                <h2 class="font-bold text-lg text-[#111827]">🛒 Keranjang</h2>
                <p class="text-sm text-gray-400" x-text="items.length + ' item'"></p>
            </div>

            {{-- Cart items --}}
            <div class="divide-y divide-[#E5E7EB] max-h-[400px] overflow-y-auto">
                <template x-for="(item, i) in items" :key="i">
                    <div class="p-4 flex items-center justify-between hover:bg-[#F9FAFB] transition">
                        <div class="flex-1">
                            <p class="font-semibold text-[#111827]" x-text="item.name"></p>
                            <p class="text-sm text-gray-400">Rp <span x-text="item.price.toLocaleString('id-ID')"></span> × <span x-text="item.qty"></span></p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-1 bg-[#F3F4F6] rounded-lg">
                                <button @click="updateQty(i, -1)" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-[#BA1826] font-bold">−</button>
                                <span class="w-8 text-center font-bold text-sm" x-text="item.qty"></span>
                                <button @click="updateQty(i, 1)" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-[#BA1826] font-bold">+</button>
                            </div>
                            <p class="font-bold text-[#BA1826] w-24 text-right">Rp <span x-text="(item.price * item.qty).toLocaleString('id-ID')"></span></p>
                            <button @click="removeItem(i)" class="text-gray-300 hover:text-red-500">🗑️</button>
                        </div>
                    </div>
                </template>
                <div x-show="items.length === 0" class="p-12 text-center text-gray-400">
                    <div class="text-5xl mb-3">🛒</div>
                    <p class="font-light">Keranjang kosong</p>
                    <p class="text-sm">Scan barcode atau cari produk</p>
                </div>
            </div>

            {{-- Footer: Total + Payment --}}
            <div x-show="items.length > 0" class="p-6 border-t border-[#E5E7EB] space-y-4" x-data="{ paid: 0 }">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500">Subtotal (<span x-text="items.length"></span> item)</span>
                    <span class="text-xl font-bold text-[#111827]" x-text="'Rp ' + subtotal().toLocaleString('id-ID')"></span>
                </div>
                <div class="flex gap-3 items-center">
                    <label class="text-gray-500 text-sm whitespace-nowrap">Dibayar:</label>
                    <input type="number" x-model="paid" placeholder="Jumlah uang" 
                           class="flex-1 px-4 py-3 rounded-xl border border-[#E5E7EB] text-lg font-bold text-right focus:ring-2 focus:ring-green-500 outline-none">
                    <span class="text-sm text-gray-400">Cash</span>
                </div>
                <div x-show="paid > 0 && paid >= subtotal()" class="flex justify-between items-center bg-green-50 px-4 py-3 rounded-xl">
                    <span class="text-green-700 font-medium">Kembalian:</span>
                    <span class="text-xl font-bold text-green-700" x-text="'Rp ' + (paid - subtotal()).toLocaleString('id-ID')"></span>
                </div>
                <button @click="checkout(paid)" 
                        :disabled="paid < subtotal()"
                        class="w-full py-4 bg-green-600 text-white rounded-xl font-bold text-lg hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition">
                    💰 Bayar Sekarang
                </button>
            </div>
        </div>
    </div>

    {{-- RIGHT: Quick Products --}}
    <div class="space-y-6">
        <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6">
            <h3 class="font-bold text-lg text-[#111827] mb-4">📦 Produk Cepat</h3>
            <div class="space-y-2 max-h-[600px] overflow-y-auto">
                @foreach($products as $product)
                <button onclick="addToCart({{ $product->id }}, '{{ addslashes($product->name) }}', {{ $product->price }}, {{ $product->stock }})"
                        class="w-full text-left p-3 rounded-xl border border-[#E5E7EB] hover:border-[#BA1826] hover:bg-[#BA1826]/5 transition flex justify-between items-center {{ $product->stock <= 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                        {{ $product->stock <= 0 ? 'disabled' : '' }}>
                    <div>
                        <p class="font-semibold text-[#111827] text-sm">{{ $product->name }}</p>
                        <p class="text-xs text-gray-400">Stok: {{ $product->stock }}</p>
                    </div>
                    <span class="font-bold text-[#BA1826]">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                </button>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ─── Scanner ───
function scanner() {
    return {
        barcode: '',
        error: '',
        async scanBarcode() {
            if (!this.barcode.trim()) return;
            const code = this.barcode.trim();
            this.error = '';
            this.barcode = '';
            try {
                const r = await fetch(`/api/v1/payment-agent/products/barcode/${code}`, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
                const d = await r.json();
                if (d.success) {
                    addToCart(d.data.id, d.data.name, d.data.price, d.data.stock);
                } else {
                    this.error = d.message || 'Produk tidak ditemukan';
                }
            } catch (e) {
                this.error = 'Gagal scan. Coba lagi.';
            }
        }
    };
}

// ─── Cart ───
var cartItems = [];
var token = '{{ request()->user()->createToken("pos")->plainTextToken ?? "" }}';

function cart() {
    return {
        items: cartItems,
        subtotal() { return this.items.reduce((s, i) => s + (i.price * i.qty), 0); },
        updateQty(i, d) {
            this.items[i].qty += d;
            if (this.items[i].qty <= 0) this.items.splice(i, 1);
        },
        removeItem(i) { this.items.splice(i, 1); },
        async checkout(paid) {
            if (paid < this.subtotal()) return alert('Uang kurang!');
            try {
                const items = this.items.map(i => ({ product_id: i.id, qty: i.qty }));
                const r = await fetch('/api/v1/payment-agent/cashier/transactions', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                    body: JSON.stringify({ items, paid, payment_method: 'cash' })
                });
                const d = await r.json();
                if (d.success) {
                    alert(`✅ Transaksi sukses!\n${d.data.invoice_no}\nTotal: Rp ${d.data.total.toLocaleString('id-ID')}\nKembali: Rp ${d.data.change.toLocaleString('id-ID')}`);
                    this.items.splice(0);
                    location.reload();
                } else {
                    alert('❌ ' + d.message);
                }
            } catch (e) {
                alert('Gagal: ' + e.message);
            }
        }
    };
}

function addToCart(id, name, price, stock) {
    if (stock <= 0) { alert('Stok habis!'); return; }
    let item = cartItems.find(i => i.id === id);
    if (item) {
        if (item.qty >= stock) { alert('Stok tidak cukup!'); return; }
        item.qty++;
    } else {
        cartItems.push({ id, name, price, qty: 1, stock });
    }
}
</script>
@endpush
