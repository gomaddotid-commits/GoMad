@extends('layouts.public')

@section('title', 'Cari Jadwal')
@section('meta_description', 'Cari jadwal travel antar kota dengan mudah. Temukan jadwal yang sesuai dengan rute dan tanggal keberangkatan Anda.')
@section('og_image', asset('images/og-search.jpg'))

@section('content')
@php
    $allCities = \App\Models\City::with('province')->orderBy('name')->get();
    $agencies = \App\Models\Agency::where('is_verified', true)->orderBy('agency_name')->get();
    $months = [];
    for ($m = 1; $m <= 12; $m++) {
        $months[] = ['value' => $m, 'label' => \Carbon\Carbon::create()->month($m)->locale('id')->monthName];
    }
    
    $query = \App\Models\Schedule::with(['route.originCity', 'route.destinationCity', 'agency', 'vehicle'])
        ->where('is_active', true)
        ->where('departure_date', '>=', now()->toDateString());

    if (request('origin')) {
        $query->whereHas('route.stops', fn($q) => $q->whereHas('city', fn($sq) => $sq->where('name', 'like', '%' . request('origin') . '%')));
    }
    if (request('destination')) {
        $query->whereHas('route.stops', fn($q) => $q->whereHas('city', fn($sq) => $sq->where('name', 'like', '%' . request('destination') . '%')));
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

    $viewMode = request('view', 'grid');
    $schedules = $query->orderBy('departure_date')
        ->orderBy('departure_time')
        ->paginate(12);
@endphp

<div class="section">
    <div class="container-magazine">
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-[#111111] mb-2">Cari Jadwal Travel</h1>
            <p class="text-gray-500 font-light">Temukan jadwal yang sesuai dengan kebutuhan Anda.</p>
        </div>

        <div class="grid lg:grid-cols-4 gap-8">
            {{-- SIDEBAR FILTER --}}
            <div class="lg:col-span-1">
                <div class="card-gomad p-5 sticky top-24 border-[#E5E5E5]">
                    <div class="flex items-center justify-between mb-4 border-b border-[#E5E5E5] pb-3">
                        <h3 class="font-bold text-[#111111] font-mono uppercase tracking-wider text-sm">Filter</h3>
                        @if(request()->anyFilled(['origin', 'destination', 'travel_class', 'agency_id']))
                        <a href="{{ route('search') }}" class="text-xs text-[#C1121F] hover:underline font-medium">Reset</a>
                        @endif
                    </div>
                    
                    <form action="{{ route('search') }}" method="GET" class="space-y-4">
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kota Asal</label>
                            <select name="origin" class="w-full px-3 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent font-medium text-[#111111] appearance-none cursor-pointer">
                                <option value="">Semua Kota</option>
                                @foreach($allCities as $city)
                                <option value="{{ $city->name }}" {{ request('origin') == $city->name ? 'selected' : '' }}>{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kota Tujuan</label>
                            <select name="destination" class="w-full px-3 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent font-medium text-[#111111] appearance-none cursor-pointer">
                                <option value="">Semua Kota</option>
                                @foreach($allCities as $city)
                                <option value="{{ $city->name }}" {{ request('destination') == $city->name ? 'selected' : '' }}>{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Tanggal</label>
                            <input type="date" name="date" value="{{ request('date') }}" 
                                   class="w-full px-3 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent font-medium text-[#111111] cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kelas</label>
                            <select name="travel_class" class="w-full px-3 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent font-medium text-[#111111] appearance-none cursor-pointer">
                                <option value="">Semua Kelas</option>
                                <option value="economy" {{ request('travel_class') == 'economy' ? 'selected' : '' }}>Ekonomi</option>
                                <option value="premium" {{ request('travel_class') == 'premium' ? 'selected' : '' }}>Premium</option>
                                <option value="charter" {{ request('travel_class') == 'charter' ? 'selected' : '' }}>Charter</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Agency</label>
                            <select name="agency_id" class="w-full px-3 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent font-medium text-[#111111] appearance-none cursor-pointer">
                                <option value="">Semua Agency</option>
                                @foreach($agencies as $agency)
                                <option value="{{ $agency->id }}" {{ request('agency_id') == $agency->id ? 'selected' : '' }}>{{ $agency->agency_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="w-full btn-gomad-primary text-center py-2.5 text-sm mt-2">Terapkan Filter</button>
                    </form>
                </div>
            </div>

            {{-- RESULTS --}}
            <div class="lg:col-span-3">
                {{-- TOOLBAR --}}
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6 border-b border-[#E5E5E5] pb-4">
                    <p class="text-sm text-gray-500">
                        Menampilkan <strong class="text-[#111111]">{{ $schedules->total() }}</strong> jadwal
                        @if(request('origin') || request('destination'))
                        <span class="text-gray-400">
                            {{ request('origin') ? 'dari ' . request('origin') : '' }}
                            {{ request('origin') && request('destination') ? 'ke' : '' }}
                            {{ request('destination') ? request('destination') : '' }}
                        </span>
                        @endif
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono uppercase tracking-wider text-gray-500">Tampilan:</span>
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
                    <div class="w-16 h-16 bg-[#F5F5F5] rounded-[12px] flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <p class="text-gray-500 text-lg font-light">Tidak ada jadwal ditemukan.</p>
                    <a href="{{ route('search') }}" class="inline-block mt-4 text-[#C1121F] hover:underline font-medium">Reset Filter</a>
                </div>
                @else
                    @if($viewMode == 'grid')
                    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($schedules as $schedule)
                        <div class="card-gomad p-5 group border-[#E5E5E5] hover:border-[#C1121F]">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-full bg-[#F5F5F5] flex items-center justify-center overflow-hidden flex-shrink-0 group-hover:bg-[#C1121F]/10 transition-colors">
                                    @if($schedule->agency->logo)
                                    <img src="{{ $schedule->agency->logo }}" alt="{{ $schedule->agency->agency_name }}" class="w-full h-full object-cover">
                                    @else
                                    <span class="text-lg">🏢</span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-sm text-[#111111] truncate">{{ $schedule->agency->agency_name }}</p>
                                    <div class="flex items-center text-xs font-mono tracking-wider">
                                        <span class="text-gray-500">⭐ {{ number_format($schedule->agency->rating, 1) }}</span>
                                        @if($schedule->agency->is_verified)
                                        <span class="text-[#C1121F] ml-2">✓ Terverifikasi</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <p class="text-sm font-medium text-[#111111] mb-1">{{ $schedule->route->route_name }}</p>
                            <p class="text-xs text-gray-500 mb-3 font-mono tracking-wider">{{ $schedule->route->origin_city_name }} → {{ $schedule->route->destination_city_name }}</p>

                            <div class="bg-[#F5F5F5] rounded-[12px] p-3 mb-3 border border-[#E5E5E5]">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium text-[#111111]">{{ $schedule->departure_date->format('d M Y') }}</span>
                                    <span class="font-mono">{{ $schedule->departure_time }}</span>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-1 font-mono uppercase tracking-wider">
                                    <span>{{ $schedule->vehicle->plate_number ?? '-' }}</span>
                                    <span class="text-[#C1121F]">{{ $schedule->travel_class }}</span>
                                </div>
                            </div>

                            <div class="flex justify-between items-center border-t border-[#E5E5E5] pt-3">
                                <div>
                                    <p class="font-bold text-[#C1121F] font-mono">Rp {{ number_format($schedule->price_per_seat, 0, ',', '.') }}</p>
                                    <p class="text-xs {{ $schedule->available_seats > 0 ? 'text-green-600' : 'text-[#C1121F]' }} font-mono uppercase tracking-wider">
                                        {{ $schedule->available_seats > 0 ? $schedule->available_seats . ' kursi' : 'Penuh' }}
                                    </p>
                                </div>
                                @auth
                                    @if($schedule->available_seats > 0)
                                    <a href="{{ route('customer.booking.create', $schedule) }}" 
                                       class="btn-gomad-primary text-sm py-2 px-4 rounded-[12px]">Booking</a>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" 
                                       class="btn-gomad-outline text-sm py-2 px-4 rounded-[12px] border-[#C1121F] text-[#C1121F] hover:bg-[#C1121F] hover:text-white">Login</a>
                                @endauth
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="space-y-3">
                        @foreach($schedules as $schedule)
                        <div class="card-gomad p-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 group border-[#E5E5E5] hover:border-[#C1121F]">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-[#F5F5F5] flex items-center justify-center overflow-hidden flex-shrink-0">
                                    @if($schedule->agency->logo)
                                    <img src="{{ $schedule->agency->logo }}" alt="" class="w-full h-full object-cover">
                                    @else
                                    <span class="text-xl">🏢</span>
                                    @endif
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-bold text-[#111111]">{{ $schedule->agency->agency_name }}</p>
                                        <span class="text-gray-500 text-xs font-mono">⭐ {{ number_format($schedule->agency->rating, 1) }}</span>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ $schedule->route->route_name }}</p>
                                    <p class="text-xs text-gray-500 font-mono tracking-wider">
                                        {{ $schedule->departure_date->format('d M Y') }} {{ $schedule->departure_time }} | 
                                        {{ $schedule->vehicle->plate_number ?? '-' }} | 
                                        <span class="text-[#C1121F] uppercase">{{ $schedule->travel_class }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 flex-shrink-0">
                                <div class="text-right">
                                    <p class="font-bold text-[#C1121F] font-mono text-lg">Rp {{ number_format($schedule->price_per_seat, 0, ',', '.') }}</p>
                                    <p class="text-xs {{ $schedule->available_seats > 0 ? 'text-green-600' : 'text-[#C1121F]' }} font-mono uppercase tracking-wider">
                                        {{ $schedule->available_seats > 0 ? $schedule->available_seats . ' kursi' : 'Penuh' }}
                                    </p>
                                </div>
                                @auth
                                    @if($schedule->available_seats > 0)
                                    <a href="{{ route('customer.booking.create', $schedule) }}" 
                                       class="btn-gomad-primary text-sm py-2 px-5 rounded-[12px] whitespace-nowrap">Booking</a>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" 
                                       class="btn-gomad-outline text-sm py-2 px-5 rounded-[12px] whitespace-nowrap border-[#C1121F] text-[#C1121F] hover:bg-[#C1121F] hover:text-white">Login</a>
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
@endsection