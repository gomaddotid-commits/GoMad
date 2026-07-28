<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - GoMad Driver</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-[#F9FAFB] font-sans text-[#111827]" x-data="{ mobileMenu: false }">

    {{-- HEADER --}}
    <header class="bg-white border-b border-[#E5E7EB] sticky top-0 z-40 shadow-sm">
        <div class="container-magazine">
            <div class="flex items-center justify-between h-14 md:h-16">
                {{-- Logo --}}
                <div class="flex items-center gap-3">
                    <a href="{{ route('driver.dashboard') }}" class="flex items-center gap-2">
                        <div class="flex items-center gap-1">
                            <span class="text-xl font-bold tracking-tighter text-[#111827]">GO</span>
                            <span class="text-[#BA1826] text-xl font-bold tracking-tighter">MAD</span>
                        </div>
                    </a>
                    <span class="hidden md:inline text-[10px] font-mono uppercase tracking-wider border border-[#BA1826] text-[#BA1826] px-2 py-0.5 rounded-full">Driver</span>
                </div>

                {{-- Desktop Navigation --}}
                <div class="hidden md:flex items-center gap-6 text-sm font-medium text-gray-500">
                    <a href="{{ route('driver.dashboard') }}" 
                       class="hover:text-[#BA1826] transition {{ request()->routeIs('driver.dashboard') ? 'text-[#BA1826]' : '' }}">
                        📊 Dashboard
                    </a>
                    <a href="{{ route('driver.assignments') }}" 
                       class="hover:text-[#BA1826] transition {{ request()->routeIs('driver.assignments') ? 'text-[#BA1826]' : '' }}">
                        📋 Penugasan
                    </a>
                    <a href="{{ route('driver.profile') }}" 
                       class="hover:text-[#BA1826] transition {{ request()->routeIs('driver.profile') ? 'text-[#BA1826]' : '' }}">
                        👤 Profil
                    </a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-[#BA1826] transition font-medium">Keluar</button>
                    </form>
                </div>

                {{-- Mobile Toggle --}}
                <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 text-[#111827]">
                    <svg x-show="!mobileMenu" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenu" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- MOBILE DRAWER --}}
        <div x-show="mobileMenu" x-cloak 
             class="fixed inset-0 z-50 md:hidden" 
             @click="mobileMenu = false">
            <div class="absolute inset-0 bg-[#111827]/50"></div>
            <div class="absolute right-0 top-0 h-full w-3/4 max-w-sm bg-white shadow-2xl flex flex-col" @click.stop="">
                {{-- Drawer Header --}}
                <div class="p-5 border-b border-[#E5E7EB] flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xl font-bold tracking-tighter text-[#111827]">GO</span>
                        <span class="text-[#BA1826] text-xl font-bold tracking-tighter">MAD</span>
                    </div>
                    <button @click="mobileMenu = false" class="p-2 text-gray-400 hover:text-[#111827]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- User Info --}}
                <div class="p-5 border-b border-[#E5E7EB]">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[#BA1826] flex items-center justify-center text-white font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-semibold text-[#111827] text-sm">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Driver</p>
                        </div>
                    </div>
                </div>

                {{-- Menu --}}
                <div class="flex-1 overflow-y-auto py-3">
                    <div class="px-5 py-2">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mb-2">Menu</p>
                        <a href="{{ route('driver.dashboard') }}" 
                           class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm {{ request()->routeIs('driver.dashboard') ? 'bg-[#BA1826]/10 text-[#BA1826] font-semibold' : 'text-gray-600 hover:bg-[#F9FAFB]' }}" 
                           @click="mobileMenu = false">
                            <span>📊</span> Dashboard
                        </a>
                        <a href="{{ route('driver.assignments') }}" 
                           class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm {{ request()->routeIs('driver.assignments') ? 'bg-[#BA1826]/10 text-[#BA1826] font-semibold' : 'text-gray-600 hover:bg-[#F9FAFB]' }}" 
                           @click="mobileMenu = false">
                            <span>📋</span> Penugasan
                        </a>
                        <a href="{{ route('driver.profile') }}" 
                           class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm {{ request()->routeIs('driver.profile') ? 'bg-[#BA1826]/10 text-[#BA1826] font-semibold' : 'text-gray-600 hover:bg-[#F9FAFB]' }}" 
                           @click="mobileMenu = false">
                            <span>👤</span> Profil
                        </a>
                    </div>
                </div>

                {{-- Logout --}}
                <div class="p-5 border-t border-[#E5E7EB]">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full text-left text-sm text-gray-400 hover:text-[#BA1826] transition py-2 font-medium">
                            🚪 Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    {{-- CONTENT --}}
    <main class="container-magazine py-8 min-h-screen pb-24 md:pb-8">
        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-[10px] mb-4 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[10px] mb-4 text-sm">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>

    {{-- BOTTOM NAV MOBILE --}}
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-[#E5E7EB] md:hidden z-50">
        <div class="flex items-center justify-around py-2">
            <a href="{{ route('driver.dashboard') }}" 
               class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('driver.dashboard') ? 'text-[#BA1826]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3"/>
                </svg>
                <span>Dashboard</span>
            </a>
            <a href="{{ route('driver.assignments') }}" 
               class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('driver.assignments') ? 'text-[#BA1826]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Penugasan</span>
            </a>
            <a href="{{ route('driver.profile') }}" 
               class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('driver.profile') ? 'text-[#BA1826]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span>Profil</span>
            </a>
        </div>
    </nav>

    @stack('scripts')
</body>
</html>