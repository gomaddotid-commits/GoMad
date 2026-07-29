@extends('layouts.agency')

@section('title', 'Lengkapi Profil Agency')
@section('content')
@php
    $agency = auth()->user()->agency;
    $provinces = \App\Models\Province::orderBy('name')->get();
    $allCities = \App\Models\City::with('province')->orderBy('name')->get();
    
    $selectedProvince = old('province_code', $agency->province_code ?? '');
    $selectedCity = old('city_code', $agency->city_code ?? '');
    
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

<div>
    <div class="text-center mb-8">
        <div class="w-20 h-20 bg-[#C1121F]/5 rounded-[12px] flex items-center justify-center text-4xl mx-auto mb-4 border border-[#E5E5E5]">🏢</div>
        <h1 class="text-xl font-bold text-[#111111] mb-2">
            {{ $agency && $agency->agency_name ? 'Setup Ulang Profil Agency' : 'Lengkapi Profil Agency' }}
        </h1>
        <p class="text-gray-500 font-light">
            {{ $agency && $agency->agency_name ? 'Perbaiki data agency Anda sesuai catatan penolakan' : 'Isi data agency Anda untuk mulai beroperasi di GoMad' }}
        </p>
    </div>

    @if($agency && $agency->rejection_reason)
    <div class="bg-red-50 border border-red-200 rounded-[12px] p-4 mb-6">
        <p class="text-sm font-medium text-red-800">❌ Alasan Penolakan Sebelumnya:</p>
        <p class="text-sm text-red-700 mt-1 font-light">{{ $agency->rejection_reason }}</p>
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

    <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-4 mb-6">
        <p class="text-sm text-yellow-800 font-light">
            ⚠️ <strong class="font-medium">Semua field wajib diisi.</strong> Data yang lengkap akan mempercepat proses verifikasi.
        </p>
    </div>

    <form action="{{ route('agency.setup.save') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Informasi Dasar -->
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">📋 Informasi Dasar</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nama Agency <span class="text-[#C1121F]">*</span></label>
                    <input type="text" name="agency_name" value="{{ old('agency_name', $agency->agency_name ?? '') }}"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" placeholder="Contoh: Travel Jaya Abadi" required>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Deskripsi Agency <span class="text-[#C1121F]">*</span></label>
                    <textarea name="description" rows="4" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" placeholder="Ceritakan tentang agency Anda, armada, layanan, dll." required>{{ old('description', $agency->description ?? '') }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Tahun Berdiri <span class="text-[#C1121F]">*</span></label>
                        <input type="number" name="founded_year" value="{{ old('founded_year', $agency->founded_year ?? '') }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" placeholder="2020" min="1950" max="{{ date('Y') }}" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kontak Person <span class="text-[#C1121F]">*</span></label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $agency->contact_person ?? '') }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" placeholder="Nama kontak person" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- LOKASI AGENCY (SEARCHABLE DROPDOWN) -->
        <div class="bg-white border-2 border-[#C1121F] rounded-[12px] p-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#C1121F] mb-4">📍 Lokasi Agency <span class="text-[#C1121F]">*</span></h3>
            
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

                {{-- Alamat Detail --}}
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Alamat Detail (Jalan, RT/RW) <span class="text-[#C1121F]">*</span></label>
                    <textarea name="address" rows="2" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                              placeholder="Jl. Trunojoyo No. 45, RT 02/RW 03" required>{{ old('address', $agency->address ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- ZONA LAYANAN (COVERAGE) -->
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm" x-data="coverageSelect()">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">🗺️ Zona Layanan (Coverage)</h3>
            <p class="text-sm text-gray-500 mb-4 font-light">Pilih kota mana saja yang dilayani agency Anda. Minimal pilih kota domisili Anda.</p>
            <div class="mb-4">
                <input type="text" x-model="searchQuery" placeholder="🔍 Filter kota..." 
                       class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition text-sm">
            </div>
            <div class="mb-3 text-sm text-gray-500 font-light">Terpilih: <strong x-text="selected.length" class="text-[#C1121F]"></strong> kota</div>
            <div class="grid md:grid-cols-3 gap-3 max-h-80 overflow-y-auto">
                <template x-for="city in filteredCities" :key="city.code">
                    <label class="flex items-center gap-3 p-3 border-2 border-[#E5E5E5] rounded-[12px] cursor-pointer hover:border-[#C1121F] transition"
                           :class="selected.includes(city.code) ? 'border-[#C1121F] bg-[#C1121F]/5' : ''">
                        <input type="checkbox" name="coverage_cities[]" :value="city.code" x-model="selected"
                               class="w-4 h-4 text-[#C1121F] rounded border-[#E5E5E5] focus:ring-[#C1121F]">
                        <div>
                            <span class="text-sm font-medium text-[#111111]" x-text="city.name"></span>
                            <span class="text-[10px] text-gray-400 block font-light" x-text="city.province_name"></span>
                        </div>
                    </label>
                </template>
            </div>
            <div class="flex gap-2 mt-3">
                <button type="button" @click="selectAll()" class="text-xs text-[#C1121F] hover:underline font-medium">Pilih Semua</button>
                <button type="button" @click="selected = []" class="text-xs text-gray-500 hover:underline font-medium">Reset</button>
            </div>
        </div>

        <!-- Kontak -->
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm" x-data="contactForm()">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">📞 Kontak</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nomor WhatsApp <span class="text-[#C1121F]">*</span></label>
                    <input type="text" name="whatsapp" x-model="whatsapp" 
                           value="{{ old('whatsapp', auth()->user()->phone ?? '') }}"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" 
                           placeholder="081234567890" required>
                    <p class="text-[10px] text-gray-400 mt-1 font-light">Notifikasi booking akan dikirim ke nomor ini</p>
                </div>
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" x-model="useSameNumber" 
                               class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]">
                        <div>
                            <span class="text-sm font-medium text-[#111111]">Gunakan nomor yang sama dengan WhatsApp</span>
                            <p class="text-xs text-gray-500 font-light mt-0.5">Nomor telepon akan diisi otomatis dengan nomor WhatsApp di atas</p>
                        </div>
                    </label>
                </div>
                <div x-show="!useSameNumber" x-transition>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nomor Telepon <span class="text-[#C1121F]">*</span></label>
                    <input type="text" name="phone" x-model="phone"
                           :required="!useSameNumber"
                           value="{{ old('phone', $agency->contact_alternate ?? '') }}"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" 
                           placeholder="081234567890">
                    <p class="text-[10px] text-gray-400 mt-1 font-light">Nomor telepon kantor/agen (bisa berbeda dengan WhatsApp)</p>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Email Bisnis</label>
                    <input type="email" name="email_alternate" 
                           value="{{ old('email_alternate', $agency->email_alternate ?? '') }}"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" 
                           placeholder="agency@email.com">
                </div>
            </div>
        </div>

        <!-- Foto Agency -->
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">🖼️ Foto Agency</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Logo Agency</label>
                    <input type="file" name="logo" accept="image/*" class="w-full text-sm">
                    <p class="text-[10px] text-gray-400 mt-1 font-light">Format: JPG, PNG, WebP. Maksimal 2MB</p>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Cover Image</label>
                    <input type="file" name="cover" accept="image/*" class="w-full text-sm">
                    <p class="text-[10px] text-gray-400 mt-1 font-light">Format: JPG, PNG, WebP. Maksimal 5MB</p>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Galeri Foto (max 10)</label>
                <input type="file" name="gallery[]" accept="image/*" multiple class="w-full text-sm">
                <p class="text-[10px] text-gray-400 mt-1 font-light">Format: JPG, PNG, WebP. Maksimal 2MB per foto</p>
            </div>
        </div>

        <!-- Dokumen Pengajuan -->
        <div class="bg-white border-2 border-[#C1121F] rounded-[12px] p-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#C1121F] mb-4">📄 Dokumen Pengajuan Verifikasi <span class="text-[#C1121F]">*</span></h3>
            <div class="bg-[#F5F5F5] rounded-lg p-4 mb-4 border border-[#E5E5E5]">
                <p class="text-sm text-[#111111] mb-2 font-medium">Dokumen PDF harus berisi:</p>
                <ol class="list-decimal list-inside text-sm text-gray-600 space-y-1 font-light">
                    <li>Profil Agency (nama, alamat, deskripsi layanan)</li>
                    <li>Profil Pemilik (nama, KTP, alamat)</li>
                    <li>Dokumen Identitas Pemilik (scan KTP/SIM)</li>
                    <li>Tanda tangan pernyataan keaslian data</li>
                </ol>
            </div>
            <input type="file" name="documents" accept=".pdf" class="w-full text-sm" required>
            <p class="text-[10px] text-gray-400 mt-1 font-light">Format: PDF. Maksimal 10MB</p>
        </div>

        <button type="submit" class="w-full btn-gomad-primary py-4 rounded-[12px] font-bold text-lg">
            📝 SIMPAN & AJUKAN VERIFIKASI
        </button>
    </form>
</div>

@push('scripts')
<script>
function contactForm() {
    return {
        whatsapp: '{{ old('whatsapp', auth()->user()->phone ?? '') }}',
        phone: '{{ old('phone', $agency->contact_alternate ?? '') }}',
        useSameNumber: {{ (old('whatsapp') && old('phone') && old('whatsapp') === old('phone')) || (!old('whatsapp') && auth()->user()->phone && auth()->user()->phone === ($agency->contact_alternate ?? '')) || empty(old('phone', $agency->contact_alternate ?? '')) ? 'true' : 'false' }},
        init() {
            if (!this.phone || this.phone === this.whatsapp) {
                this.useSameNumber = true;
                this.phone = this.whatsapp;
            }
            this.$watch('whatsapp', (value) => { if (this.useSameNumber) this.phone = value; });
            this.$watch('useSameNumber', (value) => { if (value) this.phone = this.whatsapp; });
        }
    }
}

function locationSelect() {
    return {
        selectedProvince: '{{ old('province_code', $agency->province_code ?? '') }}',
        selectedCity: '{{ old('city_code', $agency->city_code ?? '') }}',
        selectedDistrict: '{{ old('district_code', $agency->district_code ?? '') }}',
        
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
            @if(old('province_code') && old('province_code') !== ($agency->province_code ?? ''))
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

function coverageSelect() {
    return {
        selected: @json(old('coverage_cities', $agency->coverage_cities ?? [])),
        searchQuery: '',
        allCities: @json($allCities->map(fn($c) => ['code' => $c->code, 'name' => $c->name, 'province_name' => $c->province?->name ?? ''])),
        get filteredCities() {
            if (!this.searchQuery) return this.allCities;
            const q = this.searchQuery.toLowerCase();
            return this.allCities.filter(c => c.name.toLowerCase().includes(q) || c.province_name.toLowerCase().includes(q));
        },
        selectAll() { this.selected = this.allCities.map(c => c.code); }
    }
}
</script>
@endpush
@endsection