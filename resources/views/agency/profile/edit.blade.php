@extends('layouts.agency')

@section('title', 'Edit Profil')
@section('content')
@php
    $agency = auth()->user()->agency;
    $provinces = \App\Models\Province::orderBy('name')->get();
    $allCities = \App\Models\City::with('province')->orderBy('name')->get();

    function arr($data) {
        if (is_array($data)) return $data;
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    $gallery = arr($agency->gallery ?? []);
    
    // Coverage data
    $coverageSelected = old('coverage_cities', $agency->coverage_cities ?? [$agency->city_code]);
    $allCitiesData = $allCities->map(function($c) {
        return [
            'code' => $c->code,
            'name' => $c->name,
            'province_name' => $c->province->name ?? '',
        ];
    })->values()->toArray();

    // Pre-load cities & districts untuk chained select
    $preloadedCities = [];
    $preloadedDistricts = [];

    if ($agency->province_code) {
        $preloadedCities = \App\Models\City::where('province_code', $agency->province_code)
            ->orderBy('name')
            ->get(['code', 'name'])
            ->toArray();
    }

    if ($agency->city_code) {
        $preloadedDistricts = \App\Models\District::where('city_code', $agency->city_code)
            ->orderBy('name')
            ->get(['code', 'name'])
            ->toArray();
    }
@endphp

<div>
    <h1 class="text-lg font-bold text-[#111111] mb-2">Edit Profil Agency</h1>
    <p class="text-gray-500 font-light mb-6 text-sm">Lengkapi profil agency Anda untuk mendapatkan verifikasi</p>

    {{-- STATUS VERIFIKASI --}}
    @if(!$agency->is_verified)
    <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-4 mb-6">
        <div class="flex items-start gap-3">
            <span class="text-2xl">⚠️</span>
            <div class="flex-1">
                <p class="font-semibold text-yellow-800 font-mono uppercase tracking-wider text-xs">Agency belum diverifikasi</p>

                @php
                    $lastVerification = $agency->verifications()->latest()->first();
                @endphp

                @if($lastVerification && $lastVerification->status == 'pending')
                    <p class="text-sm text-yellow-700 mt-1 font-light">
                        ⏳ Pengajuan verifikasi Anda sedang diproses oleh admin.
                        @if($lastVerification->created_at)
                            <br>Diajukan: {{ $lastVerification->created_at->format('d M Y H:i') }}
                        @endif
                    </p>

                @elseif($lastVerification && $lastVerification->status == 'rejected')
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 mt-2">
                        <p class="text-sm font-medium text-red-800">❌ Pengajuan Ditolak</p>
                        <p class="text-sm text-red-700 mt-1 font-light">
                            <strong>Alasan:</strong> {{ $lastVerification->rejection_reason ?? 'Tidak ada alasan' }}
                        </p>
                        @if($lastVerification->verified_at)
                            <p class="text-xs text-red-500 mt-1">Ditolak pada: {{ $lastVerification->verified_at->format('d M Y H:i') }}</p>
                        @endif
                    </div>

                    <div class="mt-3 p-3 bg-white border border-yellow-300 rounded-lg">
                        <p class="text-sm font-medium text-yellow-800 mb-2 font-mono uppercase tracking-wider text-xs">📝 Perbaiki data sesuai catatan penolakan di atas</p>
                        <a href="{{ route('agency.setup', ['reset' => 1]) }}" class="inline-block bg-[#C1121F] text-white px-6 py-2 rounded-[12px] text-sm font-semibold hover:bg-[#8A0F18] transition">
                            🔄 Setup Ulang Profil Agency
                        </a>
                    </div>

                @else
                    <p class="text-sm text-yellow-700 mt-1 font-light">
                        Lengkapi semua data profil, lalu klik tombol <strong>"Ajukan Verifikasi"</strong>.
                        Admin akan mereview dalam 1-3 hari kerja.
                    </p>
                    <form action="{{ route('agency.profile.verify') }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="bg-[#C1121F] text-white px-6 py-2 rounded-[12px] text-sm font-semibold hover:bg-[#8A0F18] transition">
                            📝 Ajukan Verifikasi
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @else
    <div class="bg-green-50 border border-green-200 rounded-[12px] p-4 mb-6">
        <div class="flex items-center gap-3">
            <span class="text-2xl">✅</span>
            <div>
                <p class="font-semibold text-green-800 font-mono uppercase tracking-wider text-xs">Agency Terverifikasi</p>
                <p class="text-sm text-green-700 font-light">Semua fitur tersedia untuk agency Anda.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- UPLOAD LOGO & COVER --}}
    <div class="grid grid-cols-2 gap-6 mb-6">
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">🖼️ Logo Agency</h3>
            <div class="mb-3">
                @if($agency->logo)
                <img src="{{ $agency->logo }}" alt="Logo" class="w-32 h-32 object-cover rounded-[12px] border border-[#E5E5E5]">
                @else
                <div class="w-32 h-32 bg-[#F5F5F5] rounded-[12px] flex items-center justify-center text-4xl text-gray-400 border border-[#E5E5E5]">🏢</div>
                @endif
            </div>
            <form action="{{ route('agency.profile.logo') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="logo" accept="image/*" class="w-full text-sm mb-2">
                <button type="submit" class="bg-[#C1121F] text-white px-4 py-2 rounded-[12px] text-sm font-semibold hover:bg-[#8A0F18] transition">Upload Logo</button>
            </form>
        </div>

        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">🌄 Cover Image</h3>
            <div class="mb-3">
                @if($agency->cover_image)
                <img src="{{ $agency->cover_image }}" alt="Cover" class="w-full h-24 object-cover rounded-[12px] border border-[#E5E5E5]">
                @else
                <div class="w-full h-24 bg-[#C1121F]/5 rounded-[12px] border border-[#E5E5E5]"></div>
                @endif
            </div>
            <form action="{{ route('agency.profile.cover') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="cover" accept="image/*" class="w-full text-sm mb-2">
                <button type="submit" class="bg-[#C1121F] text-white px-4 py-2 rounded-[12px] text-sm font-semibold hover:bg-[#8A0F18] transition">Upload Cover</button>
            </form>
        </div>
    </div>

    {{-- FORM PROFIL --}}
    <form action="{{ route('agency.profile.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Informasi Dasar --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">📋 Informasi Dasar</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nama Agency <span class="text-[#C1121F]">*</span></label>
                    <input type="text" name="agency_name" value="{{ old('agency_name', $agency->agency_name) }}"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Deskripsi</label>
                    <textarea name="description" rows="4" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">{{ old('description', $agency->description) }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Tahun Berdiri</label>
                        <input type="number" name="founded_year" value="{{ old('founded_year', $agency->founded_year) }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" min="1950" max="{{ date('Y') }}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kontak Person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $agency->contact_person) }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">HP Alternatif</label>
                        <input type="text" name="contact_alternate" value="{{ old('contact_alternate', $agency->contact_alternate) }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Email Bisnis</label>
                        <input type="email" name="email_alternate" value="{{ old('email_alternate', $agency->email_alternate) }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════ --}}
        {{-- LOKASI AGENCY (LARAVOLT) --}}
        {{-- ═══════════════════════════════════════ --}}
        <div class="bg-white border-2 border-[#C1121F] rounded-[12px] p-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#C1121F] mb-4">📍 Lokasi Agency</h3>
            
            <div x-data="locationSelect()" class="space-y-4">
                {{-- Provinsi --}}
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Provinsi <span class="text-[#C1121F]">*</span></label>
                    <select name="province_code" x-model="province" @change="loadCities()" 
                            class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                        <option value="">Pilih Provinsi</option>
                        @foreach($provinces as $p)
                        <option value="{{ $p->code }}" {{ old('province_code', $agency->province_code) == $p->code ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Kab/Kota --}}
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kabupaten/Kota <span class="text-[#C1121F]">*</span></label>
                    <select name="city_code" x-model="city" @change="loadDistricts()" :disabled="!province"
                            class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                        <option value="">Pilih Kab/Kota</option>
                        <template x-for="c in cities" :key="c.code">
                            <option :value="c.code" x-text="c.name"
                                    :selected="c.code === '{{ old('city_code', $agency->city_code) }}'"></option>
                        </template>
                    </select>
                </div>

                {{-- Kecamatan --}}
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kecamatan</label>
                    <select name="district_code" x-model="district" :disabled="!city"
                            class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                        <option value="">Pilih Kecamatan</option>
                        <template x-for="d in districts" :key="d.code">
                            <option :value="d.code" x-text="d.name"
                                    :selected="d.code === '{{ old('district_code', $agency->district_code) }}'"></option>
                        </template>
                    </select>
                </div>

                {{-- Alamat Detail --}}
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Alamat Detail (Jalan, RT/RW) <span class="text-[#C1121F]">*</span></label>
                    <textarea name="address" rows="2" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                              placeholder="Jl. Trunojoyo No. 45, RT 02/RW 03" required>{{ old('address', $agency->address) }}</textarea>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════ --}}
        {{-- ZONA LAYANAN (COVERAGE) --}}
        {{-- ═══════════════════════════════════════ --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm" x-data="coverageSelect()">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">🗺️ Zona Layanan (Coverage)</h3>
            <p class="text-sm text-gray-500 mb-4 font-light">Pilih kota mana saja yang dilayani agency Anda.</p>

            {{-- Search Filter --}}
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

        <div class="flex gap-4">
            <button type="submit" class="btn-gomad-primary px-8 py-3 rounded-[12px] font-semibold">
                💾 SIMPAN PROFIL
            </button>
        </div>
    </form>

    {{-- GALLERY --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mt-6 shadow-sm">
        <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">📸 Galeri Foto</h3>

        <div class="grid grid-cols-4 gap-3 mb-4">
            @foreach($gallery as $index => $photo)
            <div class="relative group">
                <img src="{{ $photo }}" alt="Gallery" class="w-full h-24 object-cover rounded-[12px] border border-[#E5E5E5]">
                <form action="{{ route('agency.profile.gallery.remove', $index) }}" method="POST" class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition">
                    @csrf @method('DELETE')
                    <button type="submit" class="bg-[#C1121F] text-white rounded-full w-6 h-6 text-xs flex items-center justify-center hover:bg-[#8A0F18]">✕</button>
                </form>
            </div>
            @endforeach

            @if(count($gallery) < 10)
            <form action="{{ route('agency.profile.gallery.add') }}" method="POST" enctype="multipart/form-data" class="border-2 border-dashed border-[#E5E5E5] rounded-[12px] flex items-center justify-center h-24 hover:border-[#C1121F] transition cursor-pointer relative">
                @csrf
                <input type="file" name="photo" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.form.submit()" required>
                <span class="text-3xl text-gray-400">+</span>
            </form>
            @endif
        </div>
        <p class="text-[10px] text-gray-400 mt-1 font-light">Klik + untuk menambah foto (max 10). Hover foto untuk hapus.</p>
    </div>
</div>

@push('scripts')
<script>
// ═══════════════════════════════════════
// LOCATION SELECT (CHAINED DROPDOWN)
// ═══════════════════════════════════════
function locationSelect() {
    return {
        province: '{{ old('province_code', $agency->province_code) }}',
        city: '{{ old('city_code', $agency->city_code) }}',
        district: '{{ old('district_code', $agency->district_code) }}',
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
                // Jangan reset city kalau masih dalam province yang sama
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
                // Jangan reset district kalau masih dalam city yang sama
                if (this.district && !this.districts.find(d => d.code === this.district)) {
                    this.district = '';
                }
            } catch (e) {
                console.error('Failed to load districts:', e);
            }
        },

        init() {
            // Data sudah pre-loaded dari server
        }
    }
}

// ═══════════════════════════════════════
// COVERAGE SELECT
// ═══════════════════════════════════════
function coverageSelect() {
    return {
        selected: @json($coverageSelected),
        searchQuery: '',
        allCities: @json($allCitiesData),

        get filteredCities() {
            if (!this.searchQuery) return this.allCities;
            const q = this.searchQuery.toLowerCase();
            return this.allCities.filter(city => 
                city.name.toLowerCase().includes(q) || 
                city.province_name.toLowerCase().includes(q)
            );
        },

        selectAll() {
            this.selected = this.allCities.map(c => c.code);
        }
    }
}
</script>
@endpush
@endsection