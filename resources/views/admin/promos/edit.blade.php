@extends('layouts.admin')

@section('title', 'Edit Promo')
@section('content')
<div class="max-w-3xl">
    <h1 class="text-2xl font-bold text-[#111111] mb-6">Edit Promo</h1>

    @php 
        $methods = $promo->applicable_payment_methods;
        
        if (is_string($methods)) {
            $selectedMethods = !empty($methods) ? explode(',', $methods) : [];
        } elseif (is_array($methods)) {
            $selectedMethods = $methods;
        } else {
            $selectedMethods = [];
        }
    @endphp

    <form action="{{ route('admin.promos.update', $promo) }}" method="POST" class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm space-y-6">
        @csrf @method('PUT')
        
        {{-- Nama & Jenis --}}
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nama Promo <span class="text-[#C1121F]">*</span></label>
                <input type="text" name="name" value="{{ old('name', $promo->name) }}" 
                       class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
            </div>
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Jenis Promo</label>
                <select name="type" id="promoType" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required onchange="togglePromoType()">
                    <option value="general" {{ old('type', $promo->type) == 'general' ? 'selected' : '' }}>🌍 General (Semua Customer)</option>
                    <option value="selective" {{ old('type', $promo->type) == 'selective' ? 'selected' : '' }}>🎯 Selektif (Agency Pilih)</option>
                </select>
            </div>
        </div>

        {{-- MODUL --}}
        <div>
            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Modul <span class="text-[#C1121F]">*</span></label>
            <select name="module" id="promoModule" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" onchange="toggleModuleFields()">
                <option value="travel" {{ old('module', $promo->module) == 'travel' ? 'selected' : '' }}>🚐 Travel (Jadwal & Booking)</option>
                <option value="rental" {{ old('module', $promo->module) == 'rental' ? 'selected' : '' }}>🚗 Rental (Sewa Mobil)</option>
                <option value="all" {{ old('module', $promo->module) == 'all' ? 'selected' : '' }}>🌍 Semua Modul (Travel + Rental)</option>
            </select>
            <p class="text-[10px] text-gray-400 mt-1 font-light">Pilih modul mana promo ini berlaku</p>
        </div>

        {{-- Deskripsi --}}
        <div>
            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Deskripsi</label>
            <textarea name="description" rows="2" 
                      class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">{{ old('description', $promo->description) }}</textarea>
        </div>

        {{-- ═══════════════════════════════════════ --}}
        {{-- DISKON TRAVEL (muncul jika module = travel atau all) --}}
        {{-- ═══════════════════════════════════════ --}}
        <div id="travelDiscountFields">
            <div class="border-t border-[#E5E5E5] pt-4">
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">🚐 Diskon Travel</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Diskon (%)</label>
                        <input type="number" name="discount_percent" value="{{ old('discount_percent', $promo->discount_percent) }}" 
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" min="1" max="100">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Maks Diskon (Rp)</label>
                        <input type="number" name="max_discount" value="{{ old('max_discount', $promo->max_discount) }}" 
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Min Pembelian (Rp)</label>
                        <input type="number" name="min_purchase" value="{{ old('min_purchase', $promo->min_purchase) }}" 
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
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Tipe Diskon</label>
                        <select name="rental_discount_type" id="rentalDiscountType" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                            <option value="percent" {{ old('rental_discount_type', $promo->rental_discount_type) == 'percent' ? 'selected' : '' }}>Persentase (%)</option>
                            <option value="fixed" {{ old('rental_discount_type', $promo->rental_discount_type) == 'fixed' ? 'selected' : '' }}>Nominal Tetap (Rp)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                            <span id="rentalDiscountLabel">
                                {{ ($promo->rental_discount_type ?? 'percent') == 'fixed' ? 'Jumlah Diskon (Rp)' : 'Jumlah Diskon (%)' }}
                            </span>
                        </label>
                        <input type="number" name="rental_discount_amount" value="{{ old('rental_discount_amount', $promo->rental_discount_amount) }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                               placeholder="{{ ($promo->rental_discount_type ?? 'percent') == 'fixed' ? '75000' : '10' }}" min="0">
                        <p class="text-[10px] text-gray-400 mt-1 font-light" id="rentalDiscountHint">
                            {{ ($promo->rental_discount_type ?? 'percent') == 'fixed' ? 'Nominal potongan tetap' : 'Persentase diskon (1-100%)' }}
                        </p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Maks Diskon (Rp)</label>
                        <input type="number" name="rental_max_discount" value="{{ old('rental_max_discount', $promo->rental_max_discount) }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                               placeholder="100000" min="0">
                        <p class="text-[10px] text-gray-400 mt-1 font-light">Untuk tipe Persentase</p>
                    </div>
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

        {{-- Selective Target --}}
        <div id="selectiveTarget" style="display: none;" class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Target Rute</label>
                <select name="route_id" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                    <option value="">Semua Rute</option>
                    @foreach(\App\Models\Route::where('is_active', true)->get() as $route)
                    <option value="{{ $route->id }}" {{ old('route_id', $promo->route_id) == $route->id ? 'selected' : '' }}>{{ $route->route_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Target Kelas</label>
                <select name="travel_class" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                    <option value="">Semua</option>
                    <option value="economy" {{ old('travel_class', $promo->travel_class) == 'economy' ? 'selected' : '' }}>Ekonomi</option>
                    <option value="premium" {{ old('travel_class', $promo->travel_class) == 'premium' ? 'selected' : '' }}>Premium</option>
                    <option value="charter" {{ old('travel_class', $promo->travel_class) == 'charter' ? 'selected' : '' }}>Charter</option>
                </select>
            </div>
        </div>

        {{-- Metode Pembayaran --}}
        <div>
            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-2">Berlaku untuk Metode Pembayaran</label>
            <p class="text-[10px] text-gray-400 mb-3 font-light">Centang metode yang berlaku. Kosongkan semua untuk berlaku di semua metode.</p>
            <div class="grid grid-cols-3 gap-3">
                <label class="flex items-center gap-2 p-3 border border-[#E5E5E5] rounded-[12px] cursor-pointer hover:bg-[#F5F5F5]">
                    <input type="checkbox" name="applicable_payment_methods[]" value="midtrans" class="w-4 h-4 text-[#C1121F] rounded border-[#E5E5E5] focus:ring-[#C1121F]"
                        {{ in_array('midtrans', $selectedMethods) ? 'checked' : '' }}>
                    <div><span class="text-sm font-medium text-[#111111]">💳 Online</span><span class="text-[10px] text-gray-400 block font-light">Midtrans</span></div>
                </label>
                <label class="flex items-center gap-2 p-3 border border-[#E5E5E5] rounded-[12px] cursor-pointer hover:bg-[#F5F5F5]">
                    <input type="checkbox" name="applicable_payment_methods[]" value="cash" class="w-4 h-4 text-[#C1121F] rounded border-[#E5E5E5] focus:ring-[#C1121F]"
                        {{ in_array('cash', $selectedMethods) ? 'checked' : '' }}>
                    <div><span class="text-sm font-medium text-[#111111]">🏪 Warung</span><span class="text-[10px] text-gray-400 block font-light">Warung GoMad</span></div>
                </label>
                <label class="flex items-center gap-2 p-3 border border-[#E5E5E5] rounded-[12px] cursor-pointer hover:bg-[#F5F5F5]">
                    <input type="checkbox" name="applicable_payment_methods[]" value="cod" class="w-4 h-4 text-[#C1121F] rounded border-[#E5E5E5] focus:ring-[#C1121F]"
                        {{ in_array('cod', $selectedMethods) ? 'checked' : '' }}>
                    <div><span class="text-sm font-medium text-[#111111]">🚗 COD</span><span class="text-[10px] text-gray-400 block font-light">Bayar ke Sopir</span></div>
                </label>
            </div>
            <p class="text-[10px] text-gray-400 mt-2 font-light">Jika tidak ada yang dicentang, promo berlaku untuk semua metode pembayaran.</p>
        </div>

        {{-- Periode --}}
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Tanggal Mulai <span class="text-[#C1121F]">*</span></label>
                <input type="date" name="start_date" value="{{ old('start_date', $promo->start_date->format('Y-m-d')) }}" 
                       class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
            </div>
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Tanggal Selesai <span class="text-[#C1121F]">*</span></label>
                <input type="date" name="end_date" value="{{ old('end_date', $promo->end_date->format('Y-m-d')) }}" 
                       class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
            </div>
        </div>

        {{-- Status --}}
        <div>
            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Status</label>
            <select name="is_active" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                <option value="1" {{ old('is_active', $promo->is_active) ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ !old('is_active', $promo->is_active) ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>

        <button type="submit" class="btn-gomad-primary w-full py-3 rounded-[12px] font-semibold text-base">
            💾 UPDATE PROMO
        </button>
    </form>
</div>

@push('scripts')
<script>
function togglePromoType() {
    var type = document.getElementById('promoType').value;
    var module = document.getElementById('promoModule').value;
    var selectiveTarget = document.getElementById('selectiveTarget');
    
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
    var type = document.getElementById('promoType').value;
    var selectiveTarget = document.getElementById('selectiveTarget');
    
    // Travel discount fields
    travelFields.style.display = (module === 'travel' || module === 'all') ? 'block' : 'none';
    
    // Rental discount fields
    rentalFields.style.display = (module === 'rental' || module === 'all') ? 'block' : 'none';
    
    // Selective target
    if (type === 'selective' && module !== 'rental') {
        selectiveTarget.style.display = 'grid';
    } else {
        selectiveTarget.style.display = 'none';
    }
}

// Update label saat tipe diskon rental berubah
document.getElementById('rentalDiscountType').addEventListener('change', function() {
    var label = document.getElementById('rentalDiscountLabel');
    var hint = document.getElementById('rentalDiscountHint');
    
    if (this.value === 'fixed') {
        label.textContent = 'Jumlah Diskon (Rp)';
        hint.textContent = 'Nominal potongan tetap';
    } else {
        label.textContent = 'Jumlah Diskon (%)';
        hint.textContent = 'Persentase diskon (1-100%)';
    }
});

// Init
document.addEventListener('DOMContentLoaded', function() {
    toggleModuleFields();
    
    // Trigger change untuk update label rental
    var rentalType = document.getElementById('rentalDiscountType');
    if (rentalType) {
        rentalType.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
@endsection