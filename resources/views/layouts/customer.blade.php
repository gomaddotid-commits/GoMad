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
<body class="bg-gray-50 font-sans text-secondary" x-data="{ mobileMenu: false }">

    {{-- HEADER --}}
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="container-custom">
            <div class="flex items-center justify-between h-14 md:h-16">
                <div class="flex items-center gap-3">
                    <a href="{{ route('customer.home') }}" class="flex items-center gap-2">
                        <img src="{{ asset('images/logo.svg') }}" alt="GoMad" class="h-6 md:h-7">
                    </a>
                    <span class="hidden md:inline text-xs bg-primary-50 text-primary-600 px-2 py-0.5 rounded-full font-medium">Customer</span>
                </div>
                
                {{-- Desktop Nav --}}
                <nav class="hidden md:flex items-center gap-6 text-sm">
                    <a href="{{ route('customer.home') }}" class="text-gray-600 hover:text-primary-600 transition {{ request()->routeIs('customer.home') ? 'text-primary-600 font-semibold' : '' }}">Home</a>
                    <a href="{{ route('customer.search') }}" class="text-gray-600 hover:text-primary-600 transition {{ request()->routeIs('customer.search') ? 'text-primary-600 font-semibold' : '' }}">Cari Jadwal</a>
                    <a href="{{ route('customer.bookings') }}" class="text-gray-600 hover:text-primary-600 transition {{ request()->routeIs('customer.bookings') ? 'text-primary-600 font-semibold' : '' }}">Booking Saya</a>
                    <a href="{{ route('customer.profile') }}" class="text-gray-600 hover:text-primary-600 transition {{ request()->routeIs('customer.profile') ? 'text-primary-600 font-semibold' : '' }}">Profil</a>
                </nav>

                {{-- Desktop Auth --}}
                <div class="hidden md:flex items-center gap-3">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 hover:text-red-500 transition">Keluar</button>
                    </form>
                </div>

                {{-- Mobile Toggle --}}
                <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 text-gray-600">
                    <svg x-show="!mobileMenu" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileMenu" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Mobile Menu --}}
        <div x-show="mobileMenu" x-cloak class="md:hidden bg-white border-t shadow-lg" @click.outside="mobileMenu = false">
            <div class="px-4 py-4 space-y-3">
                <a href="{{ route('customer.home') }}" class="block text-sm font-medium text-gray-700 py-2">Home</a>
                <a href="{{ route('customer.search') }}" class="block text-sm font-medium text-gray-700 py-2">Cari Jadwal</a>
                <a href="{{ route('customer.bookings') }}" class="block text-sm font-medium text-gray-700 py-2">Booking Saya</a>
                <a href="{{ route('customer.profile') }}" class="block text-sm font-medium text-gray-700 py-2">Profil</a>
                <hr class="border-gray-100">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-red-500 text-sm">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    {{-- CONTENT --}}
    <main class="min-h-screen pb-20 md:pb-0">
        @yield('content')
    </main>

    {{-- BOTTOM NAV MOBILE --}}
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 md:hidden z-50">
        <div class="flex items-center justify-around py-2">
            <a href="{{ route('customer.home') }}" class="flex flex-col items-center gap-1 text-xs {{ request()->routeIs('customer.home') ? 'text-primary-600' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Home</span>
            </a>
            <a href="{{ route('customer.search') }}" class="flex flex-col items-center gap-1 text-xs {{ request()->routeIs('customer.search') ? 'text-primary-600' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span>Cari</span>
            </a>
            <a href="{{ route('customer.bookings') }}" class="flex flex-col items-center gap-1 text-xs {{ request()->routeIs('customer.bookings') ? 'text-primary-600' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                <span>Booking</span>
            </a>
            <a href="{{ route('customer.profile') }}" class="flex flex-col items-center gap-1 text-xs {{ request()->routeIs('customer.profile') ? 'text-primary-600' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Profil</span>
            </a>
        </div>
    </nav>

    @stack('scripts')
</body>
</html>