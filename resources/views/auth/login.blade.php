@extends('layouts.public')

@section('title', 'Masuk')
@section('meta_description', 'Masuk ke akun GoMad Anda. Booking travel, cek tiket, dan nikmati perjalanan dengan GoMad.')
@section('og_image', asset('images/og-login.jpg'))

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-20 px-6 bg-[#F9FAFB]">
    <div class="w-full max-w-md">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-[#111827]">Masuk</h1>
            <p class="text-gray-500 font-light text-sm mt-1">Lanjutkan perjalanan Anda</p>
        </div>

        @if($errors->any())
        <div class="bg-[#F9FAFB] border border-[#BA1826] text-[#BA1826] px-4 py-3 rounded-[10px] mb-6 text-sm font-medium">
            {{ $errors->first() }}
        </div>
        @endif

        <div class="bg-white p-8 shadow-gomad border border-[#E5E7EB] rounded-[12px]">
            <form action="{{ route('login') }}" method="POST" x-data="{ showPassword: false }">
                @csrf
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" 
                               class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none transition bg-transparent text-[#111827]" 
                               placeholder="nama@email.com" required>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Password</label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" name="password" 
                                   class="w-full px-0 py-2 pr-8 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none transition bg-transparent text-[#111827]" 
                                   placeholder="Masukkan password" required>
                            <button type="button" @click="showPassword = !showPassword" class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#111827] transition-colors">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2 text-gray-500">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-[#E5E7EB] text-[#BA1826] focus:ring-[#BA1826]">
                            Ingat saya
                        </label>
                        <a href="{{ route('password.request') }}" class="text-sm text-primary-600 hover:underline">Lupa password?</a>
                    </div>
                </div>

                <button type="submit" class="w-full btn-gomad-primary mt-6 text-base py-3 rounded-[10px]">
                    Masuk
                </button>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-[#E5E7EB]"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-400 font-mono uppercase tracking-wider text-xs">Atau masuk dengan</span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('google.login') }}"
                           class="w-full flex items-center justify-center gap-3 px-4 py-3 border border-[#E5E7EB] rounded-[10px] text-[#111827] font-medium hover:bg-[#F9FAFB] transition">
                            <svg class="w-5 h-5" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            Masuk dengan Google
                        </a>
                    </div>
                </div>
            </form>

            <div class="mt-6 text-center text-sm">
                <span class="text-gray-500">Belum punya akun?</span>
                <a href="{{ route('register') }}" class="text-[#BA1826] font-semibold hover:underline">Daftar sekarang</a>
            </div>
        </div>
    </div>
</div>
@endsection