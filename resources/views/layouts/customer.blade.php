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
<body class="bg-[#F5F5F5] font-sans text-[#111111]" x-data="{ mobileMenu: false }">

    {{-- HEADER CUSTOMER --}}
    <header class="bg-white border-b border-[#E5E5E5] sticky top-0 z-40 shadow-sm">
        <div class="container-magazine">
            <div class="flex items-center justify-between h-14 md:h-16">
                <div class="flex items-center gap-3">
                    <a href="{{ route('customer.home') }}" class="flex items-center gap-2">
                        <div class="flex items-center gap-1">
                            <span class="text-xl font-bold tracking-tighter text-[#111111]">GO</span>
                            <span class="text-[#C1121F] text-xl font-bold tracking-tighter">MAD</span>
                        </div>
                    </a>
                    <span class="hidden md:inline text-[10px] font-mono uppercase tracking-wider border border-[#C1121F] text-[#C1121F] px-2 py-0.5 rounded-full">Customer</span>
                </div>
                
                {{-- ═══════════════════════════════════ --}}
                {{-- TAB SWITCH (DESKTOP ONLY) --}}
                {{-- ═══════════════════════════════════ --}}
                @php
                    $isRentalMode = request()->is('customer/rental*') || request()->is('customer/documents*');
                @endphp
                <div class="hidden md:flex bg-[#F5F5F5] rounded-lg p-1">
                    <a href="{{ route('customer.search') }}" 
                       class="px-4 py-2 rounded-md text-sm font-medium transition {{ !$isRentalMode ? 'bg-white shadow text-[#C1121F]' : 'text-gray-500 hover:text-[#111111]' }}">
                        🚐 Travel
                    </a>
                    <a href="{{ route('customer.rental.browse') }}" 
                       class="px-4 py-2 rounded-md text-sm font-medium transition {{ $isRentalMode ? 'bg-white shadow text-[#C1121F]' : 'text-gray-500 hover:text-[#111111]' }}">
                        🚗 Rental
                    </a>
                </div>

                {{-- Desktop Nav --}}
                <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-gray-500">
                    <a href="{{ route('customer.home') }}" class="hover:text-[#C1121F] transition {{ request()->routeIs('customer.home') ? 'text-[#C1121F]' : '' }}">Home</a>
                    
                    @if(!$isRentalMode)
                        <a href="{{ route('customer.search') }}" class="hover:text-[#C1121F] transition {{ request()->routeIs('customer.search') ? 'text-[#C1121F]' : '' }}">Cari Jadwal</a>
                        <a href="{{ route('customer.bookings') }}" class="hover:text-[#C1121F] transition {{ request()->routeIs('customer.bookings*') ? 'text-[#C1121F]' : '' }}">Booking Saya</a>
                    @else
                        <a href="{{ route('customer.rental.browse') }}" class="hover:text-[#C1121F] transition {{ request()->routeIs('customer.rental.browse') ? 'text-[#C1121F]' : '' }}">Cari Mobil</a>
                        <a href="{{ route('customer.rentals') }}" class="hover:text-[#C1121F] transition {{ request()->routeIs('customer.rentals*') ? 'text-[#C1121F]' : '' }}">Rental Saya</a>
                        <a href="{{ route('customer.documents') }}" class="hover:text-[#C1121F] transition {{ request()->routeIs('customer.documents*') ? 'text-[#C1121F]' : '' }}">Dokumen</a>
                    @endif
                    
                    <a href="{{ route('customer.profile') }}" class="hover:text-[#C1121F] transition {{ request()->routeIs('customer.profile') ? 'text-[#C1121F]' : '' }}">Profil</a>
                </nav>

                {{-- Desktop Auth --}}
                <div class="hidden md:flex items-center gap-3">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-sm text-gray-400 hover:text-[#C1121F] transition font-medium">Keluar</button>
                    </form>
                </div>

                {{-- Mobile Toggle --}}
                <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 text-[#111111]">
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
            <div class="absolute inset-0 bg-[#111111]/50"></div>
            
            {{-- Drawer --}}
            <div class="absolute right-0 top-0 h-full w-3/4 max-w-sm bg-white shadow-2xl flex flex-col" @click.stop="">
                {{-- Header --}}
                <div class="p-5 border-b border-[#E5E5E5] flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-bold tracking-tighter text-[#111111]">GO</span>
                        <span class="text-[#C1121F] text-xl font-bold tracking-tighter">MAD</span>
                    </div>
                    <button @click="mobileMenu = false" class="p-2 text-gray-400 hover:text-[#111111]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- User Info --}}
                <div class="p-5 border-b border-[#E5E5E5]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[#C1121F] flex items-center justify-center text-white font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-semibold text-[#111111] text-sm">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════════════════════════ --}}
                {{-- DRAWER MENU LENGKAP --}}
                {{-- ═══════════════════════════════════ --}}
                <div class="flex-1 overflow-y-auto py-3">
                    {{-- AKSES DASAR --}}
                    <div class="px-5 py-2">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mb-2">Akses Dasar</p>
                        <a href="{{ route('customer.home') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm {{ request()->routeIs('customer.home') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}" @click="mobileMenu = false">
                            <span>🏠</span> Home
                        </a>
                        <a href="{{ route('customer.profile') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm {{ request()->routeIs('customer.profile') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}" @click="mobileMenu = false">
                            <span>👤</span> Profil
                        </a>
                    </div>

                    {{-- AKSES LANJUTAN TRAVEL --}}
                    <div class="px-5 py-2">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mb-2">Modul Travel</p>
                        <a href="{{ route('customer.search') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm {{ request()->routeIs('customer.search') && !$isRentalMode ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}" @click="mobileMenu = false">
                            <span>🔍</span> Cari Jadwal
                        </a>
                        <a href="{{ route('customer.bookings') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm {{ request()->routeIs('customer.bookings*') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}" @click="mobileMenu = false">
                            <span>🎫</span> Booking Saya
                        </a>
                    </div>

                    {{-- AKSES LANJUTAN RENTAL --}}
                    <div class="px-5 py-2">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mb-2">Modul Rental</p>
                        <a href="{{ route('customer.rental.browse') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm {{ request()->routeIs('customer.rental.browse') && $isRentalMode ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}" @click="mobileMenu = false">
                            <span>🔍</span> Cari Mobil
                        </a>
                        <a href="{{ route('customer.rentals') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm {{ request()->routeIs('customer.rentals*') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}" @click="mobileMenu = false">
                            <span>🚗</span> Rental Saya
                        </a>
                        <a href="{{ route('customer.documents') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm {{ request()->routeIs('customer.documents*') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}" @click="mobileMenu = false">
                            <span>📄</span> Dokumen Saya
                        </a>
                    </div>
                </div>

                {{-- Logout --}}
                <div class="p-5 border-t border-[#E5E5E5]">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full text-left text-sm text-gray-400 hover:text-[#C1121F] transition py-2 font-medium">🚪 Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    {{-- CONTENT --}}
    <main class="min-h-screen pb-20 md:pb-0">
        @yield('content')
    </main>

    {{-- ═══════════════════════════════════ --}}
    {{-- BOTTOM NAV MOBILE --}}
    {{-- ═══════════════════════════════════ --}}
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-[#E5E5E5] md:hidden z-40">
        <div class="flex items-center justify-around py-2">
            {{-- Home --}}
            <a href="{{ route('customer.home') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('customer.home') ? 'text-[#C1121F]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3"/></svg>
                <span>Home</span>
            </a>
            
            {{-- Cari --}}
            @if(!$isRentalMode)
            <a href="{{ route('customer.search') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('customer.search') ? 'text-[#C1121F]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span>Cari</span>
            </a>
            @else
            <a href="{{ route('customer.rental.browse') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('customer.rental.browse') ? 'text-[#C1121F]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span>Cari</span>
            </a>
            @endif
            
            {{-- Switch Modul (Timbul) --}}
            <a href="{{ $isRentalMode ? route('customer.search') : route('customer.rental.browse') }}" 
               class="flex flex-col items-center gap-1 text-[10px] {{ $isRentalMode ? 'text-gray-500' : 'text-gray-500' }}">
                <div class="w-9 h-9 rounded-full bg-[#C1121F] text-white flex items-center justify-center text-sm -mt-4 shadow-lg border-2 border-white">
                    {{ $isRentalMode ? '🚐' : '🚗' }}
                </div>
                <span class="font-medium">{{ $isRentalMode ? 'Travel' : 'Rental' }}</span>
            </a>
            
            {{-- Booking/Rental Saya --}}
            @if(!$isRentalMode)
            <a href="{{ route('customer.bookings') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('customer.bookings*') ? 'text-[#C1121F]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                <span>Booking</span>
            </a>
            @else
            <a href="{{ route('customer.rentals') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('customer.rentals*') ? 'text-[#C1121F]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                <span>Rental</span>
            </a>
            @endif
            
            {{-- Profil --}}
            <a href="{{ route('customer.profile') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('customer.profile') ? 'text-[#C1121F]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14"/></svg>
                <span>Profil</span>
            </a>
        </div>
    </nav>

    @stack('scripts')
</body>
</html>