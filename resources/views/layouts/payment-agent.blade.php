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
<body class="bg-gray-50 font-sans text-secondary" x-data="{ sidebarOpen: false }">
    
    <div class="flex h-screen overflow-hidden">
        {{-- MOBILE OVERLAY --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 bg-black/50 z-40 lg:hidden"></div>

        {{-- SIDEBAR --}}
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform duration-300 lg:relative lg:translate-x-0 overflow-y-auto"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            @php $agent = auth()->user()->paymentAgent ?? null; $hasAgent = $agent && $agent->agent_name; @endphp
            
            <div class="p-5 border-b border-gray-100">
                <a href="{{ route('payment-agent.dashboard') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/gepeng-ireng.png') }}" alt="GoMad" class="h-7">
                </a>
                @if($hasAgent)
                <p class="text-xs text-gray-500 mt-1 truncate">{{ $agent->agent_name }}</p>
                @endif
            </div>
            
            <nav class="p-3 space-y-1">
                @if(!$hasAgent)
                <a href="{{ route('payment-agent.setup') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('payment-agent.setup') ? 'bg-primary-50 text-primary-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                    <span>📝</span> Setup Warung
                </a>
                @else
                <a href="{{ route('payment-agent.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('payment-agent.dashboard') ? 'bg-primary-50 text-primary-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                    <span>📊</span> Dashboard
                </a>
                
                @if($agent->is_verified)
                <a href="{{ route('payment-agent.payments') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('payment-agent.payments') ? 'bg-primary-50 text-primary-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                    <span>💰</span> Pembayaran
                </a>
                <a href="{{ route('payment-agent.settlements') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('payment-agent.settlements') ? 'bg-primary-50 text-primary-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                    <span>📋</span> Settlement
                </a>
                @endif
                
                <a href="{{ route('payment-agent.profile') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ request()->routeIs('payment-agent.profile') ? 'bg-primary-50 text-primary-600 font-semibold' : 'text-gray-600 hover:bg-gray-50' }}">
                    <span>⚙️</span> Profil
                </a>
                @endif
            </nav>
            
            <div class="p-4 border-t border-gray-100 mt-auto">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-full bg-green-600 flex items-center justify-center text-white text-sm font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-secondary">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500">Warung</p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full text-left text-sm text-gray-500 hover:text-red-500 transition py-2">🚪 Logout</button>
                </form>
            </div>
        </aside>

        {{-- MAIN CONTENT --}}
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white border-b border-gray-200 px-4 lg:px-6 py-3 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h1 class="text-lg font-bold text-secondary">@yield('title', 'Dashboard')</h1>
                </div>
                <div class="flex items-center gap-3">
                    @if($hasAgent && !$agent->is_verified)
                    <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full font-medium">Pending</span>
                    @elseif($hasAgent && $agent->is_verified)
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-medium">Verified</span>
                    @endif
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4 text-sm">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4 text-sm">{{ session('error') }}</div>
                @endif
                @if(session('warning'))
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-xl mb-4 text-sm">{{ session('warning') }}</div>
                @endif
                
                @if($hasAgent && !$agent->is_verified && !request()->routeIs('payment-agent.setup'))
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6 flex items-center justify-between">
                    <div>
                        <span class="font-semibold text-yellow-800">Warung belum diverifikasi.</span>
                        <span class="text-sm text-yellow-700 ml-2">Hubungi admin untuk verifikasi.</span>
                    </div>
                    <a href="{{ route('payment-agent.profile') }}" class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-yellow-600 transition whitespace-nowrap ml-4">Lihat Profil</a>
                </div>
                @endif
                
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>