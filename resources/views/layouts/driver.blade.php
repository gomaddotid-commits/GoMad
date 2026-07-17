<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Jadwal') - GoMad Driver</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-[#F9FAFB] font-sans text-[#111827]">

    {{-- HEADER --}}
    <header class="bg-white border-b border-[#E5E7EB] sticky top-0 z-40 shadow-sm">
        <div class="container-magazine">
            <div class="flex items-center justify-between h-14 md:h-16">
                <div class="flex items-center gap-3">
                    <a href="{{ route('driver.schedule') }}" class="flex items-center gap-2">
                        <div class="flex items-center gap-1">
                            <span class="text-xl font-bold tracking-tighter text-[#111827]">GO</span>
                            <span class="text-[#BA1826] text-xl font-bold tracking-tighter">MAD</span>
                        </div>
                    </a>
                    <span class="text-[10px] font-mono uppercase tracking-wider bg-yellow-50 text-yellow-700 px-2 py-0.5 rounded-full border border-yellow-200">Driver</span>
                </div>
                <div class="flex items-center gap-6 text-sm font-medium text-gray-500">
                    <a href="{{ route('driver.schedule') }}" class="hover:text-[#BA1826] transition {{ request()->routeIs('driver.schedule') ? 'text-[#BA1826]' : '' }}">Jadwal</a>
                    <a href="{{ route('driver.profile') }}" class="hover:text-[#BA1826] transition {{ request()->routeIs('driver.profile') ? 'text-[#BA1826]' : '' }}">Profil</a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-[#BA1826] transition font-medium">Keluar</button>
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
            <a href="{{ route('driver.schedule') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('driver.schedule') ? 'text-[#BA1826]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Jadwal</span>
            </a>
            <a href="{{ route('driver.profile') }}" class="flex flex-col items-center gap-1 text-[10px] {{ request()->routeIs('driver.profile') ? 'text-[#BA1826]' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Profil</span>
            </a>
        </div>
    </nav>

    @stack('scripts')
</body>
</html>