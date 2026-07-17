@extends('layouts.agency')

@section('title', 'Lengkapi Profil Agency')
@section('content')
@php
    $agency = auth()->user()->agency;
    $provinces = \App\Models\Province::orderBy('name')->get();
    $allCities = \App\Models\City::with('province')->orderBy('name')->get();
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

        <!-- LOKASI AGENCY (LARAVOLT) -->
        <div class="bg-white border-2 border-[#C1121F] rounded-[12px] p-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#C1121F] mb-4">📍 Lokasi Agency <span class="text-[#C1121F]">*</span></h3>
            
            <div x-data="locationSelect()" class="space-y-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Provinsi <span class="text-[#C1121F]">*</span></label>
                    <select name="province_code" x-model="province" @change="loadCities()" 
                            class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                        <option value="">Pilih Provinsi</option>
                        @foreach($provinces as $p)
                        <option value="{{ $p->code }}" {{ old('province_code', $agency->province_code ?? '') == $p->code ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kabupaten/Kota <span class="text-[#C1121F]">*</span></label>
                    <select name="city_code" x-model="city" @change="loadDistricts()" :disabled="!province"
                            class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                        <option value="">Pilih Kab/Kota</option>
                        <template x-for="c in cities" :key="c.code">
                            <option :value="c.code" x-text="c.name" :selected="c.code === '{{ old('city_code', $agency->city_code ?? '') }}'"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kecamatan</label>
                    <select name="district_code" x-model="district" :disabled="!city"
                            class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                        <option value="">Pilih Kecamatan</option>
                        <template x-for="d in districts" :key="d.code">
                            <option :value="d.code" x-text="d.name" :selected="d.code === '{{ old('district_code', $agency->district_code ?? '') }}'"></option>
                        </template>
                    </select>
                </div>
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

            <div class="mb-3 text-sm text-gray-500 font-light">
                Terpilih: <strong x-text="selected.length" class="text-[#C1121F]"></strong> kota
            </div>

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
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">📞 Kontak</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nomor Telepon <span class="text-[#C1121F]">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $agency->contact_alternate ?? auth()->user()->phone) }}"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" placeholder="081234567890" required>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Email Bisnis</label>
                    <input type="email" name="email_alternate" value="{{ old('email_alternate', $agency->email_alternate ?? '') }}"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" placeholder="agency@email.com">
                </div>
            </div>
        </div>

        <!-- Foto & Dokumen -->
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">🖼️ Foto Agency</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Logo Agency</label>
                    <input type="file" name="logo" accept="image/*" class="w-full text-sm">
                    <p class="text-[10px] text-gray-400 mt-1 font-light">Format: JPG, PNG. Max 2MB</p>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Cover Image</label>
                    <input type="file" name="cover" accept="image/*" class="w-full text-sm">
                    <p class="text-[10px] text-gray-400 mt-1 font-light">Format: JPG, PNG. Max 5MB</p>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Galeri Foto (max 10)</label>
                <input type="file" name="gallery[]" accept="image/*" multiple class="w-full text-sm">
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
            <p class="text-[10px] text-gray-400 mt-1 font-light">Format: PDF. Max 10MB</p>
        </div>

        <button type="submit" class="w-full btn-gomad-primary py-4 rounded-[12px] font-bold text-lg">
            📝 SIMPAN & AJUKAN VERIFIKASI
        </button>
    </form>
</div>

@push('scripts')
<script>
function locationSelect() {
    return {
        province: '{{ old('province_code', $agency->province_code ?? '') }}',
        city: '{{ old('city_code', $agency->city_code ?? '') }}',
        district: '{{ old('district_code', $agency->district_code ?? '') }}',
        cities: [],
        districts: [],

        async loadCities() {
            if (!this.province) { this.cities = []; this.city = ''; this.district = ''; return; }
            try {
                const res = await fetch(`/api/v1/region/cities?province=${this.province}`);
                const data = await res.json();
                this.cities = data.data || data || [];
                this.city = '';
                this.district = '';
            } catch (e) { console.error('Failed to load cities:', e); }
        },

        async loadDistricts() {
            if (!this.city) { this.districts = []; this.district = ''; return; }
            try {
                const res = await fetch(`/api/v1/region/districts?city=${this.city}`);
                const data = await res.json();
                this.districts = data.data || data || [];
                this.district = '';
            } catch (e) { console.error('Failed to load districts:', e); }
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