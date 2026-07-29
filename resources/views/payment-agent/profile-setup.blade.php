@extends('layouts.payment-agent')

@section('title', 'Lengkapi Profil Warung')
@section('content')
@php 
    $agent = auth()->user()->paymentAgent; 
    $provinces = \App\Models\Province::orderBy('name')->get();
    
    $selectedProvince = old('province_code', $agent->province_code ?? '');
    $selectedCity = old('city_code', $agent->city_code ?? '');
    
    $preloadedCities = [];
    $preloadedDistricts = [];
    
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

        {{-- LOKASI WARUNG (SEARCHABLE DROPDOWN) --}}
        <div class="bg-white border-2 border-[#C1121F] rounded-[12px] p-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#C1121F] mb-4">📍 Lokasi Warung <span class="text-[#C1121F]">*</span></h3>
            
            <div x-data="locationSelect()" class="space-y-4" x-init="initLocation()">
                {{-- Provinsi --}}
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Provinsi <span class="text-[#C1121F]">*</span></label>
                    <div class="relative">
                        <div class="relative">
                            <input type="text" 
                                   x-model="provinceSearch" 
                                   @click="provinceOpen = !provinceOpen"
                                   @input="provinceOpen = true"
                                   placeholder="Ketik atau pilih provinsi..."
                                   class="w-full px-0 py-2 pr-8 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition cursor-pointer"
                                   x-ref="provinceInput">
                            <svg @click.stop="provinceOpen = !provinceOpen" class="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 cursor-pointer hover:text-[#111111] transition" :class="{'rotate-180': provinceOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                        <div x-show="provinceOpen" @click.away="provinceOpen = false" x-cloak
                             class="absolute z-50 w-full mt-1 bg-white border border-[#E5E5E5] rounded-[12px] shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="province in filteredProvinces()" :key="province.code">
                                <div @click="selectProvince(province); provinceOpen = false;"
                                     class="px-4 py-2.5 text-sm hover:bg-[#C1121F]/5 cursor-pointer transition border-b border-[#F5F5F5] last:border-0"
                                     :class="{'bg-[#C1121F]/5 font-semibold text-[#C1121F]': province.code === selectedProvince}">
                                    <span x-text="province.name"></span>
                                </div>
                            </template>
                            <div x-show="filteredProvinces().length === 0" class="px-4 py-2.5 text-sm text-gray-400 text-center">Tidak ditemukan</div>
                        </div>
                        <input type="hidden" name="province_code" :value="selectedProvince">
                    </div>
                </div>

                {{-- Kab/Kota --}}
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kabupaten/Kota <span class="text-[#C1121F]">*</span></label>
                    <div class="relative">
                        <div class="relative">
                            <input type="text" 
                                   x-model="citySearch" 
                                   @click="if(selectedProvince) cityOpen = !cityOpen"
                                   @input="if(selectedProvince) cityOpen = true"
                                   placeholder="Ketik atau pilih kota..."
                                   :disabled="!selectedProvince"
                                   class="w-full px-0 py-2 pr-8 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent transition"
                                   :class="!selectedProvince ? 'cursor-not-allowed text-gray-400 bg-gray-50' : 'cursor-pointer text-[#111111]'"
                                   x-ref="cityInput">
                            <svg @click.stop="if(selectedProvince) cityOpen = !cityOpen" class="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 cursor-pointer hover:text-[#111111] transition" :class="{'rotate-180': cityOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                        <div x-show="cityOpen && selectedProvince" @click.away="cityOpen = false" x-cloak
                             class="absolute z-50 w-full mt-1 bg-white border border-[#E5E5E5] rounded-[12px] shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="city in filteredCities()" :key="city.code">
                                <div @click="selectCity(city); cityOpen = false;"
                                     class="px-4 py-2.5 text-sm hover:bg-[#C1121F]/5 cursor-pointer transition border-b border-[#F5F5F5] last:border-0"
                                     :class="{'bg-[#C1121F]/5 font-semibold text-[#C1121F]': city.code === selectedCity}">
                                    <span x-text="city.name"></span>
                                </div>
                            </template>
                            <div x-show="filteredCities().length === 0" class="px-4 py-2.5 text-sm text-gray-400 text-center">Tidak ditemukan</div>
                        </div>
                        <input type="hidden" name="city_code" :value="selectedCity">
                    </div>
                </div>

                {{-- Kecamatan --}}
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kecamatan</label>
                    <div class="relative">
                        <div class="relative">
                            <input type="text" 
                                   x-model="districtSearch" 
                                   @click="if(selectedCity) districtOpen = !districtOpen"
                                   @input="if(selectedCity) districtOpen = true"
                                   placeholder="Ketik atau pilih kecamatan..."
                                   :disabled="!selectedCity"
                                   class="w-full px-0 py-2 pr-8 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent transition"
                                   :class="!selectedCity ? 'cursor-not-allowed text-gray-400 bg-gray-50' : 'cursor-pointer text-[#111111]'"
                                   x-ref="districtInput">
                            <svg @click.stop="if(selectedCity) districtOpen = !districtOpen" class="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 cursor-pointer hover:text-[#111111] transition" :class="{'rotate-180': districtOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                        <div x-show="districtOpen && selectedCity" @click.away="districtOpen = false" x-cloak
                             class="absolute z-50 w-full mt-1 bg-white border border-[#E5E5E5] rounded-[12px] shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="district in filteredDistricts()" :key="district.code">
                                <div @click="selectDistrict(district); districtOpen = false;"
                                     class="px-4 py-2.5 text-sm hover:bg-[#C1121F]/5 cursor-pointer transition border-b border-[#F5F5F5] last:border-0"
                                     :class="{'bg-[#C1121F]/5 font-semibold text-[#C1121F]': district.code === selectedDistrict}">
                                    <span x-text="district.name"></span>
                                </div>
                            </template>
                            <div x-show="filteredDistricts().length === 0" class="px-4 py-2.5 text-sm text-gray-400 text-center">Tidak ditemukan</div>
                        </div>
                        <input type="hidden" name="district_code" :value="selectedDistrict">
                    </div>
                </div>

                {{-- Alamat Lengkap --}}
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Alamat Lengkap <span class="text-[#C1121F]">*</span></label>
                    <textarea name="address" rows="2" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>{{ old('address', $agent->address ?? '') }}</textarea>
                </div>

                {{-- Link Google Maps --}}
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
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nama Pemilik <span class="text-[#C1121F]">*</span></label>
                    <input type="text" name="owner_name" value="{{ old('owner_name', $agent->owner_name ?? auth()->user()->name) }}" 
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nomor WhatsApp Pemilik <span class="text-[#C1121F]">*</span></label>
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
            <div x-show="hasGuard" x-transition>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nama Penjaga</label>
                        <input type="text" name="guard_name" value="{{ old('guard_name', $agent->guard_name ?? '') }}" 
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nomor HP Penjaga</label>
                        <input type="text" name="guard_phone" value="{{ old('guard_phone', $agent->guard_phone ?? '') }}" 
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
function contactForm() {
    return {
        whatsapp: '{{ old('owner_phone', $agent->owner_phone ?? auth()->user()->phone ?? '') }}',
    }
}

function guardForm() {
    return {
        hasGuard: {{ old('guard_name', $agent->guard_name ?? '') ? 'true' : 'false' }},
    }
}

function locationSelect() {
    return {
        selectedProvince: '{{ old('province_code', $agent->province_code ?? '') }}',
        selectedCity: '{{ old('city_code', $agent->city_code ?? '') }}',
        selectedDistrict: '{{ old('district_code', $agent->district_code ?? '') }}',
        
        provinceSearch: '',
        provinceOpen: false,
        citySearch: '',
        cityOpen: false,
        districtSearch: '',
        districtOpen: false,
        
        provinces: @json($provinces->map(fn($p) => ['code' => $p->code, 'name' => $p->name])),
        cities: @json($preloadedCities),
        districts: @json($preloadedDistricts),
        
        filteredProvinces() {
            var q = (this.provinceSearch || '').toLowerCase();
            if (!q) return this.provinces;
            return this.provinces.filter(p => p.name.toLowerCase().includes(q));
        },
        filteredCities() {
            var q = (this.citySearch || '').toLowerCase();
            if (!q) return this.cities;
            return this.cities.filter(c => c.name.toLowerCase().includes(q));
        },
        filteredDistricts() {
            var q = (this.districtSearch || '').toLowerCase();
            if (!q) return this.districts;
            return this.districts.filter(d => d.name.toLowerCase().includes(q));
        },
        getProvinceName() {
            if (!this.selectedProvince) return '';
            var p = this.provinces.find(p => p.code === this.selectedProvince);
            return p ? p.name : '';
        },
        getCityName() {
            if (!this.selectedCity) return '';
            var c = this.cities.find(c => c.code === this.selectedCity);
            return c ? c.name : '';
        },
        getDistrictName() {
            if (!this.selectedDistrict) return '';
            var d = this.districts.find(d => d.code === this.selectedDistrict);
            return d ? d.name : '';
        },
        selectProvince(province) {
            this.selectedProvince = province.code;
            this.selectedCity = '';
            this.selectedDistrict = '';
            this.cities = [];
            this.districts = [];
            this.provinceSearch = province.name;
            this.loadCities();
        },
        selectCity(city) {
            this.selectedCity = city.code;
            this.selectedDistrict = '';
            this.districts = [];
            this.citySearch = city.name;
            this.loadDistricts();
        },
        selectDistrict(district) {
            this.selectedDistrict = district.code;
            this.districtSearch = district.name;
        },
        async loadCities() {
            if (!this.selectedProvince) return;
            try {
                const res = await fetch(`/api/v1/region/cities?province=${this.selectedProvince}`);
                const data = await res.json();
                this.cities = data.data || data || [];
                @if(old('city_code'))
                var oldCityCode = '{{ old('city_code') }}';
                var foundCity = this.cities.find(c => c.code === oldCityCode);
                if (foundCity) { this.selectedCity = oldCityCode; this.citySearch = foundCity.name; this.loadDistricts(); }
                @endif
            } catch (e) {}
        },
        async loadDistricts() {
            if (!this.selectedCity) return;
            try {
                const res = await fetch(`/api/v1/region/districts?city=${this.selectedCity}`);
                const data = await res.json();
                this.districts = data.data || data || [];
                @if(old('district_code'))
                var oldDistCode = '{{ old('district_code') }}';
                var foundDist = this.districts.find(d => d.code === oldDistCode);
                if (foundDist) { this.selectedDistrict = oldDistCode; this.districtSearch = foundDist.name; }
                @endif
            } catch (e) {}
        },
        initLocation() {
            if (this.selectedProvince && this.getProvinceName()) {
                this.provinceSearch = this.getProvinceName();
                if (this.cities.length === 0 && this.selectedProvince) {
                    this.loadCities().then(() => {
                        if (this.selectedCity && this.getCityName()) {
                            this.citySearch = this.getCityName();
                            if (this.districts.length === 0 && this.selectedCity) {
                                this.loadDistricts().then(() => {
                                    if (this.selectedDistrict && this.getDistrictName()) this.districtSearch = this.getDistrictName();
                                });
                            } else if (this.selectedDistrict && this.getDistrictName()) this.districtSearch = this.getDistrictName();
                        }
                    });
                } else {
                    if (this.selectedCity && this.getCityName()) {
                        this.citySearch = this.getCityName();
                        if (this.districts.length === 0 && this.selectedCity) {
                            this.loadDistricts().then(() => {
                                if (this.selectedDistrict && this.getDistrictName()) this.districtSearch = this.getDistrictName();
                            });
                        } else if (this.selectedDistrict && this.getDistrictName()) this.districtSearch = this.getDistrictName();
                    }
                }
            }
            @if(old('province_code') && old('province_code') !== ($agent->province_code ?? ''))
            this.selectedProvince = '{{ old('province_code') }}';
            this.selectedCity = '';
            this.selectedDistrict = '';
            this.cities = [];
            this.districts = [];
            this.loadCities();
            @endif
        }
    }
}
</script>
@endpush
@endsection