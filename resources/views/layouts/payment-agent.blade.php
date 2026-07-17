<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - GoMad Warung</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-[#F9FAFB] font-sans text-[#111827]" x-data="{ sidebarOpen: false }">
    
    <div class="flex h-screen overflow-hidden">
        {{-- MOBILE OVERLAY --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 bg-[#111827]/50 z-40 lg:hidden"></div>

        {{-- SIDEBAR --}}
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-[#E5E7EB] transform transition-transform duration-300 lg:relative lg:translate-x-0 overflow-y-auto"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            @php $agent = auth()->user()->paymentAgent ?? null; $hasAgent = $agent && $agent->agent_name; @endphp
            
            <div class="p-5 border-b border-[#E5E7EB]">
                <a href="{{ route('payment-agent.dashboard') }}" class="flex items-center gap-2">
                    <div class="flex items-center gap-1">
                        <span class="text-xl font-bold tracking-tighter text-[#111827]">GO</span>
                        <span class="text-[#BA1826] text-xl font-bold tracking-tighter">MAD</span>
                    </div>
                </a>
                @if($hasAgent)
                <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mt-1 truncate">{{ $agent->agent_name }}</p>
                @endif
            </div>
            
            <nav class="p-3 space-y-1">
                @if(!$hasAgent)
                <a href="{{ route('payment-agent.setup') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm transition {{ request()->routeIs('payment-agent.setup') ? 'bg-[#BA1826]/10 text-[#BA1826] font-semibold' : 'text-gray-600 hover:bg-[#F9FAFB]' }}">
                    <span>📝</span> Setup Warung
                </a>
                @else
                <a href="{{ route('payment-agent.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm transition {{ request()->routeIs('payment-agent.dashboard') ? 'bg-[#BA1826]/10 text-[#BA1826] font-semibold' : 'text-gray-600 hover:bg-[#F9FAFB]' }}">
                    <span>📊</span> Dashboard
                </a>
                
                @if($agent->is_verified)
                <a href="{{ route('payment-agent.payments') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm transition {{ request()->routeIs('payment-agent.payments') ? 'bg-[#BA1826]/10 text-[#BA1826] font-semibold' : 'text-gray-600 hover:bg-[#F9FAFB]' }}">
                    <span>💰</span> Pembayaran
                </a>
                <a href="{{ route('payment-agent.settlements') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm transition {{ request()->routeIs('payment-agent.settlements') ? 'bg-[#BA1826]/10 text-[#BA1826] font-semibold' : 'text-gray-600 hover:bg-[#F9FAFB]' }}">
                    <span>📋</span> Settlement
                </a>
                @endif
                
                <a href="{{ route('payment-agent.profile') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-[10px] text-sm transition {{ request()->routeIs('payment-agent.profile') ? 'bg-[#BA1826]/10 text-[#BA1826] font-semibold' : 'text-gray-600 hover:bg-[#F9FAFB]' }}">
                    <span>⚙️</span> Profil
                </a>
                @endif
            </nav>
            
            <div class="p-4 border-t border-[#E5E7EB] mt-auto">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-full bg-[#BA1826] flex items-center justify-center text-white text-sm font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-[#111827]">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Warung</p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-left text-sm text-gray-400 hover:text-[#BA1826] transition py-2 font-medium">🚪 Logout</button>
                </form>
            </div>
        </aside>

        {{-- MAIN CONTENT --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white border-b border-[#E5E7EB] px-4 lg:px-6 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 text-gray-600 hover:bg-[#F9FAFB] rounded-[10px]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1 class="text-lg font-bold text-[#111827]">@yield('title', 'Dashboard')</h1>
                </div>
                <div class="flex items-center gap-3">
                    @if($hasAgent && !$agent->is_verified)
                    <span class="text-[10px] font-mono uppercase tracking-wider bg-yellow-50 text-yellow-700 px-2 py-1 rounded-full border border-yellow-200">Pending</span>
                    @elseif($hasAgent && $agent->is_verified)
                    <span class="text-[10px] font-mono uppercase tracking-wider bg-green-50 text-green-700 px-2 py-1 rounded-full border border-green-200">Verified</span>
                    @endif
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-[10px] mb-4 text-sm">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[10px] mb-4 text-sm">{{ session('error') }}</div>
                @endif
                @if(session('warning'))
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-[10px] mb-4 text-sm">{{ session('warning') }}</div>
                @endif
                
                @if($hasAgent && !$agent->is_verified && !request()->routeIs('payment-agent.setup'))
                <div class="bg-yellow-50 border border-yellow-200 rounded-[10px] p-4 mb-6 flex items-center justify-between">
                    <div>
                        <span class="font-semibold text-yellow-800 font-mono uppercase tracking-wider text-xs">Warung belum diverifikasi.</span>
                        <span class="text-sm text-yellow-700 ml-2 font-light">Hubungi admin untuk verifikasi.</span>
                    </div>
                    <a href="{{ route('payment-agent.profile') }}" class="bg-[#BA1826] text-white px-4 py-2 rounded-[10px] text-sm font-medium hover:bg-[#8A0F18] transition whitespace-nowrap ml-4">Lihat Profil</a>
                </div>
                @endif
                
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>