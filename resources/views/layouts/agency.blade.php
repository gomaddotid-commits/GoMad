<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - GoMad Agency</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-[#F5F5F5] font-sans text-[#111111]" x-data="{ sidebarOpen: false }">
    
    <div class="flex h-screen overflow-hidden">
        {{-- SIDEBAR OVERLAY MOBILE --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 bg-[#111111]/50 z-40 lg:hidden"></div>

        {{-- SIDEBAR --}}
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-[#E5E5E5] transform transition-transform duration-300 lg:relative lg:translate-x-0 overflow-y-auto"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            @php 
                $agency = auth()->user()->agency; 
                $hasAgency = $agency && $agency->agency_name;
                $isRentalMode = request()->is('agency/rental*');
            @endphp
            
            <div class="p-5 border-b border-[#E5E5E5]">
                <a href="{{ route('agency.dashboard') }}" class="flex items-center gap-2">
                    <div class="flex items-center gap-1">
                        <span class="text-xl font-bold tracking-tighter text-[#111111]">GO</span>
                        <span class="text-[#C1121F] text-xl font-bold tracking-tighter">MAD</span>
                    </div>
                </a>
                @if($hasAgency)
                <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mt-1 truncate">{{ $agency->agency_name }}</p>
                @endif
            </div>
            
            <nav class="p-3 space-y-1">
                @if(!$hasAgency)
                {{-- Setup Agency --}}
                <a href="{{ route('agency.setup') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.setup') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                    <span>📝</span> Setup Agency
                </a>
                @else
                
                {{-- ═══════════════════════════════════════ --}}
                {{-- FLIP MODE SWITCH --}}
                {{-- ═══════════════════════════════════════ --}}
                <div class="px-1 mb-3">
                    <div class="flex bg-[#F5F5F5] rounded-lg p-1">
                        <a href="{{ $isRentalMode ? route('agency.dashboard') : '#' }}" 
                           class="flex-1 text-center py-2 rounded-md text-xs font-semibold transition {{ !$isRentalMode ? 'bg-white shadow text-[#C1121F]' : 'text-gray-500 hover:text-[#111111]' }}">
                            🚐 Travel
                        </a>
                        <a href="{{ $isRentalMode ? '#' : route('agency.rental.dashboard') }}" 
                           class="flex-1 text-center py-2 rounded-md text-xs font-semibold transition {{ $isRentalMode ? 'bg-white shadow text-[#C1121F]' : 'text-gray-500 hover:text-[#111111]' }}">
                            🚗 Rental
                        </a>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════ --}}
                {{-- AKSES DASAR (Selalu Ada) --}}
                {{-- ═══════════════════════════════════════ --}}
                <div class="px-1 py-1 text-[10px] font-mono uppercase tracking-wider text-gray-400">Akses Dasar</div>
                
                <a href="{{ route('agency.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.dashboard') && !$isRentalMode ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                    <span>📊</span> Dashboard
                </a>
                
                @if($agency->is_verified)
                
                <a href="{{ route('agency.drivers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.drivers.*') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                    <span>👨‍✈️</span> Driver
                </a>
                
                {{-- Kendaraan (akses dasar - selalu tampil) --}}
                <a href="{{ $isRentalMode ? route('agency.rental.vehicles') : route('agency.vehicles.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.vehicles.*') || request()->routeIs('agency.rental.vehicles*') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                    <span>🚐</span> Kendaraan
                </a>
                
                <a href="{{ route('agency.wallet.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.wallet.*') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                    <span>💰</span> Dompet
                </a>
                
                <a href="{{ route('agency.reports') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.reports') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                    <span>📈</span> Laporan
                </a>
                
                <a href="{{ route('agency.reviews') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.reviews') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                    <span>⭐</span> Review
                </a>

                {{-- ═══════════════════════════════════════ --}}
                {{-- AKSES LANJUTAN (Mode Travel / Rental) --}}
                {{-- ═══════════════════════════════════════ --}}
                
                @if(!$isRentalMode)
                    {{-- MODE TRAVEL --}}
                    <div class="px-1 py-1 mt-2 text-[10px] font-mono uppercase tracking-wider text-gray-400">Modul Travel</div>
                    
                    <a href="{{ route('agency.schedules.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.schedules.*') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                        <span>📅</span> Jadwal
                    </a>
                    <a href="{{ route('agency.bookings.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.bookings.*') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                        <span>🎫</span> Booking
                    </a>
                    <a href="{{ route('agency.transfers.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.transfers.*') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                        <span>🔄</span> Transfer
                    </a>
                    <a href="{{ route('agency.promos.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.promos.*') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                        <span>🎫</span> Promo
                    </a>
                @else
                    {{-- MODE RENTAL --}}
                    <div class="px-1 py-1 mt-2 text-[10px] font-mono uppercase tracking-wider text-gray-400">Modul Rental</div>
                    
                    <a href="{{ route('agency.rental.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.rental.dashboard') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                        <span>📊</span> Dashboard Rental
                    </a>
                    <a href="{{ route('agency.rental.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.rental.index') || request()->routeIs('agency.rental.show') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                        <span>🎫</span> Booking Rental
                    </a>
                    <a href="{{ route('agency.rental.vehicles') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.rental.vehicles*') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                        <span>🚗</span> Setup Kendaraan
                    </a>
                    <a href="{{ route('agency.rental.promos') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.rental.promos') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                        <span>🎫</span> Promo
                    </a>
                @endif
                
                @endif
                
                {{-- Profil (selalu di bawah) --}}
                <div class="px-1 py-1 mt-2 text-[10px] font-mono uppercase tracking-wider text-gray-400">Pengaturan</div>
                <a href="{{ route('agency.profile.edit') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[12px] text-sm transition {{ request()->routeIs('agency.profile.*') ? 'bg-[#C1121F]/10 text-[#C1121F] font-semibold' : 'text-gray-600 hover:bg-[#F5F5F5]' }}">
                    <span>⚙️</span> Profil
                </a>
                @endif
            </nav>
            
            <div class="p-4 border-t border-[#E5E5E5] mt-auto">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-full bg-[#C1121F] flex items-center justify-center text-white text-sm font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-[#111111]">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Agency</p>
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
                    @if($hasAgency && $agency->is_verified)
                    <span class="text-[10px] font-mono uppercase tracking-wider px-2 py-1 rounded-full border {{ $isRentalMode ? 'bg-orange-50 text-orange-700 border-orange-200' : 'bg-blue-50 text-blue-700 border-blue-200' }}">
                        {{ $isRentalMode ? 'Mode Rental' : 'Mode Travel' }}
                    </span>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    @if($hasAgency && !$agency->is_verified)
                    <span class="text-[10px] font-mono uppercase tracking-wider bg-yellow-50 text-yellow-700 px-2 py-1 rounded-full border border-yellow-200">Pending</span>
                    @elseif($hasAgency && $agency->is_verified)
                    <span class="text-[10px] font-mono uppercase tracking-wider bg-green-50 text-green-700 px-2 py-1 rounded-full border border-green-200">Verified</span>
                    @endif
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
                
                @if($hasAgency && !$agency->is_verified && !request()->routeIs('agency.profile.edit') && !request()->routeIs('agency.setup'))
                <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-4 mb-6 flex items-center justify-between">
                    <div>
                        <span class="font-semibold text-yellow-800 font-mono uppercase tracking-wider text-xs">Agency belum diverifikasi.</span>
                        <span class="text-sm text-yellow-700 ml-2 font-light">Lengkapi profil dan ajukan verifikasi.</span>
                    </div>
                    <a href="{{ route('agency.profile.edit') }}" class="bg-[#C1121F] text-white px-4 py-2 rounded-[12px] text-sm font-medium hover:bg-[#8A0F18] transition whitespace-nowrap ml-4">Lengkapi Profil</a>
                </div>
                @endif
                
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>