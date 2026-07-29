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
    
    $coverageSelected = old('coverage_cities', $agency->coverage_cities ?? [$agency->city_code]);
    $allCitiesData = $allCities->map(function($c) {
        return [
            'code' => $c->code,
            'name' => $c->name,
            'province_name' => $c->province->name ?? '',
        ];
    })->values()->toArray();

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
    <h1 class="text-lg font-bold text-[#111827] mb-2">Edit Profil Agency</h1>
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
                        <a href="{{ route('agency.setup', ['reset' => 1]) }}" class="inline-block bg-[#BA1826] text-white px-6 py-2 rounded-[10px] text-sm font-semibold hover:bg-[#8A0F18] transition">
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
                        <button type="submit" class="bg-[#BA1826] text-white px-6 py-2 rounded-[10px] text-sm font-semibold hover:bg-[#8A0F18] transition">
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

    {{-- UPLOAD LOGO & COVER --}}
    <div class="grid grid-cols-2 gap-6 mb-6">
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 shadow-gomad">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111827] mb-3">🖼️ Logo Agency</h3>
            <div class="mb-3">
                @if($agency->logo)
                <img src="{{ $agency->logo }}" alt="Logo" class="w-32 h-32 object-cover rounded-[12px] border border-[#E5E7EB]">
                @else
                <div class="w-32 h-32 bg-[#F9FAFB] rounded-[12px] flex items-center justify-center text-4xl text-gray-400 border border-[#E5E7EB]">🏢</div>
                @endif
            </div>
            <form action="{{ route('agency.profile.logo') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="logo" accept="image/*" class="w-full text-sm mb-2">
                <p class="text-[10px] text-gray-400 mb-2 font-light">Format: JPG, PNG, WebP. Maksimal 2MB</p>
                <button type="submit" class="bg-[#BA1826] text-white px-4 py-2 rounded-[10px] text-sm font-semibold hover:bg-[#8A0F18] transition">Upload Logo</button>
            </form>
        </div>
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 shadow-gomad">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111827] mb-3">🌄 Cover Image</h3>
            <div class="mb-3">
                @if($agency->cover_image)
                <img src="{{ $agency->cover_image }}" alt="Cover" class="w-full h-24 object-cover rounded-[12px] border border-[#E5E7EB]">
                @else
                <div class="w-full h-24 bg-[#BA1826]/5 rounded-[12px] border border-[#E5E7EB]"></div>
                @endif
            </div>
            <form action="{{ route('agency.profile.cover') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="cover" accept="image/*" class="w-full text-sm mb-2">
                <p class="text-[10px] text-gray-400 mb-2 font-light">Format: JPG, PNG, WebP. Maksimal 5MB</p>
                <button type="submit" class="bg-[#BA1826] text-white px-4 py-2 rounded-[10px] text-sm font-semibold hover:bg-[#8A0F18] transition">Upload Cover</button>
            </form>
        </div>
    </div>

    {{-- FORM PROFIL --}}
    <form action="{{ route('agency.profile.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Informasi Dasar --}}
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 shadow-gomad">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111827] mb-4">📋 Informasi Dasar</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nama Agency <span class="text-[#BA1826]">*</span></label>
                    <input type="text" name="agency_name" value="{{ old('agency_name', $agency->agency_name) }}"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition" required>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Deskripsi</label>
                    <textarea name="description" rows="4" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition">{{ old('description', $agency->description) }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Tahun Berdiri</label>
                        <input type="number" name="founded_year" value="{{ old('founded_year', $agency->founded_year) }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition" min="1950" max="{{ date('Y') }}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kontak Person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $agency->contact_person) }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">HP Alternatif</label>
                        <input type="text" name="contact_alternate" value="{{ old('contact_alternate', $agency->contact_alternate) }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Email Bisnis</label>
                        <input type="email" name="email_alternate" value="{{ old('email_alternate', $agency->email_alternate) }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition">
                    </div>
                </div>
            </div>
        </div>

        {{-- LOKASI AGENCY (SEARCHABLE DROPDOWN) --}}
        <div class="bg-white border-2 border-[#BA1826] rounded-[12px] p-6 shadow-gomad">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#BA1826] mb-4">📍 Lokasi Agency</h3>
            
            <div x-data="locationSelect()" class="space-y-4" x-init="initLocation()">
                {{-- Provinsi --}}
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Provinsi <span class="text-[#BA1826]">*</span></label>
                    <div class="relative">
                        <div class="relative">
                            <input type="text" 
                                   x-model="provinceSearch" 
                                   @click="provinceOpen = !provinceOpen"
                                   @input="provinceOpen = true"
                                   placeholder="Ketik atau pilih provinsi..."
                                   class="w-full px-0 py-2 pr-8 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition cursor-pointer"
                                   x-ref="provinceInput">
                            <svg @click.stop="provinceOpen = !provinceOpen" class="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 cursor-pointer hover:text-[#111827] transition" :class="{'rotate-180': provinceOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                        <div x-show="provinceOpen" @click.away="provinceOpen = false" x-cloak
                             class="absolute z-50 w-full mt-1 bg-white border border-[#E5E7EB] rounded-[12px] shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="province in filteredProvinces()" :key="province.code">
                                <div @click="selectProvince(province); provinceOpen = false;"
                                     class="px-4 py-2.5 text-sm hover:bg-[#BA1826]/5 cursor-pointer transition border-b border-[#F5F5F5] last:border-0"
                                     :class="{'bg-[#BA1826]/5 font-semibold text-[#BA1826]': province.code === selectedProvince}">
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
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kabupaten/Kota <span class="text-[#BA1826]">*</span></label>
                    <div class="relative">
                        <div class="relative">
                            <input type="text" 
                                   x-model="citySearch" 
                                   @click="if(selectedProvince) cityOpen = !cityOpen"
                                   @input="if(selectedProvince) cityOpen = true"
                                   placeholder="Ketik atau pilih kota..."
                                   :disabled="!selectedProvince"
                                   class="w-full px-0 py-2 pr-8 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent transition"
                                   :class="!selectedProvince ? 'cursor-not-allowed text-gray-400 bg-gray-50' : 'cursor-pointer text-[#111827]'"
                                   x-ref="cityInput">
                            <svg @click.stop="if(selectedProvince) cityOpen = !cityOpen" class="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 cursor-pointer hover:text-[#111827] transition" :class="{'rotate-180': cityOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                        <div x-show="cityOpen && selectedProvince" @click.away="cityOpen = false" x-cloak
                             class="absolute z-50 w-full mt-1 bg-white border border-[#E5E7EB] rounded-[12px] shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="city in filteredCities()" :key="city.code">
                                <div @click="selectCity(city); cityOpen = false;"
                                     class="px-4 py-2.5 text-sm hover:bg-[#BA1826]/5 cursor-pointer transition border-b border-[#F5F5F5] last:border-0"
                                     :class="{'bg-[#BA1826]/5 font-semibold text-[#BA1826]': city.code === selectedCity}">
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
                                   class="w-full px-0 py-2 pr-8 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent transition"
                                   :class="!selectedCity ? 'cursor-not-allowed text-gray-400 bg-gray-50' : 'cursor-pointer text-[#111827]'"
                                   x-ref="districtInput">
                            <svg @click.stop="if(selectedCity) districtOpen = !districtOpen" class="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 cursor-pointer hover:text-[#111827] transition" :class="{'rotate-180': districtOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                        <div x-show="districtOpen && selectedCity" @click.away="districtOpen = false" x-cloak
                             class="absolute z-50 w-full mt-1 bg-white border border-[#E5E7EB] rounded-[12px] shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="district in filteredDistricts()" :key="district.code">
                                <div @click="selectDistrict(district); districtOpen = false;"
                                     class="px-4 py-2.5 text-sm hover:bg-[#BA1826]/5 cursor-pointer transition border-b border-[#F5F5F5] last:border-0"
                                     :class="{'bg-[#BA1826]/5 font-semibold text-[#BA1826]': district.code === selectedDistrict}">
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
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Alamat Detail (Jalan, RT/RW) <span class="text-[#BA1826]">*</span></label>
                    <textarea name="address" rows="2" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition"
                              placeholder="Jl. Trunojoyo No. 45, RT 02/RW 03" required>{{ old('address', $agency->address) }}</textarea>
                </div>
            </div>
        </div>

        {{-- ZONA LAYANAN (COVERAGE) --}}
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 shadow-gomad" x-data="coverageSelect()">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111827] mb-4">🗺️ Zona Layanan (Coverage)</h3>
            <p class="text-sm text-gray-500 mb-4 font-light">Pilih kota mana saja yang dilayani agency Anda.</p>
            <div class="mb-4">
                <input type="text" x-model="searchQuery" placeholder="🔍 Filter kota..." 
                       class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition text-sm">
            </div>
            <div class="mb-3 text-sm text-gray-500 font-light">Terpilih: <strong x-text="selected.length" class="text-[#BA1826]"></strong> kota</div>
            <div class="grid md:grid-cols-3 gap-3 max-h-80 overflow-y-auto">
                <template x-for="city in filteredCities" :key="city.code">
                    <label class="flex items-center gap-3 p-3 border-2 border-[#E5E7EB] rounded-[12px] cursor-pointer hover:border-[#BA1826] transition"
                           :class="selected.includes(city.code) ? 'border-[#BA1826] bg-[#BA1826]/5' : ''">
                        <input type="checkbox" name="coverage_cities[]" :value="city.code" x-model="selected"
                               class="w-4 h-4 text-[#BA1826] rounded border-[#E5E7EB] focus:ring-[#BA1826]">
                        <div>
                            <span class="text-sm font-medium text-[#111827]" x-text="city.name"></span>
                            <span class="text-[10px] text-gray-400 block font-light" x-text="city.province_name"></span>
                        </div>
                    </label>
                </template>
            </div>
            <div class="flex gap-2 mt-3">
                <button type="button" @click="selectAll()" class="text-xs text-[#BA1826] hover:underline font-medium">Pilih Semua</button>
                <button type="button" @click="selected = []" class="text-xs text-gray-500 hover:underline font-medium">Reset</button>
            </div>
        </div>

        <div class="flex gap-4">
            <button type="submit" class="btn-gomad-primary px-8 py-3 rounded-[10px] font-semibold">💾 SIMPAN PROFIL</button>
        </div>
    </form>

    {{-- DOKUMEN VERIFIKASI --}}
    <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 mt-6 shadow-gomad">
        <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111827] mb-4">📄 Dokumen Verifikasi</h3>
        @if($agency->business_license)
        <div class="bg-green-50 border border-green-200 rounded-[12px] p-4 mb-4">
            <p class="text-sm text-green-700 font-light mb-2">✅ Dokumen sudah diupload</p>
            <a href="{{ $agency->business_license }}" target="_blank" class="inline-flex items-center gap-2 text-[#C1121F] text-sm hover:underline font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Lihat Dokumen Saat Ini →
            </a>
        </div>
        @endif
        <form action="{{ route('agency.profile.license') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">{{ $agency->business_license ? 'Upload Ulang Dokumen' : 'Upload Dokumen Verifikasi' }} (PDF)</label>
                <input type="file" name="license" accept=".pdf" class="w-full text-sm" required>
                <p class="text-[10px] text-gray-400 mt-1 font-light">Format: PDF. Maksimal 10MB</p>
            </div>
            <button type="submit" class="mt-3 bg-[#BA1826] text-white px-4 py-2 rounded-[10px] text-sm font-semibold hover:bg-[#8A0F18] transition">📤 Upload Dokumen</button>
        </form>
    </div>

    {{-- GALLERY --}}
    <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 mt-6 shadow-gomad">
        <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111827] mb-4">📸 Galeri Foto</h3>
        <div class="grid grid-cols-4 gap-3 mb-4">
            @foreach($gallery as $index => $photo)
            <div class="relative group">
                <img src="{{ $photo }}" alt="Gallery" class="w-full h-24 object-cover rounded-[12px] border border-[#E5E7EB]">
                <form action="{{ route('agency.profile.gallery.remove', $index) }}" method="POST" class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition">
                    @csrf @method('DELETE')
                    <button type="submit" class="bg-[#BA1826] text-white rounded-full w-6 h-6 text-xs flex items-center justify-center hover:bg-[#8A0F18]">✕</button>
                </form>
            </div>
            @endforeach
            @if(count($gallery) < 10)
            <form action="{{ route('agency.profile.gallery.add') }}" method="POST" enctype="multipart/form-data" class="border-2 border-dashed border-[#E5E7EB] rounded-[12px] flex items-center justify-center h-24 hover:border-[#BA1826] transition cursor-pointer relative">
                @csrf
                <input type="file" name="photo" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.form.submit()" required>
                <span class="text-3xl text-gray-400">+</span>
            </form>
            @endif
        </div>
        <p class="text-[10px] text-gray-400 mt-1 font-light">Klik + untuk menambah foto (max 10). Hover foto untuk hapus. Maksimal 2MB per foto.</p>
    </div>
</div>

@push('scripts')
<script>
function locationSelect() {
    return {
        selectedProvince: '{{ old('province_code', $agency->province_code) }}',
        selectedCity: '{{ old('city_code', $agency->city_code) }}',
        selectedDistrict: '{{ old('district_code', $agency->district_code) }}',
        
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
                if (foundCity) {
                    this.selectedCity = oldCityCode;
                    this.citySearch = foundCity.name;
                    this.loadDistricts();
                }
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
                if (foundDist) {
                    this.selectedDistrict = oldDistCode;
                    this.districtSearch = foundDist.name;
                }
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
                                    if (this.selectedDistrict && this.getDistrictName()) {
                                        this.districtSearch = this.getDistrictName();
                                    }
                                });
                            } else if (this.selectedDistrict && this.getDistrictName()) {
                                this.districtSearch = this.getDistrictName();
                            }
                        }
                    });
                } else {
                    if (this.selectedCity && this.getCityName()) {
                        this.citySearch = this.getCityName();
                        if (this.districts.length === 0 && this.selectedCity) {
                            this.loadDistricts().then(() => {
                                if (this.selectedDistrict && this.getDistrictName()) {
                                    this.districtSearch = this.getDistrictName();
                                }
                            });
                        } else if (this.selectedDistrict && this.getDistrictName()) {
                            this.districtSearch = this.getDistrictName();
                        }
                    }
                }
            }
            @if(old('province_code') && old('province_code') !== $agency->province_code)
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
        selected: @json($coverageSelected),
        searchQuery: '',
        allCities: @json($allCitiesData),
        get filteredCities() {
            if (!this.searchQuery) return this.allCities;
            const q = this.searchQuery.toLowerCase();
            return this.allCities.filter(city => city.name.toLowerCase().includes(q) || city.province_name.toLowerCase().includes(q));
        },
        selectAll() { this.selected = this.allCities.map(c => c.code); }
    }
}
</script>
@endpush
@endsection