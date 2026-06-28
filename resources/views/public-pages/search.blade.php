@extends('layouts.public')

@section('title', 'Cari Jadwal')
@section('meta_description', 'Cari jadwal travel antar kota di Madura. Temukan jadwal yang sesuai dengan rute dan tanggal keberangkatan Anda.')
@section('og_image', asset('images/og-search.jpg'))

@section('content')
@php
    $allCities = \App\Models\RouteStop::select('city_name')->distinct()->orderBy('city_name')->get();
    $agencies = \App\Models\Agency::where('is_verified', true)->orderBy('agency_name')->get();
    $months = [];
    for ($m = 1; $m <= 12; $m++) {
        $months[] = ['value' => $m, 'label' => \Carbon\Carbon::create()->month($m)->locale('id')->monthName];
    }
    
    $query = \App\Models\Schedule::with(['route', 'agency', 'vehicle'])
        ->where('is_active', true)
        ->where('departure_date', '>=', now()->toDateString());

    if (request('origin')) {
        $query->whereHas('route', function($q) {
            $q->where('origin_city', 'like', '%' . request('origin') . '%');
        });
    }
    if (request('destination')) {
        $query->whereHas('route', function($q) {
            $q->where('destination_city', 'like', '%' . request('destination') . '%');
        });
    }
    if (request('date')) {
        $query->whereDate('departure_date', request('date'));
    }
    if (request('month')) {
        $query->whereMonth('departure_date', request('month'))
              ->whereYear('departure_date', request('year', now()->year));
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
    <div class="container-custom">
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-secondary mb-2">Cari Jadwal Travel</h1>
            <p class="text-gray-600">Temukan jadwal yang sesuai dengan kebutuhan Anda</p>
        </div>

        <div class="grid lg:grid-cols-4 gap-8">
            {{-- Sidebar Filter --}}
            <div class="lg:col-span-1">
                <div class="card p-5 sticky top-24">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-secondary">Filter</h3>
                        @if(request()->anyFilled(['origin', 'destination', 'month', 'travel_class', 'agency_id']))
                        <a href="{{ route('search') }}" class="text-xs text-primary-600 hover:underline font-medium">Reset</a>
                        @endif
                    </div>
                    
                    <form action="{{ route('search') }}" method="GET" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Kota Asal</label>
                            <select name="origin" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-600 bg-gray-50">
                                <option value="">Semua Kota</option>
                                @foreach($allCities as $city)
                                <option value="{{ $city->city_name }}" {{ request('origin') == $city->city_name ? 'selected' : '' }}>{{ $city->city_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Kota Tujuan</label>
                            <select name="destination" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-600 bg-gray-50">
                                <option value="">Semua Kota</option>
                                @foreach($allCities as $city)
                                <option value="{{ $city->city_name }}" {{ request('destination') == $city->city_name ? 'selected' : '' }}>{{ $city->city_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
                            <input type="date" name="date" value="{{ request('date') }}" 
                                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-600 bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Bulan</label>
                            <select name="month" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-600 bg-gray-50">
                                <option value="">Semua Bulan</option>
                                @foreach($months as $month)
                                <option value="{{ $month['value'] }}" {{ request('month') == $month['value'] ? 'selected' : '' }}>{{ $month['label'] }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="year" value="{{ request('year', now()->year) }}">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Kelas</label>
                            <select name="travel_class" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-600 bg-gray-50">
                                <option value="">Semua Kelas</option>
                                <option value="economy" {{ request('travel_class') == 'economy' ? 'selected' : '' }}>Ekonomi</option>
                                <option value="premium" {{ request('travel_class') == 'premium' ? 'selected' : '' }}>Premium</option>
                                <option value="charter" {{ request('travel_class') == 'charter' ? 'selected' : '' }}>Charter</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Agency</label>
                            <select name="agency_id" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-600 bg-gray-50">
                                <option value="">Semua Agency</option>
                                @foreach($agencies as $agency)
                                <option value="{{ $agency->id }}" {{ request('agency_id') == $agency->id ? 'selected' : '' }}>{{ $agency->agency_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-primary-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-primary-700 transition">
                            Terapkan Filter
                        </button>
                    </form>
                </div>
            </div>

            {{-- Results --}}
            <div class="lg:col-span-3">
                {{-- Toolbar --}}
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
                    <p class="text-sm text-gray-600">
                        Menampilkan <strong>{{ $schedules->total() }}</strong> jadwal
                        @if(request('origin') || request('destination'))
                        <span class="text-gray-400">
                            {{ request('origin') ? 'dari ' . request('origin') : '' }}
                            {{ request('origin') && request('destination') ? 'ke' : '' }}
                            {{ request('destination') ? request('destination') : '' }}
                        </span>
                        @endif
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500">Tampilan:</span>
                        <div class="flex bg-gray-100 rounded-lg p-1">
                            <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" 
                               class="px-3 py-1.5 rounded-md text-sm transition {{ $viewMode == 'grid' ? 'bg-white shadow text-primary-600 font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" 
                               class="px-3 py-1.5 rounded-md text-sm transition {{ $viewMode == 'list' ? 'bg-white shadow text-primary-600 font-medium' : 'text-gray-500 hover:text-gray-700' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                @if($schedules->isEmpty())
                <div class="card p-12 text-center">
                    <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <p class="text-gray-500 text-lg">Tidak ada jadwal ditemukan.</p>
                    <p class="text-gray-400 text-sm mt-2">Coba ubah filter atau cari dengan kata kunci berbeda.</p>
                    <a href="{{ route('search') }}" class="inline-block mt-4 text-primary-600 hover:underline font-medium">Reset Filter</a>
                </div>
                @else
                    @if($viewMode == 'grid')
                    {{-- GRID VIEW --}}
                    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach($schedules as $schedule)
                        <div class="card p-5 group">
                            {{-- Agency Info --}}
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center overflow-hidden flex-shrink-0">
                                    @if($schedule->agency->logo)
                                    <img src="{{ asset('storage/' . $schedule->agency->logo) }}" alt="{{ $schedule->agency->agency_name }}" class="w-full h-full object-cover">
                                    @else
                                    <span class="text-lg">🏢</span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-sm text-secondary truncate">{{ $schedule->agency->agency_name }}</p>
                                    <div class="flex items-center text-xs">
                                        <span class="text-yellow-500">⭐ {{ number_format($schedule->agency->rating, 1) }}</span>
                                        @if($schedule->agency->is_verified)
                                        <span class="text-blue-500 ml-1">✓</span>
                                        @endif
                                        <span class="text-gray-400 ml-2">{{ $schedule->agency->total_bookings }} booking</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Route Info --}}
                            <p class="text-sm font-medium text-secondary mb-1">{{ $schedule->route->route_name }}</p>
                            <p class="text-xs text-gray-500 mb-3">{{ $schedule->route->origin_city }} → {{ $schedule->route->destination_city }}</p>

                            {{-- Schedule Info --}}
                            <div class="bg-gray-50 rounded-xl p-3 mb-3">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium">{{ $schedule->departure_date->format('d M Y') }}</span>
                                    <span>{{ $schedule->departure_time }}</span>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>{{ $schedule->vehicle->plate_number ?? '-' }}</span>
                                    <span class="capitalize">{{ $schedule->travel_class }}</span>
                                </div>
                            </div>

                            {{-- Price & Booking --}}
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="font-bold text-primary-600">Rp {{ number_format($schedule->price_per_seat, 0, ',', '.') }}</p>
                                    <p class="text-xs {{ $schedule->available_seats > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $schedule->available_seats > 0 ? $schedule->available_seats . ' kursi tersedia' : 'Penuh' }}
                                    </p>
                                </div>
                                @auth
                                    @if($schedule->available_seats > 0)
                                    <a href="{{ route('customer.booking.create', $schedule) }}" 
                                       class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-primary-700 transition">
                                        Booking
                                    </a>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" 
                                       class="border-2 border-primary-600 text-primary-600 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-primary-600 hover:text-white transition">
                                        Login
                                    </a>
                                @endauth
                            </div>

                            {{-- Promo Badge --}}
                            @php
                                $schedulePromos = $schedule->promos()->where('is_active', true)->where('start_date', '<=', now())->where('end_date', '>=', now())->first();
                            @endphp
                            @if($schedulePromos)
                            <div class="mt-2 bg-purple-50 text-purple-700 text-xs px-2 py-1 rounded-lg text-center font-medium">
                                Promo {{ $schedulePromos->discount_percent }}% tersedia
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    {{-- LIST VIEW --}}
                    <div class="space-y-3">
                        @foreach($schedules as $schedule)
                        <div class="card p-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 group">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-full bg-primary-50 flex items-center justify-center overflow-hidden flex-shrink-0">
                                    @if($schedule->agency->logo)
                                    <img src="{{ asset('storage/' . $schedule->agency->logo) }}" alt="" class="w-full h-full object-cover">
                                    @else
                                    <span class="text-xl">🏢</span>
                                    @endif
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-bold text-secondary">{{ $schedule->agency->agency_name }}</p>
                                        <span class="text-yellow-500 text-xs">⭐ {{ number_format($schedule->agency->rating, 1) }}</span>
                                    </div>
                                    <p class="text-sm text-gray-600">{{ $schedule->route->route_name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $schedule->departure_date->format('d M Y') }} {{ $schedule->departure_time }} | 
                                        {{ $schedule->vehicle->plate_number ?? '-' }} | 
                                        <span class="capitalize">{{ $schedule->travel_class }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 flex-shrink-0">
                                <div class="text-right">
                                    <p class="font-bold text-primary-600 text-lg">Rp {{ number_format($schedule->price_per_seat, 0, ',', '.') }}</p>
                                    <p class="text-xs {{ $schedule->available_seats > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $schedule->available_seats > 0 ? $schedule->available_seats . ' kursi' : 'Penuh' }}
                                    </p>
                                </div>
                                @auth
                                    @if($schedule->available_seats > 0)
                                    <a href="{{ route('customer.booking.create', $schedule) }}" 
                                       class="bg-primary-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-primary-700 transition whitespace-nowrap">
                                        Booking
                                    </a>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" 
                                       class="border-2 border-primary-600 text-primary-600 px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-primary-600 hover:text-white transition whitespace-nowrap">
                                        Login
                                    </a>
                                @endauth
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Pagination --}}
                    <div class="mt-8">
                        {{ $schedules->appends(request()->query())->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection