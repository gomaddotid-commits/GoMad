@extends('layouts.agency')

@section('title', 'Edit Profil')
@section('content')
@php
    $agency = auth()->user()->agency;

    function arr($data) {
        if (is_array($data)) return $data;
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    $gallery = arr($agency->gallery ?? []);
@endphp

<div>
    <h1 class="text-lg font-bold text-secondary mb-2">Edit Profil Agency</h1>
    <p class="text-gray-500 mb-6 text-sm">Lengkapi profil agency Anda untuk mendapatkan verifikasi</p>

    {{-- STATUS VERIFIKASI --}}
    @if(!$agency->is_verified)
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
        <div class="flex items-start gap-3">
            <span class="text-2xl">⚠️</span>
            <div class="flex-1">
                <p class="font-semibold text-yellow-800">Agency belum diverifikasi</p>

                @php
                    $lastVerification = $agency->verifications()->latest()->first();
                @endphp

                @if($lastVerification && $lastVerification->status == 'pending')
                    <p class="text-sm text-yellow-700 mt-1">
                        ⏳ Pengajuan verifikasi Anda sedang diproses oleh admin.
                        @if($lastVerification->created_at)
                            <br>Diajukan: {{ $lastVerification->created_at->format('d M Y H:i') }}
                        @endif
                    </p>

                @elseif($lastVerification && $lastVerification->status == 'rejected')
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 mt-2">
                        <p class="text-sm font-medium text-red-800">❌ Pengajuan Ditolak</p>
                        <p class="text-sm text-red-700 mt-1">
                            <strong>Alasan:</strong> {{ $lastVerification->rejection_reason ?? 'Tidak ada alasan' }}
                        </p>
                        @if($lastVerification->verified_at)
                            <p class="text-xs text-red-500 mt-1">Ditolak pada: {{ $lastVerification->verified_at->format('d M Y H:i') }}</p>
                        @endif
                    </div>

                    <div class="mt-3 p-3 bg-white border border-yellow-300 rounded-lg">
                        <p class="text-sm font-medium text-yellow-800 mb-2">📝 Perbaiki data sesuai catatan penolakan di atas, lalu setup ulang:</p>
                        <a href="{{ route('agency.setup', ['reset' => 1]) }}" class="inline-block bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm font-semibold hover:bg-yellow-600 transition">
                            🔄 Setup Ulang Profil Agency
                        </a>
                    </div>

                @else
                    <p class="text-sm text-yellow-700 mt-1">
                        Lengkapi semua data profil, lalu klik tombol <strong>"Ajukan Verifikasi"</strong>.
                        Admin akan mereview dalam 1-3 hari kerja.
                    </p>
                    <form action="{{ route('agency.profile.verify') }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="bg-yellow-500 text-white px-6 py-2 rounded-lg text-sm font-semibold hover:bg-yellow-600 transition">
                            📝 Ajukan Verifikasi
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @else
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
        <div class="flex items-center gap-3">
            <span class="text-2xl">✅</span>
            <div>
                <p class="font-semibold text-green-800">Agency Terverifikasi</p>
                <p class="text-sm text-green-700">Semua fitur tersedia untuk agency Anda.</p>
            </div>
        </div>
    </div>
    @endif

    {{-- UPLOAD LOGO & COVER --}}
    <div class="grid grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-secondary mb-3">🖼️ Logo Agency</h3>
            <div class="mb-3">
                @if($agency->logo)
                <img src="{{ asset('storage/' . $agency->logo) }}" alt="Logo" class="w-32 h-32 object-cover rounded-xl border border-gray-200">
                @else
                <div class="w-32 h-32 bg-gray-100 rounded-xl flex items-center justify-center text-4xl text-gray-400">🏢</div>
                @endif
            </div>
            <form action="{{ route('agency.profile.logo') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="logo" accept="image/*" class="w-full text-sm mb-2" required>
                <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-primary-700 transition">Upload Logo</button>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-secondary mb-3">🌄 Cover Image</h3>
            <div class="mb-3">
                @if($agency->cover_image)
                <img src="{{ asset('storage/' . $agency->cover_image) }}" alt="Cover" class="w-full h-24 object-cover rounded-xl border border-gray-200">
                @else
                <div class="w-full h-24 bg-gradient-to-r from-primary-600 to-primary-800 rounded-xl"></div>
                @endif
            </div>
            <form action="{{ route('agency.profile.cover') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="cover" accept="image/*" class="w-full text-sm mb-2" required>
                <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-primary-700 transition">Upload Cover</button>
            </form>
        </div>
    </div>

    {{-- FORM PROFIL --}}
    <form action="{{ route('agency.profile.update') }}" method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
        @csrf
        @method('PUT')

        <div>
            <h3 class="font-bold text-secondary mb-4">📋 Informasi Dasar</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">Nama Agency <span class="text-red-500">*</span></label>
                    <input type="text" name="agency_name" value="{{ old('agency_name', $agency->agency_name) }}"
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">Alamat Lengkap <span class="text-red-500">*</span></label>
                    <textarea name="address" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" required>{{ old('address', $agency->address) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">Deskripsi</label>
                    <textarea name="description" rows="4" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50">{{ old('description', $agency->description) }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-1">Tahun Berdiri</label>
                        <input type="number" name="founded_year" value="{{ old('founded_year', $agency->founded_year) }}"
                               class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" min="1950" max="{{ date('Y') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-1">Kontak Person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $agency->contact_person) }}"
                               class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-1">HP Alternatif</label>
                        <input type="text" name="contact_alternate" value="{{ old('contact_alternate', $agency->contact_alternate) }}"
                               class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-1">Email Bisnis</label>
                        <input type="email" name="email_alternate" value="{{ old('email_alternate', $agency->email_alternate) }}"
                               class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-4">
            <button type="submit" class="bg-primary-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-primary-700 transition active:scale-95">
                💾 SIMPAN PROFIL
            </button>
        </div>
    </form>

    {{-- GALLERY --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-6">
        <h3 class="font-bold text-secondary mb-4">📸 Galeri Foto</h3>

        <div class="grid grid-cols-4 gap-3 mb-4">
            @foreach($gallery as $index => $photo)
            <div class="relative group">
                <img src="{{ asset('storage/' . $photo) }}" alt="Gallery" class="w-full h-24 object-cover rounded-xl border border-gray-200">
                <form action="{{ route('agency.profile.gallery.remove', $index) }}" method="POST" class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition">
                    @csrf @method('DELETE')
                    <button type="submit" class="bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center hover:bg-red-600">✕</button>
                </form>
            </div>
            @endforeach

            @if(count($gallery) < 10)
            <form action="{{ route('agency.profile.gallery.add') }}" method="POST" enctype="multipart/form-data" class="border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center h-24 hover:border-primary-600 transition cursor-pointer relative">
                @csrf
                <input type="file" name="photo" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="this.form.submit()" required>
                <span class="text-3xl text-gray-400">+</span>
            </form>
            @endif
        </div>
        <p class="text-xs text-gray-500">Klik + untuk menambah foto (max 10). Hover foto untuk hapus.</p>
    </div>
</div>
@endsection