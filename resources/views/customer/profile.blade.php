@extends('layouts.customer')

@section('title', 'Profil')
@section('content')
<div class="px-4 py-8">
    <h1 class="text-lg font-bold text-secondary mb-6">Profil Saya</h1>

    {{-- Referral Card --}}
    @php
        $referralCode = \App\Models\ReferralCode::where('user_id', auth()->id())->first();
        if (!$referralCode) { $referralCode = app(\App\Services\PromoService::class)->generateReferralCode(auth()->user()); }
    @endphp
    <div class="bg-gradient-to-br from-primary-600 to-primary-800 rounded-2xl shadow-sm border border-primary-700 p-6 mb-6 text-white">
        <h2 class="text-xl font-bold mb-2">Ajak Teman, Dapat Diskon!</h2>
        <p class="text-sm text-white/80 mb-4">Bagikan kode referral Anda. Setiap teman yang daftar dan transaksi, Anda dapat diskon s/d 50%!</p>
        
        <div class="bg-white/15 backdrop-blur rounded-xl p-4 mb-4 text-center">
            <p class="text-xs text-white/70 mb-1">Kode Referral Anda</p>
            <p class="text-3xl font-mono font-bold tracking-widest">{{ $referralCode->code }}</p>
        </div>
        
        <div class="bg-white/15 backdrop-blur rounded-xl p-3 mb-4">
            <p class="text-xs text-white/70 mb-1">Atau bagikan link:</p>
            <div class="flex items-center gap-2">
                <input type="text" id="referralLink" readonly value="{{ route('register', ['ref' => $referralCode->code]) }}" class="flex-1 px-3 py-2 rounded-lg text-gray-800 text-sm bg-white">
                <button onclick="copyReferralLink()" class="bg-white text-primary-600 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-100 transition">Copy</button>
            </div>
        </div>
        
        <div class="flex gap-2">
            <a href="https://wa.me/?text={{ urlencode('Daftar GoMad pakai kode referral saya: ' . $referralCode->code . ' 🎁\nDaftar di: ' . route('register', ['ref' => $referralCode->code])) }}" target="_blank" class="flex-1 bg-green-500 text-white py-2.5 rounded-xl text-sm font-semibold text-center hover:bg-green-600 transition">💬 Share WhatsApp</a>
            <button onclick="copyReferralCode()" class="bg-white/20 text-white py-2.5 px-4 rounded-xl text-sm font-semibold hover:bg-white/30 transition">Copy Kode</button>
        </div>
        
        <div class="grid grid-cols-2 gap-3 mt-4">
            <div class="bg-white/15 rounded-xl p-3 text-center">
                <p class="text-xs text-white/70">Total Mengajak</p>
                <p class="text-2xl font-bold">{{ $referralCode->total_referred }}</p>
            </div>
            <div class="bg-white/15 rounded-xl p-3 text-center">
                <p class="text-xs text-white/70">Berhasil</p>
                <p class="text-2xl font-bold">{{ $referralCode->successful_referrals }}</p>
            </div>
        </div>
    </div>

    {{-- Form Profil --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-bold text-secondary mb-4">Informasi Akun</h3>
        <form action="{{ route('customer.profile.update') }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Nama <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Email</label>
                <input type="email" value="{{ auth()->user()->email }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-100 text-gray-500" disabled>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Nomor HP <span class="text-red-500">*</span></label>
                <input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" required>
            </div>
            <button type="submit" class="bg-primary-600 text-white w-full py-3 rounded-xl font-semibold hover:bg-primary-700 transition active:scale-95">Simpan</button>
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