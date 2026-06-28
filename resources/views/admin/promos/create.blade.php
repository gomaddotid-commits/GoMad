@extends('layouts.admin')

@section('title', 'Buat Promo')
@section('content')
<div class="max-w-3xl">
    <h1 class="text-2xl font-bold text-secondary mb-6">Buat Promo Baru</h1>

    <form action="{{ route('admin.promos.store') }}" method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
        @csrf
        
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Nama Promo</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" placeholder="Flash Sale Lebaran" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Jenis Promo</label>
                <select name="type" id="promoType" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" required onchange="togglePromoType()">
                    <option value="general">🌍 General (Semua Customer)</option>
                    <option value="selective">🎯 Selektif (Agency Pilih)</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">Deskripsi</label>
            <textarea name="description" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50">{{ old('description') }}</textarea>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div><label class="block text-sm font-medium text-secondary mb-1">Diskon (%)</label><input type="number" name="discount_percent" value="{{ old('discount_percent') }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50" min="1" max="100" required></div>
            <div><label class="block text-sm font-medium text-secondary mb-1">Maks Diskon (Rp)</label><input type="number" name="max_discount" value="{{ old('max_discount', 50000) }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50" required></div>
            <div><label class="block text-sm font-medium text-secondary mb-1">Min Pembelian (Rp)</label><input type="number" name="min_purchase" value="{{ old('min_purchase', 0) }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50"></div>
        </div>

        <div id="selectiveTarget" style="display:none;" class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Target Rute</label>
                <select name="route_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50">
                    <option value="">Semua Rute</option>
                    @foreach(\App\Models\Route::where('is_active', true)->get() as $route)
                    <option value="{{ $route->id }}">{{ $route->route_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Target Kelas</label>
                <select name="travel_class" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50">
                    <option value="">Semua</option>
                    <option value="economy">Ekonomi</option>
                    <option value="premium">Premium</option>
                    <option value="charter">Charter</option>
                </select>
            </div>
        </div>

        {{-- Metode Pembayaran --}}
        <div>
            <label class="block text-sm font-medium text-secondary mb-2">Berlaku untuk Metode Pembayaran</label>
            <p class="text-xs text-gray-500 mb-3">Centang metode yang berlaku. Kosongkan semua untuk berlaku di semua metode.</p>
            <div class="grid grid-cols-3 gap-3">
                <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" name="applicable_payment_methods[]" value="midtrans" class="w-4 h-4 text-primary-600 rounded">
                    <div><span class="text-sm font-medium">💳 Online</span><span class="text-xs text-gray-500 block">Midtrans</span></div>
                </label>
                <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" name="applicable_payment_methods[]" value="cash" class="w-4 h-4 text-primary-600 rounded">
                    <div><span class="text-sm font-medium">🏪 Warung</span><span class="text-xs text-gray-500 block">Warung GoMad</span></div>
                </label>
                <label class="flex items-center gap-2 p-3 border border-gray-200 rounded-xl cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" name="applicable_payment_methods[]" value="cod" class="w-4 h-4 text-primary-600 rounded">
                    <div><span class="text-sm font-medium">🚗 COD</span><span class="text-xs text-gray-500 block">Bayar ke Sopir</span></div>
                </label>
            </div>
            <p class="text-xs text-gray-400 mt-2">Jika tidak ada yang dicentang, promo berlaku untuk semua metode pembayaran.</p>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-secondary mb-1">Tanggal Mulai</label><input type="date" name="start_date" value="{{ old('start_date') }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50" required></div>
            <div><label class="block text-sm font-medium text-secondary mb-1">Tanggal Selesai</label><input type="date" name="end_date" value="{{ old('end_date') }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50" required></div>
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">Penanggung Biaya</label>
            <select name="cost_bearer" id="costBearer" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50" required onchange="toggleShare()">
                <option value="platform">Platform (100%)</option>
                <option value="agency">Agency (100%)</option>
                <option value="shared">Shared</option>
            </select>
        </div>
        <div id="sharePercent" style="display:none;" class="grid grid-cols-2 gap-4">
            <div><label class="block text-sm font-medium text-secondary mb-1">Platform (%)</label><input type="number" name="platform_share" value="50" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50"></div>
            <div><label class="block text-sm font-medium text-secondary mb-1">Agency (%)</label><input type="number" name="agency_share" value="50" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50"></div>
        </div>

        <button type="submit" class="btn-primary">Simpan Promo</button>
    </form>
</div>

@push('scripts')
<script>
function togglePromoType() { document.getElementById('selectiveTarget').style.display = document.getElementById('promoType').value === 'selective' ? 'grid' : 'none'; }
function toggleShare() { document.getElementById('sharePercent').style.display = document.getElementById('costBearer').value === 'shared' ? 'grid' : 'none'; }
</script>
@endpush
@endsection