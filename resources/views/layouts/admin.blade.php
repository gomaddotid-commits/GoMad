<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="api-token" content="{{ session('api_token') }}">
    <title>@yield('title', 'Dashboard') - GoMad Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-[#F5F5F5] font-sans text-[#111111]" x-data="{ sidebarOpen: false }">
    
    <div class="flex h-screen overflow-hidden">
        {{-- SIDEBAR OVERLAY MOBILE --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 bg-[#111111]/50 z-40 lg:hidden"></div>

        {{-- SIDEBAR --}}
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-[#111111] text-white transform transition-transform duration-300 lg:relative lg:translate-x-0 overflow-y-auto"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            
            {{-- Logo --}}
            <div class="p-5 border-b border-gray-800">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <div class="flex items-center gap-1">
                        <span class="text-xl font-bold tracking-tighter text-white">GO</span>
                        <span class="text-[#C1121F] text-xl font-bold tracking-tighter">MAD</span>
                    </div>
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400 ml-2">Admin Panel</span>
                </a>
            </div>
            
            {{-- ═══════════════════════════════════════ --}}
            {{-- FLIP MODE SWITCH --}}
            {{-- ═══════════════════════════════════════ --}}
            @php
                $isRentalMode = request()->is('admin/rental*');
            @endphp
            <div class="px-3 py-3">
                <div class="flex bg-gray-800 rounded-lg p-1">
                    <a href="{{ $isRentalMode ? route('admin.dashboard') : '#' }}" 
                       class="flex-1 text-center py-2 rounded-md text-xs font-semibold transition {{ !$isRentalMode ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:text-white' }}">
                        🚐 Travel
                    </a>
                    <a href="{{ $isRentalMode ? '#' : route('admin.rental.dashboard') }}" 
                       class="flex-1 text-center py-2 rounded-md text-xs font-semibold transition {{ $isRentalMode ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:text-white' }}">
                        🚗 Rental
                    </a>
                </div>
            </div>
            
            <nav class="p-3 space-y-1">
                @if(!$isRentalMode)
                    {{-- ═══════════════════════════════════════ --}}
                    {{-- MODE TRAVEL --}}
                    {{-- ═══════════════════════════════════════ --}}
                    <p class="px-3 py-2 text-[10px] font-mono uppercase tracking-wider text-gray-500">Menu Utama</p>
                    
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.dashboard') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>📊</span> Dashboard
                    </a>
                    <a href="{{ route('admin.agencies.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.agencies.*') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>🏢</span> Agency
                    </a>
                    <a href="{{ route('admin.customers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.customers.*') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>👥</span> Customer
                    </a>
                    <a href="{{ route('admin.drivers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.drivers.*') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>👨‍✈️</span> Driver
                    </a>
                    <a href="{{ route('admin.payment-agents.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.payment-agents.*') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>🏪</span> Warung
                    </a>
                    
                    <p class="px-3 py-2 text-[10px] font-mono uppercase tracking-wider text-gray-500 mt-4">Operasional</p>
                    <a href="{{ route('admin.routes.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.routes.*') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>🗺️</span> Rute
                    </a>
                    <a href="{{ route('admin.bookings.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.bookings.*') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>🎫</span> Booking
                    </a>
                    <a href="{{ route('admin.promos.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.promos.*') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>🎫</span> Promo
                    </a>
                    
                    <p class="px-3 py-2 text-[10px] font-mono uppercase tracking-wider text-gray-500 mt-4">Keuangan</p>
                    <a href="{{ route('admin.withdrawals.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.withdrawals.*') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>💸</span> Withdrawal
                    </a>
                    <a href="{{ route('admin.settlements.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.settlements.*') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>📋</span> Settlement
                    </a>
                    
                    <p class="px-3 py-2 text-[10px] font-mono uppercase tracking-wider text-gray-500 mt-4">Lainnya</p>
                    <a href="{{ route('admin.reports') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.reports') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>📈</span> Laporan
                    </a>
                    <a href="{{ route('admin.settings') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.settings') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>⚙️</span> Pengaturan
                    </a>
                    
                @else
                    {{-- ═══════════════════════════════════════ --}}
                    {{-- MODE RENTAL --}}
                    {{-- ═══════════════════════════════════════ --}}
                    <p class="px-3 py-2 text-[10px] font-mono uppercase tracking-wider text-gray-500">Menu Rental</p>
                    
                    <a href="{{ route('admin.rental.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.rental.dashboard') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>📊</span> Dashboard Rental
                    </a>
                    <a href="{{ route('admin.rental.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.rental.index') || request()->routeIs('admin.rental.show') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>🎫</span> Semua Rental
                    </a>
                    <a href="{{ route('admin.rental.documents') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.rental.documents*') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>📄</span> Verifikasi Dokumen
                    </a>
                    
                    <p class="px-3 py-2 text-[10px] font-mono uppercase tracking-wider text-gray-500 mt-4">Manajemen</p>
                    <a href="{{ route('admin.agencies.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.agencies.*') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>🏢</span> Agency
                    </a>
                    <a href="{{ route('admin.customers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.customers.*') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>👥</span> Customer
                    </a>
                    <a href="{{ route('admin.promos.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.promos.*') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>🎫</span> Promo
                    </a>
                    
                    <p class="px-3 py-2 text-[10px] font-mono uppercase tracking-wider text-gray-500 mt-4">Lainnya</p>
                    <a href="{{ route('admin.reports') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.reports') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>📈</span> Laporan
                    </a>
                    <a href="{{ route('admin.settings') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('admin.settings') ? 'bg-[#C1121F] text-white' : 'text-gray-400 hover:bg-[#C1121F]/10 hover:text-white' }}">
                        <span>⚙️</span> Pengaturan
                    </a>
                @endif
            </nav>
            
            {{-- User Info --}}
            <div class="p-4 border-t border-gray-800 mt-auto">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-full bg-[#C1121F] flex items-center justify-center text-white text-sm font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">
                            Admin {{ $isRentalMode ? '(Rental Mode)' : '(Travel Mode)' }}
                        </p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-left text-sm text-gray-400 hover:text-[#C1121F] transition py-2 font-medium">🚪 Logout</button>
                </form>
            </div>
        </aside>

        {{-- MAIN CONTENT --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            {{-- TOPBAR --}}
            <header class="bg-white border-b border-[#E5E5E5] px-4 lg:px-6 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 text-gray-600 hover:bg-[#F5F5F5] rounded-[12px]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1 class="text-lg font-bold text-[#111111]">@yield('title', 'Dashboard')</h1>
                    
                    {{-- Mode Badge --}}
                    <span class="text-[10px] font-mono uppercase tracking-wider px-2 py-1 rounded-full border {{ $isRentalMode ? 'bg-orange-50 text-orange-700 border-orange-200' : 'bg-blue-50 text-blue-700 border-blue-200' }}">
                        {{ $isRentalMode ? 'Mode Rental' : 'Mode Travel' }}
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ $isRentalMode ? route('admin.dashboard') : route('admin.rental.dashboard') }}" 
                       class="text-sm text-gray-500 hover:text-[#C1121F] transition font-medium">
                        Switch ke {{ $isRentalMode ? 'Travel' : 'Rental' }}
                    </a>
                    <a href="{{ route('home') }}" target="_blank" class="text-sm text-gray-500 hover:text-[#C1121F] transition font-medium">Lihat Website</a>
                </div>
            </header>

            {{-- PAGE CONTENT --}}
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-[12px] mb-4 text-sm">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[12px] mb-4 text-sm">{{ session('error') }}</div>
                @endif
                @if(session('warning'))
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-[12px] mb-4 text-sm">{{ session('warning') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>