<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO Meta --}}
    <meta name="description" content="@yield('meta_description', 'GoMad - Mobilitas orèng Madhurâ. Platform booking travel antar kota di Madura. Dijemput di rumah, diantar ke tujuan.')">
    <meta name="keywords" content="@yield('meta_keywords', 'GoMad, travel madura, booking travel, sumenep surabaya, travel antar kota, warung gomad')">
    <meta name="author" content="GoMad">
    <meta name="robots" content="index, follow">

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('og_title', 'GoMad - Mobilitas orèng Madhurâ')">
    <meta property="og:description" content="@yield('og_description', 'Platform booking travel antar kota di Madura. Dijemput di rumah, diantar ke tujuan.')">
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="GoMad">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'GoMad - Mobilitas orèng Madhurâ')">
    <meta name="twitter:description" content="@yield('og_description', 'Platform booking travel antar kota di Madura.')">
    <meta name="twitter:image" content="@yield('og_image', asset('images/og-default.jpg'))">

    {{-- Canonical --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">

    <title>@yield('title', 'GoMad') - Mobilitas orèng Madhurâ</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-white font-sans text-secondary overflow-x-auto">

    {{-- HEADER --}}
    <header class="sticky-header" x-data="{ mobileMenu: false }" id="mainHeader">
        <div class="container-custom">
            <div class="flex items-center justify-between h-16 md:h-20">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2 flex-shrink-0">
                    {{-- Logo Putih (default) --}}
                    <img src="{{ asset('images/bulat-putih.png') }}" alt="GoMad" class="logo-white h-8 md:h-14 w-auto">
                    {{-- Logo Berwarna (saat scroll) --}}
                    <img src="{{ asset('images/bulat-merah.png') }}" alt="GoMad" class="logo-colored h-8 md:h-14 w-auto">
                </a>

                {{-- Menu Desktop --}}
                <nav class="hidden md:flex items-center gap-6 lg:gap-8">
                    <a href="{{ route('home') }}" class="nav-link text-sm font-medium {{ request()->routeIs('home') ? 'active' : '' }}">Beranda</a>
                    <a href="{{ route('search') }}" class="nav-link text-sm font-medium {{ request()->routeIs('search') ? 'active' : '' }}">Cari Jadwal</a>
                    <a href="{{ route('listing') }}" class="nav-link text-sm font-medium {{ request()->routeIs('listing') ? 'active' : '' }}">Agency</a>
                    <a href="{{ route('download-app') }}" class="nav-link text-sm font-medium">Download App</a>
                    <a href="{{ route('eticket.public') }}" class="nav-link text-sm font-medium">Cek E-Ticket</a>
                </nav>

                {{-- Auth Desktop --}}
                <div class="hidden md:flex items-center gap-3 flex-shrink-0">
                    @auth
                        <a href="{{ route(\App\Enums\UserRole::from(auth()->user()->role)->defaultRedirectRoute()) }}"
                           class="btn-auth">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn-auth-outline text-sm">Masuk</a>
                        <a href="{{ route('register') }}" class="btn-auth text-sm">Daftar</a>
                    @endauth
                </div>

                {{-- Mobile Menu Toggle --}}
                <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 mobile-toggle-icon" id="mobileToggle">
                    <svg x-show="!mobileMenu" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenu" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile Menu Dropdown --}}
        <div x-show="mobileMenu" x-cloak class="md:hidden bg-white border-t shadow-lg" @click.outside="mobileMenu = false">
            <div class="px-4 py-4 space-y-3">
                <a href="{{ route('home') }}" class="block text-sm font-medium text-gray-700 py-2 {{ request()->routeIs('home') ? 'text-primary-600' : '' }}">Beranda</a>
                <a href="{{ route('search') }}" class="block text-sm font-medium text-gray-700 py-2 {{ request()->routeIs('search') ? 'text-primary-600' : '' }}">Cari Jadwal</a>
                <a href="{{ route('listing') }}" class="block text-sm font-medium text-gray-700 py-2 {{ request()->routeIs('listing') ? 'text-primary-600' : '' }}">Agency</a>
                <a href="{{ route('download-app') }}" class="block text-sm font-medium text-gray-700 py-2">Download App</a>
                <a href="{{ route('eticket.public') }}" class="block text-sm font-medium text-gray-700 py-2">Cek E-Ticket</a>
                <hr class="border-gray-100">
                @auth
                    <a href="{{ route(\App\Enums\UserRole::from(auth()->user()->role)->defaultRedirectRoute()) }}"
                       class="block btn-primary text-center">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="block text-sm font-medium text-gray-700 py-2">Masuk</a>
                    <a href="{{ route('register') }}" class="block btn-primary text-center">Daftar</a>
                @endauth
            </div>
        </div>
    </header>

    {{-- MAIN CONTENT --}}
    <main class="min-h-screen">
        @yield('content')
    </main>

    {{-- BOTTOM NAVIGATION MOBILE --}}
    <nav class="bottom-nav md:hidden">
        <div class="flex items-center justify-around py-2">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-1 text-xs {{ request()->routeIs('home') ? 'text-primary-600' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Beranda</span>
            </a>
            <a href="{{ route('search') }}" class="flex flex-col items-center gap-1 text-xs {{ request()->routeIs('search') ? 'text-primary-600' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span>Cari</span>
            </a>
            <a href="{{ route('listing') }}" class="flex flex-col items-center gap-1 text-xs {{ request()->routeIs('listing') ? 'text-primary-600' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span>Agency</span>
            </a>
            @auth
            <a href="{{ route('customer.profile') }}" class="flex flex-col items-center gap-1 text-xs {{ request()->routeIs('customer.profile') ? 'text-primary-600' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Profil</span>
            </a>
            @else
            <a href="{{ route('login') }}" class="flex flex-col items-center gap-1 text-xs text-gray-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Masuk</span>
            </a>
            @endauth
        </div>
    </nav>

    {{-- FOOTER --}}
    <footer class="bg-gray-900 text-white pt-16 pb-24 md:pb-8">
        <div class="container-custom">
            <div class="grid md:grid-cols-4 gap-8 mb-8">
                <div>
                    <img src="{{ asset('images/bulat-putih.png') }}" alt="GoMad" class="h-14 mb-4">
                    <p class="text-gray-400 text-sm leading-relaxed">Mobilitas orèng Madhurâ. Platform booking travel antar kota di Madura. Door-to-door service.</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Layanan</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white transition">Ekonomi</a></li>
                        <li><a href="#" class="hover:text-white transition">Premium</a></li>
                        <li><a href="#" class="hover:text-white transition">Charter</a></li>
                        <li><a href="#" class="hover:text-white transition">Rental</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Tautan</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="{{ route('home') }}" class="hover:text-white transition">Beranda</a></li>
                        <li><a href="{{ route('search') }}" class="hover:text-white transition">Cari Jadwal</a></li>
                        <li><a href="{{ route('listing') }}" class="hover:text-white transition">Agency</a></li>
                        <li><a href="{{ route('eticket.public') }}" class="hover:text-white transition">Cek E-Ticket</a></li>
                        <li><a href="{{ route('download-app') }}" class="hover:text-white transition">Download App</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Kontak</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li class="flex items-center gap-2"><span>📞</span> 0812-3456-7890</li>
                        <li class="flex items-center gap-2"><span>✉️</span> support@gomad.id</li>
                        <li class="flex items-center gap-2"><span>📍</span> Sumenep, Madura</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 text-center text-sm text-gray-500">
                <p>&copy; {{ date('Y') }} GoMad. All rights reserved.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>