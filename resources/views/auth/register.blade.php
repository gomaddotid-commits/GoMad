@extends('layouts.public')

@section('title', 'Daftar')
@section('meta_description', 'Daftar akun GoMad dan mulai perjalanan Anda. Tersedia untuk Customer, Agency, dan Warung.')
@section('og_image', asset('images/og-register.jpg'))

@section('content')
<div class="section">
    <div class="container-custom">
        <div class="max-w-md mx-auto">
            <div class="text-center mb-8">
                <a href="{{ route('home') }}" class="inline-block mb-6">
                    <img src="{{ asset('images/logo.svg') }}" alt="GoMad" class="h-10 mx-auto">
                </a>
                <h1 class="text-2xl font-bold text-secondary mb-2">Daftar GoMad</h1>
                <p class="text-gray-600">Buat akun dan mulai perjalanan Anda</p>
            </div>

            @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm">
                @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
                @endforeach
            </div>
            @endif

            <div class="card p-6 md:p-8" x-data="{ 
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
                }
            }">
                <form action="{{ route('register') }}" method="POST">
                    @csrf

                    {{-- Pilihan Role --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-secondary mb-3">Daftar Sebagai</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="role-card border-2 rounded-xl p-3 text-center cursor-pointer transition" 
                                   :class="role === 'customer' ? 'border-primary-600 bg-primary-50' : 'border-gray-200 hover:border-gray-300'"
                                   @click="role = 'customer'">
                                <input type="radio" name="role" value="customer" class="hidden" checked>
                                <span class="text-xl block mb-1">🧑</span>
                                <span class="text-xs font-semibold">Customer</span>
                            </label>
                            <label class="role-card border-2 rounded-xl p-3 text-center cursor-pointer transition" 
                                   :class="role === 'agency' ? 'border-primary-600 bg-primary-50' : 'border-gray-200 hover:border-gray-300'"
                                   @click="role = 'agency'">
                                <input type="radio" name="role" value="agency" class="hidden">
                                <span class="text-xl block mb-1">🏢</span>
                                <span class="text-xs font-semibold">Agency</span>
                            </label>
                            <label class="role-card border-2 rounded-xl p-3 text-center cursor-pointer transition" 
                                   :class="role === 'payment_agent' ? 'border-primary-600 bg-primary-50' : 'border-gray-200 hover:border-gray-300'"
                                   @click="role = 'payment_agent'">
                                <input type="radio" name="role" value="payment_agent" class="hidden">
                                <span class="text-xl block mb-1">🏪</span>
                                <span class="text-xs font-semibold">Warung</span>
                            </label>
                        </div>
                    </div>

                    <div class="space-y-4">
                        {{-- Nama Lengkap --}}
                        <div>
                            <label class="block text-sm font-medium text-secondary mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" 
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 focus:border-primary-600 bg-gray-50 transition" 
                                   placeholder="Nama lengkap Anda" required>
                        </div>
                        
                        {{-- Email --}}
                        <div>
                            <label class="block text-sm font-medium text-secondary mb-1">Alamat Email <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" 
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 focus:border-primary-600 bg-gray-50 transition" 
                                   placeholder="nama@email.com" required>
                        </div>

                        {{-- Nomor HP / WhatsApp --}}
                        <div>
                            <label class="block text-sm font-medium text-secondary mb-1">Nomor HP (WhatsApp) <span class="text-red-500">*</span></label>
                            <input type="text" name="phone" value="{{ old('phone') }}" 
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 focus:border-primary-600 bg-gray-50 transition" 
                                   placeholder="081234567890" required>
                            <p class="text-xs text-gray-500 mt-1">Digunakan untuk notifikasi booking via WhatsApp</p>
                        </div>

                        {{-- Password --}}
                        <div>
                            <label class="block text-sm font-medium text-secondary mb-1">Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password"
                                       class="w-full px-4 py-3 pr-12 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 focus:border-primary-600 bg-gray-50 transition" 
                                       placeholder="Minimal 8 karakter" required minlength="8">
                                <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            {{-- Password Strength --}}
                            <div class="mt-2" x-show="password.length > 0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs text-gray-500">Kekuatan password:</span>
                                    <span class="text-xs font-medium" x-text="passwordStrength.text"></span>
                                </div>
                                <div class="bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-300" 
                                         :class="passwordStrength.color" 
                                         :style="'width: ' + passwordStrength.width"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Konfirmasi Password --}}
                        <div>
                            <label class="block text-sm font-medium text-secondary mb-1">Konfirmasi Password <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation"
                                       class="w-full px-4 py-3 pr-12 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 focus:border-primary-600 bg-gray-50 transition" 
                                       placeholder="Ulangi password" required>
                                <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg x-show="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Kode Referral --}}
                        <div>
                            <label class="block text-sm font-medium text-secondary mb-1">
                                Kode Referral <span class="text-xs text-gray-400">(Opsional)</span>
                            </label>
                            <input type="text" name="referral_code" value="{{ old('referral_code', request('ref')) }}" 
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-gray-50 uppercase transition" 
                                   placeholder="Contoh: BUDI123">
                            <p class="text-xs text-gray-500 mt-1">Dapatkan diskon dengan kode referral dari teman</p>
                        </div>
                    </div>

                    <button type="submit" class="w-full btn-primary mt-6 text-base py-3">
                        Daftar Sekarang
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Sudah punya akun? 
                        <a href="{{ route('login') }}" class="text-primary-600 font-semibold hover:underline">Masuk di sini</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection