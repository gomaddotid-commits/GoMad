@extends('layouts.admin')

@section('title', 'Buat Promo')
@section('content')
<div class="max-w-3xl">
    <h1 class="text-2xl font-bold text-[#111111] mb-6">Buat Promo Baru</h1>

    <form action="{{ route('admin.promos.store') }}" method="POST" class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm space-y-6">
        @csrf
        
        {{-- Nama & Jenis --}}
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nama Promo <span class="text-[#C1121F]">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" 
                       class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" 
                       placeholder="Flash Sale Lebaran" required>
            </div>
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Jenis Promo <span class="text-[#C1121F]">*</span></label>
                <select name="type" id="promoType" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required onchange="togglePromoType()">
                    <option value="general" {{ old('type') == 'general' ? 'selected' : '' }}>🌍 General (Semua Customer)</option>
                    <option value="selective" {{ old('type') == 'selective' ? 'selected' : '' }}>🎯 Selektif (Agency Pilih)</option>
                </select>
            </div>
        </div>

        {{-- MODUL --}}
        <div>
            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Modul <span class="text-[#C1121F]">*</span></label>
            <select name="module" id="promoModule" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" onchange="toggleModuleFields()">
                <option value="travel" {{ old('module') == 'travel' ? 'selected' : '' }}>🚐 Travel (Jadwal & Booking)</option>
                <option value="rental" {{ old('module') == 'rental' ? 'selected' : '' }}>🚗 Rental (Sewa Mobil)</option>
                <option value="all" {{ old('module') == 'all' ? 'selected' : '' }}>🌍 Semua Modul (Travel + Rental)</option>
            </select>
            <p class="text-[10px] text-gray-400 mt-1 font-light">Pilih modul mana promo ini berlaku</p>
        </div>

        {{-- Deskripsi --}}
        <div>
            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Deskripsi</label>
            <textarea name="description" rows="2" 
                      class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                      placeholder="Deskripsi singkat promo...">{{ old('description') }}</textarea>
        </div>

        {{-- ═══════════════════════════════════════ --}}
        {{-- DISKON TRAVEL (muncul jika module = travel atau all) --}}
        {{-- ═══════════════════════════════════════ --}}
        <div id="travelDiscountFields">
            <div class="border-t border-[#E5E5E5] pt-4">
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">🚐 Diskon Travel</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Diskon (%) <span class="text-[#C1121F]">*</span></label>
                        <input type="number" name="discount_percent" value="{{ old('discount_percent') }}" 
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" 
                               min="1" max="100">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Maks Diskon (Rp)</label>
                        <input type="number" name="max_discount" value="{{ old('max_discount', 50000) }}" 
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Min Pembelian (Rp)</label>
                        <input type="number" name="min_purchase" value="{{ old('min_purchase', 0) }}" 
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════ --}}
        {{-- DISKON RENTAL (muncul jika module = rental atau all) --}}
        {{-- ═══════════════════════════════════════ --}}
        <div id="rentalDiscountFields">
            <div class="border-t border-[#E5E5E5] pt-4">
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">🚗 Diskon Rental</h3>
                
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Tipe Diskon <span class="text-[#C1121F]">*</span></label>
                        <select name="rental_discount_type" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                            <option value="percent" {{ old('rental_discount_type') == 'percent' ? 'selected' : '' }}>Persentase (%)</option>
                            <option value="fixed" {{ old('rental_discount_type') == 'fixed' ? 'selected' : '' }}>Nominal Tetap (Rp)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                            <span id="rentalDiscountLabel">Jumlah Diskon (%)</span>
                            <span class="text-[#C1121F]">*</span>
                        </label>
                        <input type="number" name="rental_discount_amount" value="{{ old('rental_discount_amount') }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                               placeholder="10" min="0" id="rentalDiscountInput">
                        <p class="text-[10px] text-gray-400 mt-1 font-light" id="rentalDiscountHint">Persentase diskon (1-100%)</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Maks Diskon (Rp)</label>
                        <input type="number" name="rental_max_discount" value="{{ old('rental_max_discount') }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                               placeholder="100000" min="0">
                        <p class="text-[10px] text-gray-400 mt-1 font-light">Untuk tipe Persentase</p>
                    </div>
                </div>
                
                <div class="mt-3">
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Minimal Sewa (Rp)</label>
                    <input type="number" name="min_purchase" value="{{ old('min_purchase', 0) }}" 
                           class="w-48 px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                           placeholder="0" min="0">
                    <p class="text-[10px] text-gray-400 mt-1 font-light">Minimal subtotal sewa agar promo berlaku</p>
                </div>
                
                <div class="bg-blue-50 border border-blue-200 rounded-[12px] p-4 mt-4">
                    <p class="text-xs text-blue-700 font-light">
                        💡 <strong>Contoh:</strong> 
                        <br>• Tipe Persentase 10%, Maks Rp 100.000 → Sewa Rp 500.000 = diskon Rp 50.000
                        <br>• Tipe Nominal Rp 75.000 → Sewa berapapun = diskon Rp 75.000
                    </p>
                </div>
            </div>
        </div>

        {{-- Selective Target (hanya untuk module travel/all) --}}
        <div id="selectiveTarget" style="display: none;" class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Target Rute</label>
                <select name="route_id" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                    <option value="">Semua Rute</option>
                    @foreach(\App\Models\Route::where('is_active', true)->get() as $route)
                    <option value="{{ $route->id }}" {{ old('route_id') == $route->id ? 'selected' : '' }}>{{ $route->route_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Target Kelas</label>
                <select name="travel_class" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                    <option value="">Semua</option>
                    <option value="economy" {{ old('travel_class') == 'economy' ? 'selected' : '' }}>Ekonomi</option>
                    <option value="premium" {{ old('travel_class') == 'premium' ? 'selected' : '' }}>Premium</option>
                    <option value="charter" {{ old('travel_class') == 'charter' ? 'selected' : '' }}>Charter</option>
                </select>
            </div>
        </div>

        {{-- Metode Pembayaran --}}
        <div>
            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-2">Berlaku untuk Metode Pembayaran</label>
            <p class="text-[10px] text-gray-400 mb-3 font-light">Centang metode yang berlaku. Kosongkan semua untuk berlaku di semua metode.</p>
            <div class="grid grid-cols-3 gap-3">
                <label class="flex items-center gap-2 p-3 border border-[#E5E5E5] rounded-[12px] cursor-pointer hover:bg-[#F5F5F5]">
                    <input type="checkbox" name="applicable_payment_methods[]" value="midtrans" class="w-4 h-4 text-[#C1121F] rounded border-[#E5E5E5] focus:ring-[#C1121F]">
                    <div><span class="text-sm font-medium text-[#111111]">💳 Online</span><span class="text-[10px] text-gray-400 block font-light">Midtrans</span></div>
                </label>
                <label class="flex items-center gap-2 p-3 border border-[#E5E5E5] rounded-[12px] cursor-pointer hover:bg-[#F5F5F5]">
                    <input type="checkbox" name="applicable_payment_methods[]" value="cash" class="w-4 h-4 text-[#C1121F] rounded border-[#E5E5E5] focus:ring-[#C1121F]">
                    <div><span class="text-sm font-medium text-[#111111]">🏪 Warung</span><span class="text-[10px] text-gray-400 block font-light">Warung GoMad</span></div>
                </label>
                <label class="flex items-center gap-2 p-3 border border-[#E5E5E5] rounded-[12px] cursor-pointer hover:bg-[#F5F5F5]">
                    <input type="checkbox" name="applicable_payment_methods[]" value="cod" class="w-4 h-4 text-[#C1121F] rounded border-[#E5E5E5] focus:ring-[#C1121F]">
                    <div><span class="text-sm font-medium text-[#111111]">🚗 COD</span><span class="text-[10px] text-gray-400 block font-light">Bayar ke Sopir</span></div>
                </label>
            </div>
            <p class="text-[10px] text-gray-400 mt-2 font-light">Jika tidak ada yang dicentang, promo berlaku untuk semua metode pembayaran.</p>
        </div>

        {{-- Periode --}}
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Tanggal Mulai <span class="text-[#C1121F]">*</span></label>
                <input type="date" name="start_date" value="{{ old('start_date') }}" 
                       class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
            </div>
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Tanggal Selesai <span class="text-[#C1121F]">*</span></label>
                <input type="date" name="end_date" value="{{ old('end_date') }}" 
                       class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
            </div>
        </div>

        {{-- Cost Bearer --}}
        <div>
            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Penanggung Biaya <span class="text-[#C1121F]">*</span></label>
            <select name="cost_bearer" id="costBearer" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required onchange="toggleShare()">
                <option value="platform" {{ old('cost_bearer') == 'platform' ? 'selected' : '' }}>Platform (100%)</option>
                <option value="agency" {{ old('cost_bearer') == 'agency' ? 'selected' : '' }}>Agency (100%)</option>
                <option value="shared" {{ old('cost_bearer') == 'shared' ? 'selected' : '' }}>Shared</option>
            </select>
        </div>
        
        <div id="sharePercent" style="display: none;" class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Platform (%)</label>
                <input type="number" name="platform_share" value="{{ old('platform_share', 50) }}" 
                       class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
            </div>
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Agency (%)</label>
                <input type="number" name="agency_share" value="{{ old('agency_share', 50) }}" 
                       class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
            </div>
        </div>

        <button type="submit" class="btn-gomad-primary w-full py-3 rounded-[12px] font-semibold text-base">
            💾 SIMPAN PROMO
        </button>
    </form>
</div>

@push('scripts')
<script>
function togglePromoType() {
    var type = document.getElementById('promoType').value;
    var selectiveTarget = document.getElementById('selectiveTarget');
    var module = document.getElementById('promoModule').value;
    
    // Selective target hanya muncul untuk travel/all + selective
    if (type === 'selective' && module !== 'rental') {
        selectiveTarget.style.display = 'grid';
    } else {
        selectiveTarget.style.display = 'none';
    }
}

function toggleModuleFields() {
    var module = document.getElementById('promoModule').value;
    var travelFields = document.getElementById('travelDiscountFields');
    var rentalFields = document.getElementById('rentalDiscountFields');
    var selectiveTarget = document.getElementById('selectiveTarget');
    var type = document.getElementById('promoType').value;
    
    // Travel discount fields
    if (module === 'travel' || module === 'all') {
        travelFields.style.display = 'block';
    } else {
        travelFields.style.display = 'none';
    }
    
    // Rental discount fields
    if (module === 'rental' || module === 'all') {
        rentalFields.style.display = 'block';
    } else {
        rentalFields.style.display = 'none';
    }
    
    // Selective target
    if (type === 'selective' && module !== 'rental') {
        selectiveTarget.style.display = 'grid';
    } else {
        selectiveTarget.style.display = 'none';
    }
}

function toggleShare() {
    document.getElementById('sharePercent').style.display = 
        document.getElementById('costBearer').value === 'shared' ? 'grid' : 'none';
}

// Update label saat tipe diskon berubah
document.querySelector('select[name="rental_discount_type"]').addEventListener('change', function() {
    var label = document.getElementById('rentalDiscountLabel');
    var hint = document.getElementById('rentalDiscountHint');
    var input = document.getElementById('rentalDiscountInput');
    
    if (this.value === 'fixed') {
        label.textContent = 'Jumlah Diskon (Rp)';
        hint.textContent = 'Nominal potongan tetap';
        input.placeholder = '75000';
    } else {
        label.textContent = 'Jumlah Diskon (%)';
        hint.textContent = 'Persentase diskon (1-100%)';
        input.placeholder = '10';
    }
});

// Init
document.addEventListener('DOMContentLoaded', function() {
    toggleModuleFields();
    toggleShare();
});
</script>
@endpush
@endsection