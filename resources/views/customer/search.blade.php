@extends('layouts.customer')

@section('title', 'Cari Jadwal')
@section('content')
@php
    $allCities = \App\Models\RouteStop::select('city_name')->distinct()->orderBy('city_name')->get();
    $agencies = \App\Models\Agency::where('is_verified', true)->orderBy('agency_name')->get();
    $months = [];
    for ($m = 1; $m <= 12; $m++) { $months[] = ['value' => $m, 'label' => \Carbon\Carbon::create()->month($m)->locale('id')->monthName]; }
    
    $query = \App\Models\Schedule::with(['route', 'agency', 'vehicle'])->where('is_active', true)->where('departure_date', '>=', now()->toDateString());
    if (request('origin')) { $query->whereHas('route', fn($q) => $q->where('origin_city', 'like', '%' . request('origin') . '%')); }
    if (request('destination')) { $query->whereHas('route', fn($q) => $q->where('destination_city', 'like', '%' . request('destination') . '%')); }
    if (request('date')) { $query->where('departure_date', request('date')); }
    if (request('month')) { $query->whereMonth('departure_date', request('month'))->whereYear('departure_date', request('year', now()->year)); }
    if (request('travel_class')) { $query->where('travel_class', request('travel_class')); }
    if (request('agency_id')) { $query->where('agency_id', request('agency_id')); }
    
    $viewMode = request('view', 'grid');
    $schedules = $query->orderBy('departure_date')->orderBy('departure_time')->paginate(12);
@endphp

<div class="container-custom py-8">
    <h1 class="text-2xl font-bold text-secondary mb-6">Cari Jadwal Travel</h1>

    <div class="grid lg:grid-cols-4 gap-8">
        {{-- Filter Sidebar --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sticky top-20">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-secondary">Filter</h3>
                    @if(request()->anyFilled(['origin', 'destination', 'month', 'travel_class', 'agency_id']))
                    <a href="{{ route('customer.search') }}" class="text-xs text-primary-600 hover:underline">Reset</a>
                    @endif
                </div>
                <form action="{{ route('customer.search') }}" method="GET" class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kota Asal</label>
                        <select name="origin" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50">
                            <option value="">Semua</option>
                            @foreach($allCities as $c)
                            <option value="{{ $c->city_name }}" {{ request('origin') == $c->city_name ? 'selected' : '' }}>{{ $c->city_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kota Tujuan</label>
                        <select name="destination" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50">
                            <option value="">Semua</option>
                            @foreach($allCities as $c)
                            <option value="{{ $c->city_name }}" {{ request('destination') == $c->city_name ? 'selected' : '' }}>{{ $c->city_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Bulan</label>
                        <select name="month" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50">
                            <option value="">Semua</option>
                            @foreach($months as $m)
                            <option value="{{ $m['value'] }}" {{ request('month') == $m['value'] ? 'selected' : '' }}>{{ $m['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Kelas</label>
                        <select name="travel_class" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50">
                            <option value="">Semua</option>
                            <option value="economy">Ekonomi</option>
                            <option value="premium">Premium</option>
                            <option value="charter">Charter</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Agency</label>
                        <select name="agency_id" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50">
                            <option value="">Semua</option>
                            @foreach($agencies as $a)
                            <option value="{{ $a->id }}">{{ $a->agency_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-primary-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-primary-700 transition">Terapkan</button>
                </form>
            </div>
        </div>

        {{-- Results --}}
        <div class="lg:col-span-3">
            <div class="flex justify-between items-center mb-4">
                <p class="text-sm text-gray-600"><strong>{{ $schedules->total() }}</strong> jadwal</p>
                <div class="flex bg-gray-100 rounded-lg p-1">
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" class="px-3 py-1.5 rounded-md text-sm {{ $viewMode == 'grid' ? 'bg-white shadow text-primary-600' : 'text-gray-500' }}">Grid</a>
                    <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" class="px-3 py-1.5 rounded-md text-sm {{ $viewMode == 'list' ? 'bg-white shadow text-primary-600' : 'text-gray-500' }}">List</a>
                </div>
            </div>

            @if($schedules->isEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center text-gray-500">Tidak ada jadwal.</div>
            @else
                @if($viewMode == 'grid')
                <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($schedules as $s)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center overflow-hidden flex-shrink-0">
                                @if($s->agency->logo)<img src="{{ asset('storage/'.$s->agency->logo) }}" class="w-full h-full object-cover">@else🏢@endif
                            </div>
                            <div>
                                <p class="font-semibold text-sm">{{ $s->agency->agency_name }}</p>
                                <p class="text-xs text-yellow-500">⭐ {{ number_format($s->agency->rating,1) }}</p>
                            </div>
                        </div>
                        <p class="text-sm font-medium mb-1">{{ $s->route->route_name }}</p>
                        <p class="text-xs text-gray-500 mb-3">{{ $s->route->origin_city }} → {{ $s->route->destination_city }}</p>
                        <div class="bg-gray-50 rounded-xl p-3 mb-3">
                            <div class="flex justify-between text-sm"><span>{{ $s->departure_date->format('d M Y') }}</span><span>{{ $s->departure_time }}</span></div>
                            <div class="flex justify-between text-xs text-gray-500 mt-1"><span>{{ $s->vehicle->plate_number ?? '-' }}</span><span class="capitalize">{{ $s->travel_class }}</span></div>
                        </div>
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-bold text-primary-600">Rp {{ number_format($s->price_per_seat,0,',','.') }}</p>
                                <p class="text-xs {{ $s->available_seats > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $s->available_seats > 0 ? $s->available_seats.' kursi' : 'Penuh' }}</p>
                            </div>
                            @if($s->available_seats > 0)
                            <a href="{{ route('customer.booking.create', $s) }}" class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-primary-700 transition">Booking</a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="space-y-3">
                    @foreach($schedules as $s)
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-primary-50 flex items-center justify-center overflow-hidden flex-shrink-0">
                                @if($s->agency->logo)<img src="{{ asset('storage/'.$s->agency->logo) }}" class="w-full h-full object-cover">@else🏢@endif
                            </div>
                            <div>
                                <p class="font-bold">{{ $s->agency->agency_name }}</p>
                                <p class="text-sm text-gray-600">{{ $s->route->route_name }}</p>
                                <p class="text-xs text-gray-500">{{ $s->departure_date->format('d M Y') }} {{ $s->departure_time }} | {{ $s->vehicle->plate_number }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="font-bold text-primary-600 text-lg">Rp {{ number_format($s->price_per_seat,0,',','.') }}</p>
                                <p class="text-xs {{ $s->available_seats > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $s->available_seats }} kursi</p>
                            </div>
                            @if($s->available_seats > 0)
                            <a href="{{ route('customer.booking.create', $s) }}" class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-primary-700">Booking</a>
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