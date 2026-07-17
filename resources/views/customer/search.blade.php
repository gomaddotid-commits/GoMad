@extends('layouts.customer')

@section('title', 'Cari Jadwal')
@section('content')
@php
    $allCities = \App\Models\City::with('province')->orderBy('name')->get();
    $agencies = \App\Models\Agency::where('is_verified', true)->orderBy('agency_name')->get();
    
    $query = \App\Models\Schedule::with(['route.originCity', 'route.destinationCity', 'agency', 'vehicle'])
        ->where('is_active', true)
        ->where('departure_date', '>=', now()->toDateString());

    // Filter by text (old way)
    if (request('origin')) {
        $query->whereHas('route.stops', fn($q) => $q->whereHas('city', fn($sq) => $sq->where('name', 'like', '%' . request('origin') . '%')));
    }
    if (request('destination')) {
        $query->whereHas('route.stops', fn($q) => $q->whereHas('city', fn($sq) => $sq->where('name', 'like', '%' . request('destination') . '%')));
    }
    
    // Filter by city_code (new way - Laravolt)
    if (request('origin_city_code')) {
        $query->whereHas('route.stops', fn($q) => $q->where('city_code', request('origin_city_code')));
    }
    if (request('destination_city_code')) {
        $query->whereHas('route.stops', fn($q) => $q->where('city_code', request('destination_city_code')));
    }
    
    // Filter by agency location
    if (request('agency_city_code')) {
        $query->whereHas('agency', fn($q) => $q->where('city_code', request('agency_city_code')));
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

    // Sorting
    if (request('sort') == 'price_low') {
        $query->orderBy('price_per_seat', 'asc');
    } elseif (request('sort') == 'price_high') {
        $query->orderBy('price_per_seat', 'desc');
    } else {
        $query->orderBy('departure_date')->orderBy('departure_time');
    }

    $viewMode = request('view', 'grid');
    $schedules = $query->paginate(12);
    
    $hasFilter = request()->anyFilled(['origin', 'destination', 'origin_city_code', 'destination_city_code', 'date', 'travel_class', 'agency_id', 'price_min', 'price_max']);
@endphp

<div class="container-magazine py-8" x-data="{ filterOpen: false, searchMode: '{{ request('origin_city_code') || request('destination_city_code') ? 'city' : 'text' }}' }">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-8">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-[#111111] mb-2">Cari Jadwal Travel</h1>
            <p class="text-gray-500 font-light">Temukan jadwal yang sesuai dengan kebutuhan Anda.</p>
        </div>
        
        <div class="flex items-center gap-3">
            @if($hasFilter)
            <a href="{{ route('customer.search') }}" class="text-xs text-[#C1121F] hover:underline font-medium whitespace-nowrap">Reset Filter</a>
            @endif
            <button @click="filterOpen = !filterOpen" 
                    class="lg:hidden flex items-center gap-2 px-4 py-2 border border-[#E5E5E5] rounded-[12px] text-sm font-medium hover:bg-[#F5F5F5] transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
                @if($hasFilter)
                <span class="w-2 h-2 bg-[#C1121F] rounded-full"></span>
                @endif
            </button>
        </div>
    </div>

    <div class="grid lg:grid-cols-4 gap-8">
        {{-- ═══════════════════════════════════ --}}
        {{-- SIDEBAR FILTER --}}
        {{-- ═══════════════════════════════════ --}}
        <div class="lg:col-span-1" :class="filterOpen ? 'block' : 'hidden'" class="lg:block">
            <div class="card-gomad p-5 sticky top-24 border-[#E5E5E5]">
                <div class="flex items-center justify-between mb-4 border-b border-[#E5E5E5] pb-3">
                    <h3 class="font-bold text-[#111111] font-mono uppercase tracking-wider text-sm">Filter</h3>
                    <button @click="filterOpen = false" class="lg:hidden text-gray-400 hover:text-[#111111]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form action="{{ route('customer.search') }}" method="GET" class="space-y-4">
                    
                    {{-- Mode Pencarian --}}
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-2">Mode Pencarian</label>
                        <div class="flex bg-[#F5F5F5] rounded-lg p-1">
                            <button type="button" @click="searchMode = 'text'" 
                                    :class="searchMode === 'text' ? 'bg-white shadow text-[#C1121F]' : 'text-gray-500'"
                                    class="flex-1 py-2 text-xs font-semibold rounded-md transition">
                                📝 Nama Kota
                            </button>
                            <button type="button" @click="searchMode = 'city'" 
                                    :class="searchMode === 'city' ? 'bg-white shadow text-[#C1121F]' : 'text-gray-500'"
                                    class="flex-1 py-2 text-xs font-semibold rounded-md transition">
                                🏙️ Pilih Kota
                            </button>
                        </div>
                    </div>

                    {{-- Mode Text (Cara Lama) --}}
                    <div x-show="searchMode === 'text'">
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kota Asal</label>
                            <input type="text" name="origin" value="{{ request('origin') }}" 
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition text-sm"
                                   placeholder="Ketik nama kota...">
                        </div>
                        <div class="mt-4">
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kota Tujuan</label>
                            <input type="text" name="destination" value="{{ request('destination') }}"
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition text-sm"
                                   placeholder="Ketik nama kota...">
                        </div>
                    </div>

                    {{-- Mode City (Laravolt Select) --}}
                    <div x-show="searchMode === 'city'" x-cloak>
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kota Asal</label>
                            <select name="origin_city_code" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] text-sm transition">
                                <option value="">Semua Kota</option>
                                @foreach($allCities as $city)
                                <option value="{{ $city->code }}" {{ request('origin_city_code') == $city->code ? 'selected' : '' }}>
                                    {{ $city->name }} ({{ $city->province->name }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-4">
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kota Tujuan</label>
                            <select name="destination_city_code" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] text-sm transition">
                                <option value="">Semua Kota</option>
                                @foreach($allCities as $city)
                                <option value="{{ $city->code }}" {{ request('destination_city_code') == $city->code ? 'selected' : '' }}>
                                    {{ $city->name }} ({{ $city->province->name }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Tanggal --}}
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Tanggal</label>
                        <input type="date" name="date" value="{{ request('date') }}" 
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] text-sm cursor-pointer">
                    </div>

                    {{-- Kelas --}}
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kelas</label>
                        <select name="travel_class" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] text-sm transition">
                            <option value="">Semua Kelas</option>
                            <option value="economy" {{ request('travel_class') == 'economy' ? 'selected' : '' }}>Ekonomi</option>
                            <option value="premium" {{ request('travel_class') == 'premium' ? 'selected' : '' }}>Premium</option>
                            <option value="charter" {{ request('travel_class') == 'charter' ? 'selected' : '' }}>Charter</option>
                        </select>
                    </div>

                    {{-- Agency --}}
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Agency</label>
                        <select name="agency_id" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] text-sm transition">
                            <option value="">Semua Agency</option>
                            @foreach($agencies as $agency)
                            <option value="{{ $agency->id }}" {{ request('agency_id') == $agency->id ? 'selected' : '' }}>
                                {{ $agency->agency_name }} ({{ $agency->city_name }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Rentang Harga --}}
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Harga/Seat (Rp)</label>
                        <div class="flex gap-2 items-center">
                            <input type="number" name="price_min" value="{{ request('price_min') }}" 
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] text-sm"
                                   placeholder="Min" min="0">
                            <span class="text-gray-400">-</span>
                            <input type="number" name="price_max" value="{{ request('price_max') }}" 
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] text-sm"
                                   placeholder="Max" min="0">
                        </div>
                    </div>

                    <button type="submit" class="w-full btn-gomad-primary text-center py-2.5 text-sm mt-2">Terapkan Filter</button>
                </form>
            </div>
        </div>

        {{-- ═══════════════════════════════════ --}}
        {{-- RESULTS --}}
        {{-- ═══════════════════════════════════ --}}
        <div class="lg:col-span-3">
            {{-- Toolbar --}}
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6 border-b border-[#E5E5E5] pb-4">
                <p class="text-sm text-gray-500 font-light">
                    Menampilkan <strong class="text-[#111111]">{{ $schedules->total() }}</strong> jadwal
                    @php
                        $activeFilter = request('origin_city_code') || request('destination_city_code') 
                            ? [\App\Models\City::find(request('origin_city_code'))?->name, \App\Models\City::find(request('destination_city_code'))?->name]
                            : [request('origin'), request('destination')];
                    @endphp
                    @if($activeFilter[0] || $activeFilter[1])
                    <span class="text-gray-400">
                        {{ $activeFilter[0] ? 'dari ' . $activeFilter[0] : '' }}
                        {{ $activeFilter[0] && $activeFilter[1] ? ' ke ' : '' }}
                        {{ $activeFilter[1] ? $activeFilter[1] : '' }}
                    </span>
                    @endif
                </p>
                <div class="flex items-center gap-3">
                    {{-- Sorting --}}
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono uppercase tracking-wider text-gray-500">Urut:</span>
                        <select onchange="window.location.href=this.value" class="text-xs border border-[#E5E5E5] rounded-lg px-2 py-1 bg-white text-[#111111]">
                            <option value="{{ request()->fullUrlWithQuery(['sort' => 'default']) }}" {{ !request('sort') || request('sort') == 'default' ? 'selected' : '' }}>Default</option>
                            <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_low']) }}" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Harga Terendah</option>
                            <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_high']) }}" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Harga Tertinggi</option>
                        </select>
                    </div>
                    
                    {{-- View Toggle --}}
                    <div class="flex bg-[#F5F5F5] rounded-lg p-1">
                        <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" 
                           class="px-3 py-1.5 rounded-md text-sm transition {{ $viewMode == 'grid' ? 'bg-white shadow text-[#C1121F] font-medium' : 'text-gray-500 hover:text-[#111111]' }}">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" 
                           class="px-3 py-1.5 rounded-md text-sm transition {{ $viewMode == 'list' ? 'bg-white shadow text-[#C1121F] font-medium' : 'text-gray-500 hover:text-[#111111]' }}">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            @if($schedules->isEmpty())
            <div class="card-gomad p-12 text-center border-[#E5E5E5]">
                <div class="w-16 h-16 bg-[#F5F5F5] rounded-[12px] flex items-center justify-center mx-auto mb-4 border border-[#E5E5E5]">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <p class="text-gray-500 text-lg font-light">Tidak ada jadwal ditemukan.</p>
                <p class="text-gray-400 text-sm mt-2">Coba ubah filter atau cari dengan kata kunci berbeda.</p>
                <a href="{{ route('customer.search') }}" class="inline-block mt-4 text-[#C1121F] hover:underline font-medium">Reset Filter</a>
            </div>
            @else
                @if($viewMode == 'grid')
                <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($schedules as $s)
                    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 shadow-sm hover:border-[#C1121F] transition-colors group">
                        {{-- Agency Info --}}
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-[#F5F5F5] flex items-center justify-center overflow-hidden flex-shrink-0 border border-[#E5E5E5]">
                                @if($s->agency->logo)<img src="{{ $s->agency->logo }}" class="w-full h-full object-cover">@else<span class="text-lg">🏢</span>@endif
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-sm text-[#111111] truncate">{{ $s->agency->agency_name }}</p>
                                <div class="flex items-center gap-1 text-xs text-gray-400 font-mono">
                                    <span>⭐ {{ number_format($s->agency->rating, 1) }}</span>
                                    <span>•</span>
                                    <span>{{ $s->agency->city_name }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Route Info --}}
                        <p class="text-sm font-medium text-[#111111] mb-1">{{ $s->route->route_name }}</p>
                        <p class="text-xs text-gray-500 font-light mb-3">
                            {{ $s->route->origin_city_name }} → {{ $s->route->destination_city_name }}
                        </p>

                        {{-- Schedule Info --}}
                        <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3 mb-3">
                            <div class="flex justify-between text-sm">
                                <span class="font-medium text-[#111111]">{{ $s->departure_date->format('d M Y') }}</span>
                                <span class="font-mono">{{ $s->departure_time }}</span>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 mt-1 font-mono uppercase tracking-wider">
                                <span>{{ $s->vehicle->plate_number ?? '-' }}</span>
                                <span class="text-[#C1121F]">{{ $s->travel_class }}</span>
                            </div>
                        </div>

                        {{-- Price & Booking --}}
                        <div class="flex justify-between items-center border-t border-[#E5E5E5] pt-3">
                            <div>
                                <p class="font-bold text-[#C1121F] font-mono">Rp {{ number_format($s->price_per_seat, 0, ',', '.') }}</p>
                                <p class="text-xs {{ $s->available_seats > 0 ? 'text-green-600' : 'text-[#C1121F]' }} font-mono uppercase tracking-wider">
                                    {{ $s->available_seats > 0 ? $s->available_seats . ' kursi' : 'Penuh' }}
                                </p>
                            </div>
                            @if($s->available_seats > 0)
                            <a href="{{ route('customer.booking.create', $s) }}" class="btn-gomad-primary text-sm py-2 px-4 rounded-[12px]">Booking</a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                {{-- List View --}}
                <div class="space-y-3">
                    @foreach($schedules as $s)
                    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 shadow-sm hover:border-[#C1121F] transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-[#F5F5F5] flex items-center justify-center overflow-hidden flex-shrink-0 border border-[#E5E5E5]">
                                @if($s->agency->logo)<img src="{{ $s->agency->logo }}" class="w-full h-full object-cover">@else<span class="text-xl">🏢</span>@endif
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-bold text-[#111111]">{{ $s->agency->agency_name }}</p>
                                    <span class="text-xs text-gray-400 font-mono">⭐ {{ number_format($s->agency->rating, 1) }}</span>
                                    <span class="text-xs text-gray-400">{{ $s->agency->city_name }}</span>
                                </div>
                                <p class="text-sm text-gray-500 font-light">{{ $s->route->route_name }}</p>
                                <p class="text-xs text-gray-400 font-mono">
                                    {{ $s->departure_date->format('d M Y') }} {{ $s->departure_time }} | 
                                    {{ $s->vehicle->plate_number }} | 
                                    <span class="text-[#C1121F] uppercase">{{ $s->travel_class }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 flex-shrink-0">
                            <div class="text-right">
                                <p class="font-bold text-[#C1121F] font-mono text-lg">Rp {{ number_format($s->price_per_seat, 0, ',', '.') }}</p>
                                <p class="text-xs {{ $s->available_seats > 0 ? 'text-green-600' : 'text-[#C1121F]' }} font-mono uppercase tracking-wider">
                                    {{ $s->available_seats > 0 ? $s->available_seats . ' kursi' : 'Penuh' }}
                                </p>
                            </div>
                            @if($s->available_seats > 0)
                            <a href="{{ route('customer.booking.create', $s) }}" class="btn-gomad-primary text-sm py-2 px-5 rounded-[12px] whitespace-nowrap">Booking</a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                <div class="mt-6">{{ $schedules->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection