@extends('layouts.public')

@section('title', 'Reset Password')
@section('meta_description', 'Buat password baru untuk akun GoMad Anda.')
@section('og_image', asset('images/og-login.jpg'))

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-20 px-6 bg-[#F9FAFB]">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <div class="flex items-center justify-center gap-1 mb-4">
                <span class="text-2xl font-bold tracking-tighter text-[#111827]">GO</span>
                <span class="text-[#BA1826] text-2xl font-bold tracking-tighter">MAD</span>
            </div>
            <h1 class="text-3xl font-bold text-[#111827] mb-2">Reset Password</h1>
            <p class="text-gray-500 font-light text-sm">Buat password baru untuk akun Anda</p>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[10px] mb-6 text-sm">
            {{ $errors->first() }}
        </div>
        @endif

        <div class="bg-white p-8 shadow-gomad border border-[#E5E7EB] rounded-[12px]" x-data="{ showPassword: false, showConfirm: false }">
            <form action="{{ route('password.update') }}" method="POST">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="mb-4">
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Password Baru</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" name="password"
                               class="w-full px-0 py-2 pr-8 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition"
                               placeholder="Minimal 8 karakter" required minlength="8">
                        <button type="button" @click="showPassword = !showPassword" class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#111827]">
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Konfirmasi Password</label>
                    <div class="relative">
                        <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation"
                               class="w-full px-0 py-2 pr-8 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition"
                               placeholder="Ulangi password" required>
                        <button type="button" @click="showConfirm = !showConfirm" class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#111827]">
                            <svg x-show="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full btn-gomad-primary text-base py-3 rounded-[10px]">
                    Reset Password
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm text-[#BA1826] hover:underline font-medium">
                    Kembali ke halaman login
                </a>
            </div>
        </div>
    </div>
</div>
@endsection