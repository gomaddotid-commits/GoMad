@extends('layouts.public')

@section('title', 'Cari Jadwal')
@section('meta_description', 'Cari jadwal travel antar kota dengan mudah. Temukan jadwal yang sesuai dengan rute dan tanggal keberangkatan Anda.')
@section('og_image', asset('images/og-search.jpg'))

@section('content')
@php
    $allCities = \App\Models\City::with('province')->orderBy('name')->get();
    $agencies = \App\Models\Agency::where('is_verified', true)->orderBy('agency_name')->get();
    $citiesData = $allCities->map(fn($c) => [
        'code' => $c->code,
        'name' => $c->name,
        'province_name' => $c->province->name ?? '',
    ])->values()->toArray();
    
    $query = \App\Models\Schedule::with(['route.originCity', 'route.destinationCity', 'agency', 'vehicle'])
        ->where('is_active', true)
        ->where('departure_date', '>=', now()->toDateString());

    if (request('origin')) {
        $query->whereHas('route.stops', fn($q) => $q->whereHas('city', fn($sq) => $sq->where('name', 'like', '%' . request('origin') . '%')));
    }
    if (request('destination')) {
        $query->whereHas('route.stops', fn($q) => $q->whereHas('city', fn($sq) => $sq->where('name', 'like', '%' . request('destination') . '%')));
    }
    if (request('origin_city_code')) {
        $query->whereHas('route.stops', fn($q) => $q->where('city_code', request('origin_city_code')));
    }
    if (request('destination_city_code')) {
        $query->whereHas('route.stops', fn($q) => $q->where('city_code', request('destination_city_code')));
    }
    if (request('date')) {
        $query->whereDate('departure_date', request('date'));
    }
    if (request('travel_class')) {
        $query->where('travel_class', request('travel_class'));
    }
    if (request('agency_id')) {
        $query->where('agency_id', request('agency_id'));
    }
    if (request('price_min')) {
        $query->where('price_per_seat', '>=', request('price_min'));
    }
    if (request('price_max')) {
        $query->where('price_per_seat', '<=', request('price_max'));
    }

    if (request('sort') == 'price_low') {
        $query->orderBy('price_per_seat', 'asc');
    } elseif (request('sort') == 'price_high') {
        $query->orderBy('price_per_seat', 'desc');
    } elseif (request('sort') == 'date') {
        $query->orderBy('departure_date')->orderBy('departure_time');
    } else {
        $query->orderBy('departure_date')->orderBy('departure_time');
    }

    $viewMode = request('view', 'grid');
    $schedules = $query->paginate(12);
    
    $hasFilter = request()->anyFilled(['origin', 'destination', 'origin_city_code', 'destination_city_code', 'date', 'travel_class', 'agency_id', 'price_min', 'price_max']);
    $activeFilterOrigin = request('origin_city_code') ? \App\Models\City::find(request('origin_city_code'))?->name : request('origin');
    $activeFilterDest = request('destination_city_code') ? \App\Models\City::find(request('destination_city_code'))?->name : request('destination');
@endphp

<div class="section" x-data="{ filterOpen: false, searchMode: '{{ request('origin_city_code') || request('destination_city_code') ? 'city' : 'text' }}' }">
    <div class="container-magazine">
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-[#111827] mb-2">Cari Jadwal Travel</h1>
            <p class="text-gray-500 font-light">Temukan jadwal yang sesuai dengan kebutuhan Anda.</p>
        </div>

        <div class="grid lg:grid-cols-4 gap-8">
            {{-- SIDEBAR FILTER --}}
            <div class="lg:col-span-1" :class="filterOpen ? 'block' : 'hidden lg:block'">
                <div class="card-gomad p-5 sticky top-24 border-[#E5E7EB]">
                    <div class="flex items-center justify-between mb-4 border-b border-[#E5E7EB] pb-3">
                        <h3 class="font-bold text-[#111827] font-mono uppercase tracking-wider text-sm">Filter</h3>
                        <button @click="filterOpen = false" class="lg:hidden text-gray-400 hover:text-[#111827]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    
                    <form action="{{ route('search') }}" method="GET" class="space-y-4" x-data="searchableSelect()">
                        {{-- Mode Pencarian --}}
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-2">Mode Pencarian</label>
                            <div class="flex bg-[#F9FAFB] rounded-lg p-1">
                                <button type="button" @click="searchMode = 'text'" 
                                        :class="searchMode === 'text' ? 'bg-white shadow text-[#BA1826]' : 'text-gray-500'"
                                        class="flex-1 py-2 text-xs font-semibold rounded-md transition">
                                    📝 Nama Kota
                                </button>
                                <button type="button" @click="searchMode = 'city'" 
                                        :class="searchMode === 'city' ? 'bg-white shadow text-[#BA1826]' : 'text-gray-500'"
                                        class="flex-1 py-2 text-xs font-semibold rounded-md transition">
                                    🏙️ Pilih Kota
                                </button>
                            </div>
                        </div>

                        {{-- Mode Text --}}
                        <div x-show="searchMode === 'text'">
                            <div>
                                <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kota Asal</label>
                                <input type="text" name="origin" value="{{ request('origin') }}" 
                                       class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition text-sm"
                                       placeholder="Ketik nama kota...">
                            </div>
                            <div class="mt-4">
                                <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kota Tujuan</label>
                                <input type="text" name="destination" value="{{ request('destination') }}"
                                       class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition text-sm"
                                       placeholder="Ketik nama kota...">
                            </div>
                        </div>

                        {{-- Mode City (Searchable) --}}
                        <div x-show="searchMode === 'city'" x-cloak>
                            <div>
                                <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kota Asal</label>
                                <div class="relative">
                                    <div class="relative">
                                        <input type="text" x-model="originSearch" @click="originOpen = !originOpen" @input="originOpen = true"
                                               placeholder="Ketik atau pilih kota..."
                                               class="w-full px-0 py-2 pr-8 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition text-sm cursor-pointer">
                                        <svg @click.stop="originOpen = !originOpen" class="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 cursor-pointer hover:text-[#111827] transition" :class="{'rotate-180': originOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                    <div x-show="originOpen" @click.away="originOpen = false" x-cloak
                                         class="absolute z-50 w-full mt-1 bg-white border border-[#E5E7EB] rounded-[12px] shadow-lg max-h-60 overflow-y-auto">
                                        <template x-for="city in filteredCities(originSearch)" :key="city.code">
                                            <div @click="selectOrigin(city); originOpen = false;"
                                                 class="px-4 py-2.5 text-sm hover:bg-[#BA1826]/5 cursor-pointer transition border-b border-[#F5F5F5] last:border-0"
                                                 :class="{'bg-[#BA1826]/5 font-semibold text-[#BA1826]': city.code === selectedOrigin}">
                                                <span x-text="city.name + ' (' + city.province_name + ')'"></span>
                                            </div>
                                        </template>
                                        <div x-show="filteredCities(originSearch).length === 0" class="px-4 py-2.5 text-sm text-gray-400 text-center">Tidak ditemukan</div>
                                    </div>
                                    <input type="hidden" name="origin_city_code" :value="selectedOrigin">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kota Tujuan</label>
                                <div class="relative">
                                    <div class="relative">
                                        <input type="text" x-model="destinationSearch" @click="destinationOpen = !destinationOpen" @input="destinationOpen = true"
                                               placeholder="Ketik atau pilih kota..."
                                               class="w-full px-0 py-2 pr-8 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition text-sm cursor-pointer">
                                        <svg @click.stop="destinationOpen = !destinationOpen" class="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 cursor-pointer hover:text-[#111827] transition" :class="{'rotate-180': destinationOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                    <div x-show="destinationOpen" @click.away="destinationOpen = false" x-cloak
                                         class="absolute z-50 w-full mt-1 bg-white border border-[#E5E7EB] rounded-[12px] shadow-lg max-h-60 overflow-y-auto">
                                        <template x-for="city in filteredCities(destinationSearch)" :key="city.code">
                                            <div @click="selectDestination(city); destinationOpen = false;"
                                                 class="px-4 py-2.5 text-sm hover:bg-[#BA1826]/5 cursor-pointer transition border-b border-[#F5F5F5] last:border-0"
                                                 :class="{'bg-[#BA1826]/5 font-semibold text-[#BA1826]': city.code === selectedDestination}">
                                                <span x-text="city.name + ' (' + city.province_name + ')'"></span>
                                            </div>
                                        </template>
                                        <div x-show="filteredCities(destinationSearch).length === 0" class="px-4 py-2.5 text-sm text-gray-400 text-center">Tidak ditemukan</div>
                                    </div>
                                    <input type="hidden" name="destination_city_code" :value="selectedDestination">
                                </div>
                            </div>
                        </div>

                        {{-- Filter Lainnya --}}
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Tanggal</label>
                            <input type="date" name="date" value="{{ request('date') }}" 
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] text-sm cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kelas</label>
                            <select name="travel_class" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] text-sm transition">
                                <option value="">Semua Kelas</option>
                                <option value="economy" {{ request('travel_class') == 'economy' ? 'selected' : '' }}>Ekonomi</option>
                                <option value="premium" {{ request('travel_class') == 'premium' ? 'selected' : '' }}>Premium</option>
                                <option value="charter" {{ request('travel_class') == 'charter' ? 'selected' : '' }}>Charter</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Agency</label>
                            <select name="agency_id" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] text-sm transition">
                                <option value="">Semua Agency</option>
                                @foreach($agencies as $agency)
                                <option value="{{ $agency->id }}" {{ request('agency_id') == $agency->id ? 'selected' : '' }}>
                                    {{ $agency->agency_name }} ({{ $agency->city_name }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Harga/Seat (Rp)</label>
                            <div class="flex gap-2 items-center">
                                <input type="number" name="price_min" value="{{ request('price_min') }}" 
                                       class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] text-sm"
                                       placeholder="Min" min="0">
                                <span class="text-gray-400">-</span>
                                <input type="number" name="price_max" value="{{ request('price_max') }}" 
                                       class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] text-sm"
                                       placeholder="Max" min="0">
                            </div>
                        </div>

                        <button type="submit" class="w-full btn-gomad-primary text-center py-2.5 text-sm mt-2">Terapkan Filter</button>
                        @if($hasFilter)
                        <a href="{{ route('search') }}" class="block w-full text-center border border-[#E5E7EB] text-gray-600 py-2.5 rounded-[10px] text-sm hover:bg-[#F9FAFB] transition">Reset Filter</a>
                        @endif
                    </form>
                </div>
            </div>

            {{-- RESULTS --}}
            <div class="lg:col-span-3" x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 600)">
                
                {{-- Toolbar --}}
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6 border-b border-[#E5E7EB] pb-4">
                    <p class="text-sm text-gray-500 font-light">
                        Menampilkan <strong class="text-[#111827]">{{ $schedules->total() }}</strong> jadwal
                        @if($activeFilterOrigin || $activeFilterDest)
                        <span class="text-gray-400">
                            {{ $activeFilterOrigin ? 'dari ' . $activeFilterOrigin : '' }}
                            {{ $activeFilterOrigin && $activeFilterDest ? ' ke ' : '' }}
                            {{ $activeFilterDest ? $activeFilterDest : '' }}
                        </span>
                        @endif
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono uppercase tracking-wider text-gray-500">Urut:</span>
                            <select onchange="window.location.href=this.value" class="text-xs border border-[#E5E7EB] rounded-lg px-2 py-1 bg-white text-[#111827]">
                                <option value="{{ request()->fullUrlWithQuery(['sort' => 'default']) }}" {{ !request('sort') || request('sort') == 'default' ? 'selected' : '' }}>Default</option>
                                <option value="{{ request()->fullUrlWithQuery(['sort' => 'date']) }}" {{ request('sort') == 'date' ? 'selected' : '' }}>Tanggal</option>
                                <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_low']) }}" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Harga Terendah</option>
                                <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_high']) }}" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Harga Tertinggi</option>
                            </select>
                        </div>
                        <div class="flex bg-[#F9FAFB] rounded-lg p-1">
                            <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" class="px-3 py-1.5 rounded-md text-sm transition {{ $viewMode == 'grid' ? 'bg-white shadow text-[#BA1826] font-medium' : 'text-gray-500 hover:text-[#111827]' }}">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" class="px-3 py-1.5 rounded-md text-sm transition {{ $viewMode == 'list' ? 'bg-white shadow text-[#BA1826] font-medium' : 'text-gray-500 hover:text-[#111827]' }}">
                                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            </a>
                        </div>
                    </div>
                    <button @click="filterOpen = !filterOpen" class="lg:hidden flex items-center gap-2 px-4 py-2 border border-[#E5E7EB] rounded-[12px] text-sm font-medium hover:bg-[#F9FAFB] transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Filter
                        @if($hasFilter)<span class="w-2 h-2 bg-[#BA1826] rounded-full"></span>@endif
                    </button>
                </div>

                {{-- ✅ SKELETON LOADING --}}
                <div x-show="loading" x-cloak>
                    <x-skeleton-card type="schedule" :count="12" />
                </div>

                {{-- ✅ KONTEN ASLI --}}
                <div x-show="!loading" x-cloak>
                    @if($schedules->isEmpty())
                    <div class="card-gomad p-12 text-center border-[#E5E7EB]">
                        <div class="w-16 h-16 bg-[#F9FAFB] rounded-[10px] flex items-center justify-center mx-auto mb-4 border border-[#E5E7EB]">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <p class="text-gray-500 text-lg font-light">Tidak ada jadwal ditemukan.</p>
                        <a href="{{ route('search') }}" class="inline-block mt-4 text-[#BA1826] hover:underline font-medium">Reset Filter</a>
                    </div>
                    @else
                        @if($viewMode == 'grid')
                        <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                            @foreach($schedules as $schedule)
                            <div class="card-gomad p-5 group border-[#E5E7EB] hover:border-[#BA1826]">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-full bg-[#F9FAFB] flex items-center justify-center overflow-hidden flex-shrink-0 group-hover:bg-[#BA1826]/10 transition-colors">
                                        @if($schedule->agency->logo)
                                        <img src="{{ $schedule->agency->logo }}" alt="{{ $schedule->agency->agency_name }}" class="w-full h-full object-cover">
                                        @else
                                        <span class="text-lg">🏢</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-sm text-[#111827] truncate">{{ $schedule->agency->agency_name }}</p>
                                        <div class="flex items-center text-xs font-mono tracking-wider">
                                            <span class="text-gray-500">⭐ {{ number_format($schedule->agency->rating, 1) }}</span>
                                            @if($schedule->agency->is_verified)
                                            <span class="text-[#BA1826] ml-2">✓ Terverifikasi</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <p class="text-sm font-medium text-[#111827] mb-1">{{ $schedule->route->route_name }}</p>
                                <p class="text-xs text-gray-500 mb-3 font-mono tracking-wider">{{ $schedule->route->origin_city_name }} → {{ $schedule->route->destination_city_name }}</p>
                                <div class="bg-[#F9FAFB] rounded-[10px] p-3 mb-3 border border-[#E5E7EB]">
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium text-[#111827]">{{ $schedule->departure_date->format('d M Y') }}</span>
                                        <span class="font-mono">{{ $schedule->departure_time }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500 mt-1 font-mono uppercase tracking-wider">
                                        <span>{{ $schedule->vehicle->plate_number ?? '-' }}</span>
                                        <span class="text-[#BA1826]">{{ $schedule->travel_class }}</span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center border-t border-[#E5E7EB] pt-3">
                                    <div>
                                        <p class="font-bold text-[#BA1826] font-mono">Rp {{ number_format($schedule->price_per_seat, 0, ',', '.') }}</p>
                                        <p class="text-xs {{ $schedule->available_seats > 0 ? 'text-green-600' : 'text-[#BA1826]' }} font-mono uppercase tracking-wider">
                                            {{ $schedule->available_seats > 0 ? $schedule->available_seats . ' kursi' : 'Penuh' }}
                                        </p>
                                    </div>
                                    @auth
                                        @if($schedule->available_seats > 0)
                                        <a href="{{ route('customer.booking.create', $schedule) }}" class="btn-gomad-primary text-sm py-2 px-4 rounded-[10px]">Booking</a>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="btn-gomad-outline text-sm py-2 px-4 rounded-[10px] border-[#BA1826] text-[#BA1826] hover:bg-[#BA1826] hover:text-white">Login</a>
                                    @endauth
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="space-y-3">
                            @foreach($schedules as $schedule)
                            <div class="card-gomad p-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 group border-[#E5E7EB] hover:border-[#BA1826]">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-[#F9FAFB] flex items-center justify-center overflow-hidden flex-shrink-0">
                                        @if($schedule->agency->logo)
                                        <img src="{{ $schedule->agency->logo }}" alt="" class="w-full h-full object-cover">
                                        @else
                                        <span class="text-xl">🏢</span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <p class="font-bold text-[#111827]">{{ $schedule->agency->agency_name }}</p>
                                            <span class="text-gray-500 text-xs font-mono">⭐ {{ number_format($schedule->agency->rating, 1) }}</span>
                                        </div>
                                        <p class="text-sm text-gray-600">{{ $schedule->route->route_name }}</p>
                                        <p class="text-xs text-gray-500 font-mono tracking-wider">
                                            {{ $schedule->departure_date->format('d M Y') }} {{ $schedule->departure_time }} | 
                                            {{ $schedule->vehicle->plate_number ?? '-' }} | 
                                            <span class="text-[#BA1826] uppercase">{{ $schedule->travel_class }}</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 flex-shrink-0">
                                    <div class="text-right">
                                        <p class="font-bold text-[#BA1826] font-mono text-lg">Rp {{ number_format($schedule->price_per_seat, 0, ',', '.') }}</p>
                                        <p class="text-xs {{ $schedule->available_seats > 0 ? 'text-green-600' : 'text-[#BA1826]' }} font-mono uppercase tracking-wider">
                                            {{ $schedule->available_seats > 0 ? $schedule->available_seats . ' kursi' : 'Penuh' }}
                                        </p>
                                    </div>
                                    @auth
                                        @if($schedule->available_seats > 0)
                                        <a href="{{ route('customer.booking.create', $schedule) }}" class="btn-gomad-primary text-sm py-2 px-5 rounded-[10px] whitespace-nowrap">Booking</a>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}" class="btn-gomad-outline text-sm py-2 px-5 rounded-[10px] whitespace-nowrap border-[#BA1826] text-[#BA1826] hover:bg-[#BA1826] hover:text-white">Login</a>
                                    @endauth
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                        <div class="mt-8">
                            {{ $schedules->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function searchableSelect() {
    return {
        originSearch: '{{ request('origin_city_code') ? \App\Models\City::find(request('origin_city_code'))?->name : '' }}{{ request('origin_city_code') && \App\Models\City::find(request('origin_city_code'))?->province?->name ? ' (' . \App\Models\City::find(request('origin_city_code'))?->province?->name . ')' : '' }}',
        originOpen: false,
        selectedOrigin: '{{ request('origin_city_code') }}',
        destinationSearch: '{{ request('destination_city_code') ? \App\Models\City::find(request('destination_city_code'))?->name : '' }}{{ request('destination_city_code') && \App\Models\City::find(request('destination_city_code'))?->province?->name ? ' (' . \App\Models\City::find(request('destination_city_code'))?->province?->name . ')' : '' }}',
        destinationOpen: false,
        selectedDestination: '{{ request('destination_city_code') }}',
        
        allCities: @json($citiesData),
        
        filteredCities(search) {
            var q = (search || '').toLowerCase();
            if (!q) return this.allCities;
            return this.allCities.filter(function(c) {
                return c.name.toLowerCase().includes(q) || c.province_name.toLowerCase().includes(q);
            });
        },
        
        selectOrigin(city) {
            this.selectedOrigin = city.code;
            this.originSearch = city.name + ' (' + city.province_name + ')';
        },
        
        selectDestination(city) {
            this.selectedDestination = city.code;
            this.destinationSearch = city.name + ' (' + city.province_name + ')';
        }
    }
}
</script>
@endpush
@endsection