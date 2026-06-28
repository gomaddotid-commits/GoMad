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
<body class="bg-gray-50 font-sans text-secondary">

    {{-- HEADER --}}
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40">
        <div class="max-w-5xl mx-auto px-4">
            <div class="flex items-center justify-between h-14">
                <div class="flex items-center gap-3">
                    <a href="{{ route('driver.schedule') }}" class="flex items-center gap-2">
                        <img src="{{ asset('images/logo.svg') }}" alt="GoMad" class="h-6">
                    </a>
                    <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-medium">Driver</span>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <a href="{{ route('driver.schedule') }}" class="text-gray-600 hover:text-primary-600 transition {{ request()->routeIs('driver.schedule') ? 'text-primary-600 font-semibold' : '' }}">Jadwal</a>
                    <a href="{{ route('driver.profile') }}" class="text-gray-600 hover:text-primary-600 transition {{ request()->routeIs('driver.profile') ? 'text-primary-600 font-semibold' : '' }}">Profil</a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-500 hover:text-red-500 transition text-sm">Keluar</button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    {{-- CONTENT --}}
    <main class="max-w-5xl mx-auto px-4 py-6">
        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 text-sm">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>

    {{-- BOTTOM NAV MOBILE --}}
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 md:hidden z-50">
        <div class="flex items-center justify-around py-2">
            <a href="{{ route('driver.schedule') }}" class="flex flex-col items-center gap-1 text-xs {{ request()->routeIs('driver.schedule') ? 'text-primary-600' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Jadwal</span>
            </a>
            <a href="{{ route('driver.profile') }}" class="flex flex-col items-center gap-1 text-xs {{ request()->routeIs('driver.profile') ? 'text-primary-600' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span>Profil</span>
            </a>
        </div>
    </nav>

    @stack('scripts')
</body>
</html>