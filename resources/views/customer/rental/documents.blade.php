@extends('layouts.customer')

@section('title', 'Dokumen Saya')
@section('content')

<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-[#111111] mb-2">Dokumen Saya</h1>
    <p class="text-gray-500 font-light mb-6">Lengkapi dokumen untuk bisa menggunakan layanan Rental Lepas Kunci (Self Drive).</p>

    {{-- Status Dokumen --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">Status Verifikasi</h3>
        
        @php
            $docStatus = $documentStatus;
            $allVerified = $docStatus['ktp']['verified'] && $docStatus['sim']['verified'];
        @endphp

        <div class="rounded-[12px] p-4 mb-4 border 
            @if($docStatus['verification_status'] == 'verified') bg-green-50 border-green-200
            @elseif($docStatus['verification_status'] == 'rejected') bg-red-50 border-red-200
            @elseif($docStatus['verification_status'] == 'pending') bg-yellow-50 border-yellow-200
            @else bg-[#F5F5F5] border-[#E5E5E5] @endif">
            <div class="flex items-center gap-3">
                <span class="text-2xl">
                    @if($docStatus['verification_status'] == 'verified') ✅
                    @elseif($docStatus['verification_status'] == 'rejected') ❌
                    @elseif($docStatus['verification_status'] == 'pending') ⏳
                    @else 📝
                    @endif
                </span>
                <div>
                    <p class="font-bold text-[#111111]">
                        @if($docStatus['verification_status'] == 'verified')
                            Dokumen Terverifikasi
                        @elseif($docStatus['verification_status'] == 'rejected')
                            Dokumen Ditolak
                        @elseif($docStatus['verification_status'] == 'pending')
                            Menunggu Verifikasi
                        @else
                            Belum Mengupload Dokumen
                        @endif
                    </p>
                    <p class="text-sm font-light text-gray-600">
                        @if($allVerified)
                            Anda sudah bisa menggunakan layanan Rental Lepas Kunci.
                        @else
                            Lengkapi KTP & SIM untuk bisa menyewa mobil tanpa supir.
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Status per Dokumen --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between p-3 bg-[#F5F5F5] rounded-[12px] border border-[#E5E5E5]">
                <div class="flex items-center gap-2">
                    <span>🪪</span>
                    <div>
                        <span class="font-medium text-[#111111] text-sm">KTP</span>
                        <p class="text-[10px] text-gray-500 font-light">{{ $docStatus['ktp']['number'] ?? 'Belum diisi' }}</p>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                    @if($docStatus['ktp']['verified']) bg-green-50 text-green-700 border-green-200
                    @elseif($docStatus['ktp']['uploaded']) bg-yellow-50 text-yellow-700 border-yellow-200
                    @else bg-[#F5F5F5] text-gray-400 border-[#E5E5E5] @endif">
                    @if($docStatus['ktp']['verified']) ✅ Verified
                    @elseif($docStatus['ktp']['uploaded']) ⏳ Menunggu
                    @else ❌ Belum
                    @endif
                </span>
            </div>

            <div class="flex items-center justify-between p-3 bg-[#F5F5F5] rounded-[12px] border border-[#E5E5E5]">
                <div class="flex items-center gap-2">
                    <span>🚗</span>
                    <div>
                        <span class="font-medium text-[#111111] text-sm">SIM</span>
                        <p class="text-[10px] text-gray-500 font-light">{{ $docStatus['sim']['number'] ?? 'Belum diisi' }}</p>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                    @if($docStatus['sim']['verified']) bg-green-50 text-green-700 border-green-200
                    @elseif($docStatus['sim']['uploaded']) bg-yellow-50 text-yellow-700 border-yellow-200
                    @else bg-[#F5F5F5] text-gray-400 border-[#E5E5E5] @endif">
                    @if($docStatus['sim']['verified']) ✅ Verified
                    @elseif($docStatus['sim']['uploaded']) ⏳ Menunggu
                    @else ❌ Belum
                    @endif
                </span>
            </div>

            <div class="flex items-center justify-between p-3 bg-[#F5F5F5] rounded-[12px] border border-[#E5E5E5]">
                <div class="flex items-center gap-2">
                    <span>📄</span>
                    <div>
                        <span class="font-medium text-[#111111] text-sm">NPWP</span>
                        <span class="text-[10px] text-gray-400 font-light ml-1">(Opsional)</span>
                        <p class="text-[10px] text-gray-500 font-light">{{ $docStatus['npwp']['number'] ?? 'Belum diisi' }}</p>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                    @if($docStatus['npwp']['verified']) bg-green-50 text-green-700 border-green-200
                    @elseif($docStatus['npwp']['uploaded']) bg-yellow-50 text-yellow-700 border-yellow-200
                    @else bg-[#F5F5F5] text-gray-400 border-[#E5E5E5] @endif">
                    @if($docStatus['npwp']['verified']) ✅ Verified
                    @elseif($docStatus['npwp']['uploaded']) ⏳ Menunggu
                    @else ⚪ Opsional
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- Form Upload --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
        <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">
            {{ $docStatus['has_documents'] ? 'Update Dokumen' : 'Upload Dokumen' }}
        </h3>

        <form action="{{ route('customer.documents.submit') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                        Nomor KTP <span class="text-[#C1121F]">*</span>
                    </label>
                    <input type="text" name="ktp_number" value="{{ old('ktp_number', $docStatus['ktp']['number'] ?? '') }}"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                           placeholder="Nomor KTP" required>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                        Foto KTP <span class="text-[#C1121F]">*</span>
                    </label>
                    <input type="file" name="ktp_photo" accept="image/*" class="w-full text-sm" {{ $docStatus['ktp']['uploaded'] ? '' : 'required' }}>
                    <p class="text-[10px] text-gray-400 mt-1 font-light">Format: JPG, PNG. Max 2MB</p>
                    @if($docStatus['ktp']['uploaded'])
                    <p class="text-[10px] text-green-600 mt-1 font-light">✅ Sudah diupload sebelumnya</p>
                    @endif
                </div>

                <hr class="border-[#E5E5E5]">

                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                        Nomor SIM <span class="text-[#C1121F]">*</span>
                    </label>
                    <input type="text" name="sim_number" value="{{ old('sim_number', $docStatus['sim']['number'] ?? '') }}"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                           placeholder="Nomor SIM" required>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                        Foto SIM <span class="text-[#C1121F]">*</span>
                    </label>
                    <input type="file" name="sim_photo" accept="image/*" class="w-full text-sm" {{ $docStatus['sim']['uploaded'] ? '' : 'required' }}>
                    <p class="text-[10px] text-gray-400 mt-1 font-light">Format: JPG, PNG. Max 2MB</p>
                    @if($docStatus['sim']['uploaded'])
                    <p class="text-[10px] text-green-600 mt-1 font-light">✅ Sudah diupload sebelumnya</p>
                    @endif
                </div>

                <hr class="border-[#E5E5E5]">

                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                        Nomor NPWP <span class="text-xs text-gray-400 font-light">(Opsional)</span>
                    </label>
                    <input type="text" name="npwp_number" value="{{ old('npwp_number', $docStatus['npwp']['number'] ?? '') }}"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                           placeholder="Nomor NPWP">
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                        Foto NPWP <span class="text-xs text-gray-400 font-light">(Opsional)</span>
                    </label>
                    <input type="file" name="npwp_photo" accept="image/*" class="w-full text-sm">
                    <p class="text-[10px] text-gray-400 mt-1 font-light">Format: JPG, PNG. Max 2MB</p>
                </div>
            </div>

            <button type="submit" class="w-full btn-gomad-primary mt-6 py-3 rounded-[12px] font-semibold">
                💾 SIMPAN DOKUMEN
            </button>
        </form>
    </div>
</div>
@endsection