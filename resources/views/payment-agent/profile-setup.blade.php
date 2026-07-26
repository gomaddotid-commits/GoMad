@extends('layouts.payment-agent')

@section('title', 'Lengkapi Profil Warung')
@section('content')
@php 
    $agent = auth()->user()->paymentAgent; 
    $provinces = \App\Models\Province::orderBy('name')->get();
    
    // ⚡ PRELOAD: Siapkan data cities & districts untuk initial load
    $preloadedCities = [];
    $preloadedDistricts = [];
    
    $selectedProvince = old('province_code', $agent->province_code ?? '');
    $selectedCity = old('city_code', $agent->city_code ?? '');
    
    if ($selectedProvince) {
        $preloadedCities = \App\Models\City::where('province_code', $selectedProvince)
            ->orderBy('name')
            ->get(['code', 'name'])
            ->toArray();
    }
    
    if ($selectedCity) {
        $preloadedDistricts = \App\Models\District::where('city_code', $selectedCity)
            ->orderBy('name')
            ->get(['code', 'name'])
            ->toArray();
    }
@endphp

<div class="max-w-2xl mx-auto">
    <div class="text-center mb-8">
        <div class="w-20 h-20 bg-[#C1121F]/5 rounded-[12px] flex items-center justify-center mx-auto mb-4 border border-[#E5E5E5]">
            <span class="text-3xl">🏪</span>
        </div>
        <h1 class="text-2xl font-bold text-[#111111] mb-2">{{ $agent && $agent->agent_name ? 'Setup Ulang Profil Warung' : 'Lengkapi Profil Warung' }}</h1>
        <p class="text-gray-500 font-light">{{ $agent && $agent->agent_name ? 'Perbaiki data sesuai catatan penolakan' : 'Isi data warung Anda untuk menjadi mitra GoMad' }}</p>
    </div>

    @if($agent && $agent->rejection_reason)
    <div class="bg-red-50 border border-red-200 rounded-[12px] p-4 mb-6">
        <p class="text-sm font-medium text-red-800">❌ Alasan Penolakan Sebelumnya:</p>
        <p class="text-sm text-red-700 mt-1 font-light">{{ $agent->rejection_reason }}</p>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[12px] mb-6 text-sm">
        <p class="font-medium mb-1">⚠️ Terjadi kesalahan:</p>
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-4 mb-6 text-sm text-yellow-800 font-light">
        <strong class="font-medium">Semua field wajib diisi</strong> kecuali yang bertanda opsional.
    </div>

    <form action="{{ route('payment-agent.setup.save') }}" method="POST" class="space-y-6">
        @csrf
        
        {{-- Informasi Warung --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">Informasi Warung</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nama Warung <span class="text-[#C1121F]">*</span></label>
                    <input type="text" name="agent_name" value="{{ old('agent_name', $agent->agent_name ?? '') }}" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">PIN Konfirmasi (6 digit) <span class="text-[#C1121F]">*</span></label>
                    <input type="password" name="pin" maxlength="6" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-center text-lg tracking-widest text-[#111111] transition" placeholder="******" required>
                    <p class="text-[10px] text-gray-400 mt-1 font-light">PIN 6 digit digunakan untuk konfirmasi setiap pembayaran</p>
                </div>
            </div>
        </div>

        {{-- LOKASI WARUNG (LARAVOLT) --}}
        <div class="bg-white border-2 border-[#C1121F] rounded-[12px] p-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#C1121F] mb-4">📍 Lokasi Warung <span class="text-[#C1121F]">*</span></h3>
            
            <div x-data="locationSelect()" class="space-y-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Provinsi <span class="text-[#C1121F]">*</span></label>
                    <select name="province_code" x-model="province" @change="loadCities()" 
                            class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                        <option value="">Pilih Provinsi</option>
                        @foreach($provinces as $p)
                        <option value="{{ $p->code }}" {{ old('province_code', $agent->province_code ?? '') == $p->code ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kabupaten/Kota <span class="text-[#C1121F]">*</span></label>
                    <select name="city_code" x-model="city" @change="loadDistricts()" :disabled="!province"
                            class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                        <option value="">Pilih Kab/Kota</option>
                        <template x-for="c in cities" :key="c.code">
                            <option :value="c.code" x-text="c.name" :selected="c.code === '{{ old('city_code', $agent->city_code ?? '') }}'"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kecamatan</label>
                    <select name="district_code" x-model="district" :disabled="!city"
                            class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                        <option value="">Pilih Kecamatan</option>
                        <template x-for="d in districts" :key="d.code">
                            <option :value="d.code" x-text="d.name" :selected="d.code === '{{ old('district_code', $agent->district_code ?? '') }}'"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Alamat Lengkap <span class="text-[#C1121F]">*</span></label>
                    <textarea name="address" rows="2" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>{{ old('address', $agent->address ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Link Google Maps</label>
                    <input type="url" name="maps_link" value="{{ old('maps_link', $agent->maps_link ?? '') }}" 
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                           placeholder="https://maps.app.goo.gl/...">
                </div>
            </div>
        </div>

        {{-- Kontak --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm" x-data="contactForm()">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">📞 Kontak</h3>
            
            <div class="space-y-4">
                {{-- Nama Pemilik --}}
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nama Pemilik <span class="text-[#C1121F]">*</span></label>
                    <input type="text" name="owner_name" value="{{ old('owner_name', $agent->owner_name ?? auth()->user()->name) }}" 
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                </div>

                {{-- Nomor WhatsApp Pemilik (WAJIB) --}}
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                        Nomor WhatsApp Pemilik <span class="text-[#C1121F]">*</span>
                    </label>
                    <input type="text" name="owner_phone" x-model="whatsapp" 
                           value="{{ old('owner_phone', $agent->owner_phone ?? auth()->user()->phone ?? '') }}"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" 
                           placeholder="081234567890" required>
                    <p class="text-[10px] text-gray-400 mt-1 font-light">Notifikasi settlement akan dikirim ke nomor ini</p>
                </div>
            </div>
        </div>

        {{-- Informasi Penjaga (Opsional) --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm" x-data="guardForm()">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">Informasi Penjaga <span class="text-sm font-normal text-gray-400 font-light">(Opsional)</span></h3>
            
            {{-- Checkbox: Ada penjaga --}}
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4 mb-4">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" x-model="hasGuard" 
                           class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]"
                           {{ old('guard_name', $agent->guard_name ?? '') ? 'checked' : '' }}>
                    <div>
                        <span class="text-sm font-medium text-[#111111]">Warung ini memiliki penjaga</span>
                        <p class="text-xs text-gray-500 font-light mt-0.5">Centang jika ada karyawan yang menjaga warung</p>
                    </div>
                </label>
            </div>

            {{-- Field Penjaga (muncul jika checkbox dicentang) --}}
            <div x-show="hasGuard" x-transition>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nama Penjaga</label>
                        <input type="text" name="guard_name" 
                               value="{{ old('guard_name', $agent->guard_name ?? '') }}" 
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nomor HP Penjaga</label>
                        <input type="text" name="guard_phone" 
                               value="{{ old('guard_phone', $agent->guard_phone ?? '') }}" 
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="w-full btn-gomad-primary text-lg py-4 rounded-[12px]">
            📝 SIMPAN & AJUKAN VERIFIKASI
        </button>
    </form>
</div>

@push('scripts')
<script>
// ═══════════════════════════════════════
// CONTACT FORM (WhatsApp)
// ═══════════════════════════════════════
function contactForm() {
    return {
        whatsapp: '{{ old('owner_phone', $agent->owner_phone ?? auth()->user()->phone ?? '') }}',
    }
}

// ═══════════════════════════════════════
// GUARD FORM (Checkbox Penjaga)
// ═══════════════════════════════════════
function guardForm() {
    return {
        hasGuard: {{ old('guard_name', $agent->guard_name ?? '') ? 'true' : 'false' }},
    }
}

// ═══════════════════════════════════════
// LOCATION SELECT (CHAINED DROPDOWN) — DENGAN PRELOAD
// ═══════════════════════════════════════
function locationSelect() {
    return {
        province: '{{ old('province_code', $agent->province_code ?? '') }}',
        city: '{{ old('city_code', $agent->city_code ?? '') }}',
        district: '{{ old('district_code', $agent->district_code ?? '') }}',
        
        // ⚡ PRELOAD: Data cities & districts dari server
        cities: @json($preloadedCities),
        districts: @json($preloadedDistricts),

        async loadCities() {
            if (!this.province) { 
                this.cities = []; 
                this.city = ''; 
                this.district = ''; 
                return; 
            }
            
            try {
                const res = await fetch(`/api/v1/region/cities?province=${this.province}`);
                const data = await res.json();
                this.cities = data.data || data || [];
                
                if (this.city && !this.cities.find(c => c.code === this.city)) {
                    this.city = '';
                    this.district = '';
                }
            } catch (e) {
                console.error('Failed to load cities:', e);
            }
        },

        async loadDistricts() {
            if (!this.city) { 
                this.districts = []; 
                this.district = ''; 
                return; 
            }
            
            try {
                const res = await fetch(`/api/v1/region/districts?city=${this.city}`);
                const data = await res.json();
                this.districts = data.data || data || [];
                
                if (this.district && !this.districts.find(d => d.code === this.district)) {
                    this.district = '';
                }
            } catch (e) {
                console.error('Failed to load districts:', e);
            }
        },

        init() {
            // Jika province sudah terpilih tapi cities kosong, auto-load
            if (this.province && this.cities.length === 0) {
                this.loadCities().then(() => {
                    if (this.city && this.districts.length === 0) {
                        this.loadDistricts();
                    }
                });
            }
        }
    }
}
</script>
@endpush
@endsection