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
            $allVerified = $docStatus['ktp']['verified'] && $docStatus['sim']['verified'] && $docStatus['selfie']['verified'];
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
                            Lengkapi KTP, SIM & Selfie untuk bisa menyewa mobil tanpa supir.
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

            <div class="flex items-center justify-between p-3 bg-[#F5F5F5] rounded-[12px] border border-[#E5E5E5]">
                <div class="flex items-center gap-2">
                    <span>🤳</span>
                    <div>
                        <span class="font-medium text-[#111111] text-sm">Selfie</span>
                        <span class="text-[10px] text-gray-400 font-light ml-1">(Wajib)</span>
                        <p class="text-[10px] text-gray-500 font-light">{{ $docStatus['selfie']['uploaded'] ? 'Sudah diupload' : 'Belum diupload' }}</p>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                    @if($docStatus['selfie']['verified']) bg-green-50 text-green-700 border-green-200
                    @elseif($docStatus['selfie']['uploaded']) bg-yellow-50 text-yellow-700 border-yellow-200
                    @else bg-[#F5F5F5] text-gray-400 border-[#E5E5E5] @endif">
                    @if($docStatus['selfie']['verified']) ✅ Verified
                    @elseif($docStatus['selfie']['uploaded']) ⏳ Menunggu
                    @else ❌ Belum
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

                <hr class="border-[#E5E5E5]">

                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                        Foto Selfie (Kamera) <span class="text-[#C1121F]">*</span>
                    </label>

                    {{-- Live camera capture (real-time) --}}
                    <div class="border border-[#E5E5E5] rounded-[12px] overflow-hidden">
                        <div class="relative bg-[#111111] w-full" style="aspect-ratio: 3/4;">
                            <video id="selfieVideo" playsinline muted autoplay class="w-full h-full object-cover hidden"></video>
                            <img id="selfieResult" class="w-full h-full object-cover hidden" alt="Hasil selfie">
                            <div id="selfiePlaceholder" class="absolute inset-0 flex flex-col items-center justify-center text-center p-6">
                                <span class="text-5xl mb-3">🤳</span>
                                <p class="text-white/70 text-sm font-light">Kamera belum aktif</p>
                            </div>
                        </div>
                        <div class="p-4 bg-white">
                            <button type="button" id="btnStartCamera" class="w-full bg-[#C1121F] text-white py-2.5 rounded-[12px] font-semibold hover:bg-[#8A0F18] transition text-sm">
                                📷 Buka Kamera & Ambil Selfie
                            </button>
                            <div id="cameraControls" class="hidden grid grid-cols-2 gap-2">
                                <button type="button" id="btnCapture" class="bg-[#C1121F] text-white py-2.5 rounded-[12px] font-semibold hover:bg-[#8A0F18] transition text-sm">
                                    📸 Ambil Foto
                                </button>
                                <button type="button" id="btnRetake" class="hidden border border-[#E5E5E5] py-2.5 rounded-[12px] font-medium text-[#111111] hover:bg-[#F5F5F5] transition text-sm">
                                    🔄 Ambil Ulang
                                </button>
                                <button type="button" id="btnStopCamera" class="hidden col-span-2 border border-[#E5E5E5] py-2 rounded-[12px] text-xs text-gray-500 hover:bg-[#F5F5F5] transition">
                                    ✖ Tutup Kamera
                                </button>
                            </div>
                            <div id="selfieFallback" class="hidden mt-2">
                                <p class="text-[10px] text-gray-500 font-light mb-1">Kamera tidak tersedia? Unggah dari galeri:</p>
                                <input type="file" id="selfieFileInput" name="selfie_photo" accept="image/*" capture="user" class="w-full text-sm" {{ $docStatus['selfie']['uploaded'] ? '' : 'required' }}>
                            </div>
                            <p id="selfieStatus" class="text-[10px] text-gray-400 mt-2 font-light">
                                📷 Ambil foto wajah Anda secara langsung (real-time). Format JPG, maks 2MB.
                            </p>
                            @if($docStatus['selfie']['uploaded'])
                            <p class="text-[10px] text-green-600 mt-1 font-light">✅ Sudah diupload sebelumnya</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full btn-gomad-primary mt-6 py-3 rounded-[12px] font-semibold">
                💾 SIMPAN DOKUMEN
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const video = document.getElementById('selfieVideo');
    const result = document.getElementById('selfieResult');
    const placeholder = document.getElementById('selfiePlaceholder');
    const fileInput = document.getElementById('selfieFileInput');
    const btnStart = document.getElementById('btnStartCamera');
    const btnCapture = document.getElementById('btnCapture');
    const btnRetake = document.getElementById('btnRetake');
    const btnStop = document.getElementById('btnStopCamera');
    const cameraControls = document.getElementById('cameraControls');
    const fallbackWrap = document.getElementById('selfieFallback');
    const statusEl = document.getElementById('selfieStatus');

    let stream = null;

    function setStatus(msg, isOk) {
        statusEl.textContent = msg;
        statusEl.classList.remove('text-gray-400', 'text-green-600', 'text-red-600');
        statusEl.classList.add(isOk === true ? 'text-green-600' : (isOk === false ? 'text-red-600' : 'text-gray-400'));
    }

    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 960 } },
                audio: false,
            });
            video.srcObject = stream;
            video.classList.remove('hidden');
            placeholder.classList.add('hidden');
            result.classList.add('hidden');
            btnStart.classList.add('hidden');
            cameraControls.classList.remove('hidden');
            btnRetake.classList.add('hidden');
            btnCapture.classList.remove('hidden');
            btnStop.classList.remove('hidden');
            fallbackWrap.classList.add('hidden');
            setStatus('Kamera aktif — posisikan wajah Anda lalu tekan "Ambil Foto".', true);
        } catch (err) {
            setStatus('Kamera tidak tersedia / izin ditolak. Unggah dari galeri di bawah.', false);
            fallbackWrap.classList.remove('hidden');
            cameraControls.classList.add('hidden');
            btnStart.classList.remove('hidden');
        }
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
        video.srcObject = null;
        video.classList.add('hidden');
        placeholder.classList.remove('hidden');
        if (!fileInput.files || fileInput.files.length === 0) {
            result.classList.add('hidden');
        }
    }

    btnCapture.addEventListener('click', function () {
        if (!video.videoWidth) return;
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(function (blob) {
            if (!blob) return;
            const file = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;

            // Tampilkan hasil
            result.src = URL.createObjectURL(blob);
            result.classList.remove('hidden');
            video.classList.add('hidden');
            placeholder.classList.add('hidden');

            // Stop kamera tapi tampilkan preview hasil
            stopCamera();
            placeholder.classList.add('hidden');

            // Toggle tombol
            btnCapture.classList.add('hidden');
            btnRetake.classList.remove('hidden');
            btnStop.classList.add('hidden');
            cameraControls.classList.remove('hidden');

            setStatus('✅ Selfie berhasil diambil. Klik "Ambil Ulang" untuk mengganti.', true);
        }, 'image/jpeg', 0.9);
    });

    btnRetake.addEventListener('click', function () {
        fileInput.value = '';
        result.classList.add('hidden');
        btnCapture.classList.remove('hidden');
        btnRetake.classList.add('hidden');
        btnStop.classList.remove('hidden');
        startCamera();
    });

    btnStop.addEventListener('click', stopCamera);
    btnStart.addEventListener('click', startCamera);

    // Fallback galeri: cerminkan file ke input utama + preview
    fileInput.addEventListener('change', function () {
        if (fileInput.files && fileInput.files.length > 0) {
            const reader = new FileReader();
            reader.onload = function (e) {
                result.src = e.target.result;
                result.classList.remove('hidden');
                placeholder.classList.add('hidden');
                video.classList.add('hidden');
                btnStart.classList.add('hidden');
                btnCapture.classList.add('hidden');
                btnRetake.classList.remove('hidden');
                cameraControls.classList.remove('hidden');
                btnStop.classList.add('hidden');
            };
            reader.readAsDataURL(fileInput.files[0]);
            setStatus('✅ Foto dari galeri dipilih.', true);
        }
    });
});
</script>
@endpush