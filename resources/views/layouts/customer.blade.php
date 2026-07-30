<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Customer') - GoMad</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-[#F9FAFB] font-sans text-[#111827]" x-data="{ mobileMenu: false }">

    {{-- ✅ TOP ANNOUNCEMENT BANNER --}}
    @php
        $bannerActive = \App\Models\PlatformSetting::getValue('top_banner_active', '0');
        $bannerText = \App\Models\PlatformSetting::getValue('top_banner_text');
        $bannerLink = \App\Models\PlatformSetting::getValue('top_banner_link');
    @endphp

    @if($bannerActive == '1' && $bannerText)
    <div x-data="{ 
        show: true,
        init() {
            if (localStorage.getItem('topBannerClosed') === 'true') {
                this.show = false;
            } else {
                setTimeout(() => { this.show = false }, 5000);
            }
        }
    }" 
    x-show="show" 
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 -translate-y-full"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-full"
    x-cloak
    class="relative z-50">
        <div class="bg-gradient-to-r from-[#BA1826] via-[#E42535] to-[#BA1826] text-white">
            <div class="container-magazine py-2.5 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm font-medium flex-1 justify-center min-w-0">
                    <span>📢</span>
                    <span class="truncate">{{ $bannerText }}</span>
                    @if($bannerLink)
                    <a href="{{ $bannerLink }}" class="underline font-bold hover:text-white/80 transition ml-1 flex-shrink-0">Selengkapnya</a>
                    @endif
                </div>
                <button @click="show = false; localStorage.setItem('topBannerClosed', 'true')" 
                        class="flex-shrink-0 ml-4 p-1.5 rounded-full hover:bg-white/20 transition text-white/80 hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- HEADER CUSTOMER --}}
    <header class="bg-white border-b border-[#E5E7EB] sticky top-0 z-40 shadow-sm">
        <div class="container-magazine">
            <div class="flex items-center justify-between h-14 md:h-16">
                <div class="flex items-center gap-3">
                    <a href="{{ route('customer.home') }}" class="flex items-center gap-2">
                        <div class="flex items-center gap-1">
                            <span class="text-xl font-bold tracking-tighter text-[#111827]">GO</span>
                            <span class="text-[#BA1826] text-xl font-bold tracking-tighter">MAD</span>
                        </div>
                    </a>
                    <span class="hidden md:inline text-[10px] font-mono uppercase tracking-wider border border-[#BA1826] text-[#BA1826] px-2 py-0.5 rounded-full">Customer</span>
                </div>
                
                {{-- TAB SWITCH --}}
                @php
                    $isRentalMode = request()->is('customer/rental*') || request()->is('customer/documents*');
                @endphp
                <div class="hidden md:flex bg-[#F9FAFB] rounded-lg p-1">
                    <a href="{{ route('customer.search') }}" 
                       class="px-4 py-2 rounded-md text-sm font-medium transition {{ !$isRentalMode ? 'bg-white shadow text-[#BA1826]' : 'text-gray-500 hover:text-[#111827]' }}">
                        🚐 Travel
                    </a>
                    <a href="{{ route('customer.rental.browse') }}" 
                       class="px-4 py-2 rounded-md text-sm font-medium transition {{ $isRentalMode ? 'bg-white shadow text-[#BA1826]' : 'text-gray-500 hover:text-[#111827]' }}">
                        🚗 Rental
                    </a>
                </div>

                {{-- Desktop Nav --}}
                <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-gray-500">
                    <a href="{{ route('customer.home') }}" class="hover:text-[#BA1826] transition {{ request()->routeIs('customer.home') ? 'text-[#BA1826]' : '' }}">Home</a>
                    
                    @if(!$isRentalMode)
                        <a href="{{ route('customer.search') }}" class="hover:text-[#BA1826] transition {{ request()->routeIs('customer.search') ? 'text-[#BA1826]' : '' }}">Cari Jadwal</a>
                        <a href="{{ route('customer.bookings') }}" class="hover:text-[#BA1826] transition {{ request()->routeIs('customer.bookings*') ? 'text-[#BA1826]' : '' }}">Booking Saya</a>
                    @else
                        <a href="{{ route('customer.rental.browse') }}" class="hover:text-[#BA1826] transition {{ request()->routeIs('customer.rental.browse') ? 'text-[#BA1826]' : '' }}">Cari Mobil</a>
                        <a href="{{ route('customer.rentals') }}" class="hover:text-[#BA1826] transition {{ request()->routeIs('customer.rentals*') ? 'text-[#BA1826]' : '' }}">Rental Saya</a>
                        <a href="{{ route('customer.documents') }}" class="hover:text-[#BA1826] transition {{ request()->routeIs('customer.documents*') ? 'text-[#BA1826]' : '' }}">Dokumen</a>
                    @endif
                    
                    <a href="{{ route('customer.profile') }}" class="hover:text-[#BA1826] transition {{ request()->routeIs('customer.profile') ? 'text-[#BA1826]' : '' }}">Profil</a>
                </nav>

                {{-- Desktop Auth --}}
                <div class="hidden md:flex items-center gap-3">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-sm text-gray-400 hover:text-[#BA1826] transition font-medium">Keluar</button>
                    </form>
                </div>

                {{-- Mobile Toggle --}}
                <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 text-[#111827]">
                    <svg x-show="!mobileMenu" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileMenu" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- MOBILE DRAWER --}}
        <div x-show="mobileMenu" x-cloak 
             class="fixed inset-0 z-50 md:hidden" 
             @click="mobileMenu = false">
            {{-- Overlay --}}
            <div class="absolute inset-0 bg-[#111827]/50"></div>
            
            {{-- Drawer --}}
            <div class="absolute right-0 top-0 h-full w-3/4 max-w-sm bg-white shadow-2xl flex flex-col" @click.stop="">
                {{-- Header --}}
                <div class="p-5 border-b border-[#E5E7EB] flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-bold tracking-tighter text-[#111827]">GO</span>
                        <span class="text-[#BA1826] text-xl font-bold tracking-tighter">MAD</span>
                    </div>
                    <button @click="mobileMenu = false" class="p-2 text-gray-400 hover:text-[#111827]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- User Info --}}
                <div class="p-5 border-b border-[#E5E7EB] flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[#BA1826] flex items-center justify-center text-white font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-semibold text-[#111827] text-sm">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </div>

                {{-- MENU - Scrollable --}}
                <div class="flex-1 overflow-y-auto py-3">
                    {{-- AKSES DASAR --}}
                    <div class="px-5 py-2">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mb-2">Akses Dasar</p>
                        <a href="{{ route('customer.home') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm {{ request()->routeIs('customer.home') ? 'bg-[#BA1826]/10 text-[#BA1826] font-semibold' : 'text-gray-600 hover:bg-[#F9FAFB]' }}" @click="mobileMenu = false">
                            <span>🏠</span> Home
                        </a>
                        <a href="{{ route('customer.profile') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm {{ request()->routeIs('customer.profile') ? 'bg-[#BA1826]/10 text-[#BA1826] font-semibold' : 'text-gray-600 hover:bg-[#F9FAFB]' }}" @click="mobileMenu = false">
                            <span>👤</span> Profil
                        </a>
                    </div>

                    {{-- MODUL TRAVEL --}}
                    <div class="px-5 py-2">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mb-2">Modul Travel</p>
                        <a href="{{ route('customer.search') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm {{ request()->routeIs('customer.search') && !$isRentalMode ? 'bg-[#BA1826]/10 text-[#BA1826] font-semibold' : 'text-gray-600 hover:bg-[#F9FAFB]' }}" @click="mobileMenu = false">
                            <span>🔍</span> Cari Jadwal
                        </a>
                        <a href="{{ route('customer.bookings') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm {{ request()->routeIs('customer.bookings*') ? 'bg-[#BA1826]/10 text-[#BA1826] font-semibold' : 'text-gray-600 hover:bg-[#F9FAFB]' }}" @click="mobileMenu = false">
                            <span>🎫</span> Booking Saya
                        </a>
                    </div>

                    {{-- MODUL RENTAL --}}
                    <div class="px-5 py-2">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mb-2">Modul Rental</p>
                        <a href="{{ route('customer.rental.browse') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm {{ request()->routeIs('customer.rental.browse') && $isRentalMode ? 'bg-[#BA1826]/10 text-[#BA1826] font-semibold' : 'text-gray-600 hover:bg-[#F9FAFB]' }}" @click="mobileMenu = false">
                            <span>🔍</span> Cari Mobil
                        </a>
                        <a href="{{ route('customer.rentals') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm {{ request()->routeIs('customer.rentals*') ? 'bg-[#BA1826]/10 text-[#BA1826] font-semibold' : 'text-gray-600 hover:bg-[#F9FAFB]' }}" @click="mobileMenu = false">
                            <span>🚗</span> Rental Saya
                        </a>
                        <a href="{{ route('customer.documents') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm {{ request()->routeIs('customer.documents*') ? 'bg-[#BA1826]/10 text-[#BA1826] font-semibold' : 'text-gray-600 hover:bg-[#F9FAFB]' }}" @click="mobileMenu = false">
                            <span>📄</span> Dokumen Saya
                        </a>
                    </div>
                    
                    {{-- LAINNYA --}}
                    <div class="px-5 py-2">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mb-2">Lainnya</p>
                        <a href="{{ route('eticket.public') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm text-gray-600 hover:bg-[#F9FAFB]" @click="mobileMenu = false">
                            <span>🎟️</span> Cek E-Ticket
                        </a>
                        <a href="{{ route('listing') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm text-gray-600 hover:bg-[#F9FAFB]" @click="mobileMenu = false">
                            <span>🏢</span> Daftar Agency
                        </a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm text-red-500 hover:bg-red-50 transition font-medium">
                                <span>🚪</span> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- CONTENT --}}
    <main class="min-h-screen pb-24 md:pb-0">
        @yield('content')
    </main>

    {{-- BOTTOM NAV MOBILE --}}
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-[#E5E7EB] md:hidden z-40 safe-area-bottom">
        <div class="flex items-center justify-around py-2">
            {{-- Home --}}
            <a href="{{ route('customer.home') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('customer.home') ? 'text-[#BA1826]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3"/></svg>
                <span>Home</span>
            </a>
            
            {{-- Travel --}}
            <a href="{{ route('customer.search') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('customer.search') && !$isRentalMode ? 'text-[#BA1826]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span>Travel</span>
            </a>
            
            {{-- Rental --}}
            <a href="{{ route('customer.rental.browse') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('customer.rental*') ? 'text-[#BA1826]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Rental</span>
            </a>
            
            {{-- Booking --}}
            <a href="{{ route('customer.bookings') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('customer.bookings*') ? 'text-[#BA1826]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                <span>Booking</span>
            </a>
            
            {{-- Profil --}}
            <a href="{{ route('customer.profile') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('customer.profile') ? 'text-[#BA1826]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14"/></svg>
                <span>Profil</span>
            </a>
        </div>
    </nav>

    @stack('scripts')
</body>
</html>