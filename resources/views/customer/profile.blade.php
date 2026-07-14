@extends('layouts.customer')

@section('title', 'Profil')
@section('content')
@php
    $isRentalMode = request()->is('customer/rental*') || request()->is('customer/documents*');
    
    // Cek status dokumen untuk rental
    $rentalService = app(\App\Services\RentalService::class);
    $docStatus = $rentalService->getCustomerDocumentStatus(auth()->user());
    $canSelfDrive = $docStatus['is_complete_for_self_drive'];
@endphp

<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-[#111111] mb-6">Profil Saya</h1>

    {{-- Status Mode Aktif --}}
    <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4 mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-2xl">{{ $isRentalMode ? '🚗' : '🚐' }}</span>
            <div>
                <p class="font-semibold text-[#111111] text-sm">Mode Aktif: {{ $isRentalMode ? 'Rental' : 'Travel' }}</p>
                <p class="text-xs text-gray-500 font-light">{{ $isRentalMode ? 'Anda sedang dalam mode Rental Mobil' : 'Anda sedang dalam mode Travel' }}</p>
            </div>
        </div>
        <a href="{{ $isRentalMode ? route('customer.home') : route('customer.rental.browse') }}" 
           class="text-xs text-[#C1121F] hover:underline font-medium">
            Switch ke {{ $isRentalMode ? 'Travel' : 'Rental' }}
        </a>
    </div>

    {{-- Referral Card --}}
    @php
        $referralCode = \App\Models\ReferralCode::where('user_id', auth()->id())->first();
        if (!$referralCode) { $referralCode = app(\App\Services\PromoService::class)->generateReferralCode(auth()->user()); }
    @endphp
    <div class="bg-[#C1121F] rounded-[12px] border border-[#C1121F] p-6 mb-6 text-white shadow-sm">
        <h2 class="text-xl font-bold mb-2">Ajak Teman, Dapat Diskon!</h2>
        <p class="text-sm text-white/80 font-light mb-4">Bagikan kode referral Anda. Setiap teman yang daftar dan transaksi, Anda dapat diskon s/d 50%!</p>
        
        <div class="bg-white/10 backdrop-blur rounded-[12px] p-4 mb-4 text-center border border-white/10">
            <p class="text-[10px] font-mono uppercase tracking-wider text-white/70 mb-1">Kode Referral Anda</p>
            <p class="text-3xl font-mono font-bold tracking-widest">{{ $referralCode->code }}</p>
        </div>
        
        <div class="bg-white/10 backdrop-blur rounded-[12px] p-3 mb-4 border border-white/10">
            <p class="text-[10px] font-mono uppercase tracking-wider text-white/70 mb-1">Atau bagikan link:</p>
            <div class="flex items-center gap-2">
                <input type="text" id="referralLink" readonly value="{{ route('register', ['ref' => $referralCode->code]) }}" class="flex-1 px-3 py-2 rounded-lg text-[#111111] text-sm bg-white">
                <button onclick="copyReferralLink()" class="bg-white text-[#C1121F] px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-100 transition">Copy</button>
            </div>
        </div>
        
        <div class="flex gap-2">
            <a href="https://wa.me/?text={{ urlencode('Daftar GoMad pakai kode referral saya: ' . $referralCode->code . ' 🎁\nDaftar di: ' . route('register', ['ref' => $referralCode->code])) }}" target="_blank" class="flex-1 bg-green-500 text-white py-2.5 rounded-[12px] text-sm font-semibold text-center hover:bg-green-600 transition">💬 Share WhatsApp</a>
            <button onclick="copyReferralCode()" class="bg-white/20 text-white py-2.5 px-4 rounded-[12px] text-sm font-semibold hover:bg-white/30 transition">Copy Kode</button>
        </div>
        
        <div class="grid grid-cols-2 gap-3 mt-4">
            <div class="bg-white/10 rounded-[12px] p-3 text-center border border-white/10">
                <p class="text-[10px] font-mono uppercase tracking-wider text-white/70">Total Mengajak</p>
                <p class="text-2xl font-bold">{{ $referralCode->total_referred }}</p>
            </div>
            <div class="bg-white/10 rounded-[12px] p-3 text-center border border-white/10">
                <p class="text-[10px] font-mono uppercase tracking-wider text-white/70">Berhasil</p>
                <p class="text-2xl font-bold">{{ $referralCode->successful_referrals }}</p>
            </div>
        </div>
    </div>

    {{-- Status Dokumen (Hanya untuk Rental) --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111]">📄 Dokumen Rental</h3>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                @if($docStatus['verification_status'] == 'verified') bg-green-50 text-green-700 border-green-200
                @elseif($docStatus['verification_status'] == 'pending') bg-yellow-50 text-yellow-700 border-yellow-200
                @elseif($docStatus['verification_status'] == 'rejected') bg-red-50 text-red-700 border-red-200
                @else bg-[#F5F5F5] text-gray-500 border-[#E5E5E5] @endif">
                @if($docStatus['verification_status'] == 'verified') ✅ Terverifikasi
                @elseif($docStatus['verification_status'] == 'pending') ⏳ Pending
                @elseif($docStatus['verification_status'] == 'rejected') ❌ Ditolak
                @else 📝 Belum Upload
                @endif
            </span>
        </div>
        
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 font-light">🪪 KTP</span>
                    <span class="text-[10px] font-mono {{ $docStatus['ktp']['verified'] ? 'text-green-600' : ($docStatus['ktp']['uploaded'] ? 'text-yellow-600' : 'text-gray-400') }}">
                        {{ $docStatus['ktp']['verified'] ? '✅' : ($docStatus['ktp']['uploaded'] ? '⏳' : '❌') }}
                    </span>
                </div>
                <p class="text-xs text-gray-400 font-mono mt-1">{{ $docStatus['ktp']['number'] ?? '-' }}</p>
            </div>
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 font-light">🚗 SIM</span>
                    <span class="text-[10px] font-mono {{ $docStatus['sim']['verified'] ? 'text-green-600' : ($docStatus['sim']['uploaded'] ? 'text-yellow-600' : 'text-gray-400') }}">
                        {{ $docStatus['sim']['verified'] ? '✅' : ($docStatus['sim']['uploaded'] ? '⏳' : '❌') }}
                    </span>
                </div>
                <p class="text-xs text-gray-400 font-mono mt-1">{{ $docStatus['sim']['number'] ?? '-' }}</p>
            </div>
        </div>
        
        @if(!$canSelfDrive)
        <div class="mt-3 bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-700">
            <p class="font-medium">⚠️ Lepas Kunci belum tersedia</p>
            <p class="font-light mt-1">Lengkapi KTP & SIM untuk bisa menyewa mobil tanpa supir.</p>
        </div>
        @endif
        
        <a href="{{ route('customer.documents') }}" class="inline-block mt-3 text-[#C1121F] text-sm font-medium hover:underline">
            {{ $docStatus['has_documents'] ? 'Update Dokumen →' : 'Upload Dokumen →' }}
        </a>
    </div>

    {{-- Form Profil --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
        <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">Informasi Akun</h3>
        <form action="{{ route('customer.profile.update') }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nama <span class="text-[#C1121F]">*</span></label>
                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
            </div>
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Email</label>
                <input type="email" value="{{ auth()->user()->email }}" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] bg-transparent text-gray-400 cursor-not-allowed" disabled>
            </div>
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nomor HP <span class="text-[#C1121F]">*</span></label>
                <input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
            </div>
            <button type="submit" class="btn-gomad-primary w-full py-3 rounded-[12px] font-semibold mt-2">Simpan</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function copyReferralLink() { var input = document.getElementById('referralLink'); input.select(); document.execCommand('copy'); alert('Link referral dicopy!'); }
function copyReferralCode() { navigator.clipboard.writeText('{{ $referralCode->code }}'); alert('Kode referral dicopy!'); }
</script>
@endpush
@endsection