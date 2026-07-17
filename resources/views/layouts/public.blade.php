<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO Meta --}}
    <meta name="description" content="@yield('meta_description', 'GoMad - Solusi transportasi Anda. Booking travel antar kota dengan mudah, dijemput di rumah, dan diantar ke tujuan.')">
    <meta name="keywords" content="@yield('meta_keywords', 'GoMad, transportasi, booking travel, sumenep surabaya, travel antar kota, warung gomad')">
    <meta name="author" content="GoMad">
    <meta name="robots" content="index, follow">

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('og_title', \App\Models\PlatformSetting::getValue('app_name', 'GoMad'))">
    <meta property="og:description" content="@yield('og_description', \App\Models\PlatformSetting::getValue('app_tagline', 'Mobilitas orèng Madhurâ'))">
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.png'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="GoMad">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', 'GoMad - Solusi transportasi Anda')">
    <meta name="twitter:description" content="@yield('og_description', 'GoMad - Solusi transportasi Anda. Booking travel antar kota dengan mudah, dijemput di rumah, dan diantar ke tujuan.')">
    <meta name="twitter:image" content="@yield('og_image', asset('images/og-default.jpg'))">

    {{-- Canonical --}}
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">

    <title>@yield('title', \App\Models\PlatformSetting::getValue('app_name', 'GoMad')) -
        {{ \App\Models\PlatformSetting::getValue('app_tagline', 'Mobilitas orèng Madhurâ') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-[#F9FAFB] text-[#111827] font-sans antialiased">

    {{-- HEADER --}}
    <header class="fixed top-0 left-0 right-0 z-50 h-16 md:h-20" id="mainHeader" x-data="{ mobileMenu: false }">
        <div class="container-magazine h-full flex items-center justify-between">

            {{-- LOGO --}}
            <a href="{{ route('home') }}" class="flex items-center gap-3 z-50">
                <img src="{{ asset('images/logo-putih.png') }}" alt="GoMad" class="h-8 md:h-10 w-auto logo-white transition-opacity duration-300">
                <img src="{{ asset('images/logo-merah.png') }}" alt="GoMad" class="h-8 md:h-10 w-auto logo-colored hidden transition-opacity duration-300">
            </a>

            {{-- DESKTOP NAV --}}
            <nav class="hidden lg:flex items-center gap-8">
                @foreach([
                    ['route' => 'home', 'label' => 'Beranda'],
                    ['route' => 'search', 'label' => 'Cari Travel'],
                    ['route' => 'rental.public', 'label' => 'Sewa Kendaraan'],
                    ['route' => 'listing', 'label' => 'Agency'],
                    ['route' => 'eticket.public', 'label' => 'E-Ticket']
                ] as $link)
                    <a href="{{ route($link['route']) }}"
                       class="nav-link text-sm font-medium transition-colors duration-300 relative
                       {{ request()->routeIs($link['route']) ? 'active' : '' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- AUTH BUTTONS --}}
            <div class="hidden lg:flex items-center gap-3 z-50">
                @auth
                    <a href="{{ route(\App\Enums\UserRole::from(auth()->user()->role)->defaultRedirectRoute()) }}"
                       class="btn-gomad-outline text-sm py-2 px-5 transition-all">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium transition-colors nav-link">Masuk</a>
                    <a href="{{ route('register') }}" class="btn-gomad-primary bg-white text-[#E42535] hover:bg-[#111827] hover:text-white text-sm py-2 px-5 transition-all">Daftar</a>
                @endauth
            </div>

            {{-- MOBILE TOGGLE --}}
            <button @click="mobileMenu = !mobileMenu" class="lg:hidden mobile-toggle-btn outline-none transition-colors duration-300 relative z-50">
                <svg x-show="!mobileMenu" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="mobileMenu" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

        </div>

        {{-- MOBILE DRAWER --}}
        <div x-show="mobileMenu" x-cloak
             @click="mobileMenu = false"
             class="fixed inset-0 bg-[#111827]/50 z-40 lg:hidden"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
        </div>

        <div x-show="mobileMenu" x-cloak
             class="fixed right-0 top-0 h-screen w-3/4 max-w-sm bg-white shadow-2xl z-[60] lg:hidden flex flex-col p-8"
             x-transition:enter="transition transform ease-out duration-300"
             x-transition:enter-start="transform translate-x-full"
             x-transition:enter-end="transform translate-x-0"
             x-transition:leave="transition transform ease-in duration-200"
             x-transition:leave-start="transform translate-x-0"
             x-transition:leave-end="transform translate-x-full"
             @click.away="mobileMenu = false">

            <div class="mb-10">
                <img src="{{ asset('images/logo-merah.png') }}" alt="GoMad" class="h-10 w-auto">
            </div>

            <div class="flex flex-col gap-6 text-lg font-medium text-[#111827]">
                <a href="{{ route('home') }}" class="border-b border-[#E5E7EB] pb-3 hover:text-[#BA1826] transition">Beranda</a>
                <a href="{{ route('search') }}" class="border-b border-[#E5E7EB] pb-3 hover:text-[#BA1826] transition">Cari Travel</a>
                <a href="{{ route('listing') }}" class="border-b border-[#E5E7EB] pb-3 hover:text-[#BA1826] transition">Agency</a>
                <a href="{{ route('rental.public') }}" class="border-b border-[#E5E7EB] pb-3 hover:text-[#BA1826] transition">Sewa Kendaraan</a>
                <a href="{{ route('eticket.public') }}" class="border-b border-[#E5E7EB] pb-3 hover:text-[#BA1826] transition">Cek E-Ticket</a>
            </div>

            <div class="mt-auto pt-8 border-t border-[#E5E7EB] flex flex-col gap-3">
                @auth
                    <a href="{{ route(\App\Enums\UserRole::from(auth()->user()->role)->defaultRedirectRoute()) }}" class="btn-gomad-primary text-center w-full">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn-gomad-outline btn-drawer text-center w-full">Masuk</a>
                    <a href="{{ route('register') }}" class="btn-gomad-primary text-center w-full">Daftar</a>
                @endauth
            </div>
        </div>
    </header>

    {{-- MAIN --}}
    <main class="pt-16 md:pt-20 min-h-screen">
        @yield('content')
    </main>

    {{-- BOTTOM NAV --}}
    <nav class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-[#E5E7EB] z-40 lg:hidden">
        <div class="flex items-center justify-around py-2">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('home') ? 'text-[#BA1826]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3"/></svg>
                <span>Beranda</span>
            </a>
            <a href="{{ route('search') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('search') ? 'text-[#BA1826]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span>Cari Travel</span>
            </a>
            <a href="{{ route('listing') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('listing') ? 'text-[#BA1826]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3"/></svg>
                <span>Agency</span>
            </a>
            <a href="{{ route('rental.public') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('rental.public') ? 'text-[#BA1826]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                <span>Sewa Kendaraan</span>
            </a>
            @auth
            <a href="{{ route('customer.profile') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('customer.profile') ? 'text-[#BA1826]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14"/></svg>
                <span>Profil</span>
            </a>
            @endauth
        </div>
    </nav>

    {{-- FOOTER --}}
    <footer class="bg-[#111827] text-white py-16 md:py-24 relative overflow-hidden mt-12">
        <div class="container-magazine grid grid-cols-1 md:grid-cols-4 gap-12 relative z-10">
            <div class="md:col-span-1 flex flex-col gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-4xl font-bold tracking-tighter">Go</span>
                    <span class="text-[#BA1826] text-4xl font-bold tracking-tighter">Mad</span>
                </div>
                <p class="text-gray-400 text-sm leading-relaxed max-w-xs">
                    {{ \App\Models\PlatformSetting::getValue('app_tagline', 'Solusi transportasi Anda.') }}
                </p>
            </div>

            <div class="md:col-span-3 grid grid-cols-2 md:grid-cols-3 gap-8 text-sm">
                <div>
                    <h4 class="font-semibold text-white mb-4">Layanan</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('search') }}" class="hover:text-white transition">Travel Reguler</a></li>
                        <li><a href="{{ route('rental.public') }}" class="hover:text-white transition">Sewa Kendaraan</a></li>
                        <li>Charter</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-white mb-4">Tautan</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('home') }}" class="hover:text-white transition">Beranda</a></li>
                        <li><a href="{{ route('search') }}" class="hover:text-white transition">Cari Travel</a></li>
                        <li><a href="{{ route('eticket.public') }}" class="hover:text-white transition">Cek E-Ticket</a></li>
                        <li><a href="{{ route('download-app') }}" class="hover:text-white transition">Download App</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-white mb-4">Kontak</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li> {{ \App\Models\PlatformSetting::getValue('support_email', 'support@gomad.id') }}</li>
                        <li> {{ \App\Models\PlatformSetting::getValue('support_phone', '081234567890') }}</li>
                        <li>Sumenep, Madura</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="container-magazine mt-12 pt-8 border-t border-white/10 text-center text-gray-500 text-xs relative z-10">
            &copy; {{ date('Y') }} {{ \App\Models\PlatformSetting::getValue('app_name', 'GoMad') }}. All rights reserved.
        </div>
    </footer>

    @stack('scripts')
</body>
</html>