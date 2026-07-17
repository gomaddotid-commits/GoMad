@extends('layouts.public')

@section('title', 'Verifikasi Email')
@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-20 px-6 bg-[#F5F5F5]">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <div class="flex items-center justify-center gap-1 mb-4">
                <span class="text-2xl font-bold tracking-tighter text-[#111111]">GO</span>
                <span class="text-[#C1121F] text-2xl font-bold tracking-tighter">MAD</span>
            </div>
            <h1 class="text-3xl font-bold text-[#111111] mb-2">Verifikasi Email</h1>
            <p class="text-gray-500 font-light text-sm">Cek inbox email Anda untuk link verifikasi</p>
        </div>

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-[12px] mb-6 text-sm">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[12px] mb-6 text-sm">
            {{ session('error') }}
        </div>
        @endif

        <div class="bg-white p-8 shadow-sm border border-[#E5E5E5] rounded-[12px] text-center">
            <div class="text-6xl mb-4">📧</div>
            
            <p class="text-[#111111] font-medium mb-2">
                Sebelum melanjutkan, silakan verifikasi email Anda.
            </p>
            <p class="text-gray-500 text-sm mb-6 font-light">
                Kami telah mengirim link verifikasi ke <strong class="text-[#111111]">{{ auth()->user()->email }}</strong>.
                Jika belum menerima, klik tombol di bawah untuk kirim ulang.
            </p>

            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4 mb-6 text-left text-sm">
                <p class="font-medium text-[#111111] mb-2">📝 Catatan:</p>
                <ul class="space-y-1 text-gray-500 font-light">
                    <li>• Cek folder <strong>Spam</strong> atau <strong>Promotions</strong></li>
                    <li>• Link verifikasi berlaku 60 menit</li>
                    <li>• Pastikan email yang didaftarkan sudah benar</li>
                </ul>
            </div>

            <form action="{{ route('verification.send') }}" method="POST" class="mb-4">
                @csrf
                <button type="submit" class="w-full btn-gomad-primary py-3 rounded-[12px] font-semibold">
                    📤 Kirim Ulang Link Verifikasi
                </button>
            </form>

            <div class="border-t border-[#E5E5E5] pt-4">
                <p class="text-sm text-gray-500 mb-3 font-light">
                    Sudah verifikasi? Coba refresh atau
                </p>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-[#C1121F] hover:underline font-medium">
                        Logout & Login Kembali
                    </button>
                </form>
            </div>
        </div>

        <div class="text-center mt-6">
            <p class="text-xs text-gray-400 font-light">
                Butuh bantuan? Hubungi kami di <a href="mailto:support@gomad.id" class="text-[#C1121F] hover:underline">support@gomad.id</a>
            </p>
        </div>
    </div>
</div>
@endsection