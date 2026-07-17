@extends('layouts.public')

@section('title', 'Daftar')
@section('meta_description', 'Daftar akun GoMad dan mulai perjalanan Anda. Tersedia untuk Customer, Agency, dan Warung.')
@section('og_image', asset('images/og-register.jpg'))

@section('content')
<div class="min-h-screen bg-[#F5F5F5] flex items-start justify-center py-20 px-6">
    <div class="w-full max-w-xl">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold tracking-tight text-[#111111]">Daftar GoMad</h1>
            <p class="text-gray-400 font-light text-sm mt-1" id="registerSubtitle">Pilih jenis akun Anda</p>
        </div>

        @if($errors->any())
        <div class="bg-[#F5F5F5] border border-[#C1121F] text-[#C1121F] px-4 py-3 rounded-[12px] mb-6 text-sm font-medium">
            @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
            @endforeach
        </div>
        @endif

        <div class="border border-[#E5E5E5] rounded-[12px] p-8 bg-white shadow-sm" x-data="{ 
            step: 1,
            role: 'customer',
            showPassword: false,
            showConfirm: false,
            password: '',
            get passwordStrength() {
                if (this.password.length === 0) return { text: '', color: '', width: '0%' };
                if (this.password.length < 6) return { text: 'Lemah', color: 'bg-red-500', width: '25%' };
                if (this.password.length < 8) return { text: 'Cukup', color: 'bg-yellow-500', width: '50%' };
                if (this.password.length < 10) return { text: 'Baik', color: 'bg-blue-500', width: '75%' };
                return { text: 'Kuat', color: 'bg-green-500', width: '100%' };
            },
            selectRole(selected) {
                this.role = selected;
                this.step = 2;
                var titles = {
                    'customer': 'Daftar sebagai Customer',
                    'agency': 'Daftar sebagai Agency',
                    'payment_agent': 'Daftar sebagai Warung GoMad'
                };
                document.getElementById('registerSubtitle').textContent = titles[selected];
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
            backToStep1() {
                this.step = 1;
                document.getElementById('registerSubtitle').textContent = 'Pilih jenis akun untuk melanjutkan';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }">
            
            {{-- STEP 1: Pilih Role --}}
            <div x-show="step === 1" class="space-y-4">
                {{-- Customer --}}
                <button @click="selectRole('customer')"
                        class="w-full flex items-center gap-4 p-5 border border-[#E5E5E5] rounded-[12px] hover:border-[#C1121F] transition group text-left bg-white">
                    <div class="w-12 h-12 bg-[#F5F5F5] rounded-[12px] flex items-center justify-center text-xl group-hover:bg-[#C1121F]/10 transition flex-shrink-0">
                        <svg class="w-6 h-6 text-[#111111]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-[#111111]">Customer</h3>
                        <p class="text-sm text-gray-500 truncate font-light">Booking travel, cari jadwal, bayar online atau di warung</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-300 group-hover:text-[#C1121F] transition flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>

                {{-- Agency --}}
                <button @click="selectRole('agency')"
                        class="w-full flex items-center gap-4 p-5 border border-[#E5E5E5] rounded-[12px] hover:border-[#C1121F] transition group text-left bg-white">
                    <div class="w-12 h-12 bg-[#F5F5F5] rounded-[12px] flex items-center justify-center text-xl group-hover:bg-[#C1121F]/10 transition flex-shrink-0">
                        <svg class="w-6 h-6 text-[#111111]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-[#111111]">Agency</h3>
                        <p class="text-sm text-gray-500 truncate font-light">Kelola jadwal, armada, driver, dan terima booking</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-300 group-hover:text-[#C1121F] transition flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>

                {{-- Warung --}}
                <button @click="selectRole('payment_agent')"
                        class="w-full flex items-center gap-4 p-5 border border-[#E5E5E5] rounded-[12px] hover:border-[#C1121F] transition group text-left bg-white">
                    <div class="w-12 h-12 bg-[#F5F5F5] rounded-[12px] flex items-center justify-center text-xl group-hover:bg-[#C1121F]/10 transition flex-shrink-0">
                        <svg class="w-6 h-6 text-[#111111]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-[#111111]">Warung GoMad</h3>
                        <p class="text-sm text-gray-500 truncate font-light">Terima pembayaran cash dari customer, jadi mitra resmi</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-300 group-hover:text-[#C1121F] transition flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>

                <div class="mt-8 text-center">
                    <p class="text-sm text-gray-500">
                        Sudah punya akun?
                        <a href="{{ route('login') }}" class="text-[#C1121F] font-semibold hover:underline">Masuk di sini</a>
                    </p>
                </div>
            </div>

            {{-- STEP 2: Form Register --}}
            <div x-show="step === 2">
                {{-- Back Button --}}
                <button @click="backToStep1()" class="text-sm text-gray-400 hover:text-[#C1121F] mb-6 flex items-center gap-1 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Ganti jenis akun
                </button>

                <form action="{{ route('register') }}" method="POST">
                    @csrf
                    <input type="hidden" name="role" x-bind:value="role">

                    <div class="space-y-5">
                        {{-- Nama --}}
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">
                                Nama Lengkap
                                <span class="text-[#C1121F]">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                                   placeholder="Nama lengkap Anda" required>
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Alamat Email <span class="text-[#C1121F]">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                                   placeholder="nama@email.com" required>
                        </div>

                        {{-- HP --}}
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">
                                Nomor HP (WhatsApp)
                                <span class="text-[#C1121F]">*</span>
                            </label>
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                                   placeholder="081234567890" required>
                        </div>

                        {{-- Password --}}
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Password <span class="text-[#C1121F]">*</span></label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password"
                                       class="w-full px-0 py-2 pr-8 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                                       placeholder="Minimal 8 karakter" required minlength="8">
                                <button type="button" @click="showPassword = !showPassword" class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#111111] transition-colors">
                                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            <div class="mt-2" x-show="password.length > 0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs text-gray-400 font-mono uppercase tracking-wider">Kekuatan:</span>
                                    <span class="text-xs font-medium" x-text="passwordStrength.text"></span>
                                </div>
                                <div class="bg-[#E5E5E5] rounded-full h-1.5 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-300"
                                         :class="passwordStrength.color"
                                         :style="'width: ' + passwordStrength.width"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Konfirmasi Password --}}
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Konfirmasi Password <span class="text-[#C1121F]">*</span></label>
                            <div class="relative">
                                <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation"
                                       class="w-full px-0 py-2 pr-8 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                                       placeholder="Ulangi password" required>
                                <button type="button" @click="showConfirm = !showConfirm" class="absolute right-0 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#111111] transition-colors">
                                    <svg x-show="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Referral (HANYA UNTUK CUSTOMER) --}}
                        <div x-show="role === 'customer'" x-cloak>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">
                                Kode Referral <span class="text-xs text-gray-400 font-light">(Opsional)</span>
                            </label>
                            <input type="text" name="referral_code" value="{{ old('referral_code', request('ref')) }}"
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] uppercase transition"
                                   placeholder="Contoh: BUDI123">
                            <p class="text-xs text-gray-400 mt-1 font-light">Dapatkan diskon dengan kode referral dari teman Anda</p>
                        </div>

                        {{-- Google Register (HANYA UNTUK CUSTOMER) --}}
                        <div x-show="role === 'customer'" x-cloak>
                            <div class="relative my-6">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-[#E5E5E5]"></div>
                                </div>
                                <div class="relative flex justify-center text-sm">
                                    <span class="px-3 bg-white text-gray-400 font-mono uppercase tracking-wider text-xs">Atau daftar dengan</span>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('google.login') }}"
                                   class="w-full flex items-center justify-center gap-3 px-4 py-3 border border-[#E5E5E5] rounded-[12px] text-[#111111] font-medium hover:bg-[#F5F5F5] transition">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                    </svg>
                                    Daftar dengan Google
                                </a>
                            </div>
                        </div>

                        <button type="submit" class="w-full btn-gomad-primary mt-2 text-base py-3 rounded-[12px]">
                            Daftar Sekarang
                        </button>
                    </div>
                </form>

                <div class="mt-6 text-center text-sm">
                    <span class="text-gray-500">Sudah punya akun?</span>
                    <a href="{{ route('login') }}" class="text-[#C1121F] font-semibold hover:underline">Masuk di sini</a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection