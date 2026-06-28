@extends('layouts.customer')

@section('title', 'Home')
@section('content')
@php
    $cities = \App\Models\RouteStop::select('city_name')->distinct()->orderBy('city_name')->get();
    $popularRoutes = \App\Models\Route::where('is_active', true)->withCount(['schedules' => fn($q) => $q->where('departure_date', '>=', now()->toDateString())->where('is_active', true)])->orderByDesc('schedules_count')->limit(4)->get();
@endphp

<div class="container-custom py-8">
    {{-- Hero Search --}}
    <div class="bg-gradient-to-br from-primary-50 to-white rounded-3xl p-6 md:p-10 mb-8 border border-primary-100">
        <h1 class="text-2xl md:text-3xl font-bold text-secondary mb-2">Mau kemana hari ini?</h1>
        <p class="text-gray-600 mb-6">Cari jadwal travel dan booking langsung</p>
        
        <form action="{{ route('customer.search') }}" method="GET" class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Kota Asal</label>
                <select name="origin" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-600 bg-white">
                    <option value="">Semua</option>
                    @foreach($cities as $city)
                    <option value="{{ $city->city_name }}">{{ $city->city_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Kota Tujuan</label>
                <select name="destination" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-600 bg-white">
                    <option value="">Semua</option>
                    @foreach($cities as $city)
                    <option value="{{ $city->city_name }}">{{ $city->city_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
                <input type="date" name="date" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-600 bg-white">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Kelas</label>
                <select name="travel_class" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-600 bg-white">
                    <option value="">Semua</option>
                    <option value="economy">Ekonomi</option>
                    <option value="premium">Premium</option>
                    <option value="charter">Charter</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-primary-600 text-white py-2.5 rounded-xl font-semibold hover:bg-primary-700 transition text-sm">Cari</button>
            </div>
        </form>
    </div>

    {{-- Quick Links --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <a href="{{ route('customer.bookings') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center hover:shadow-md transition group">
            <div class="w-10 h-10 bg-primary-50 rounded-xl flex items-center justify-center text-lg mx-auto mb-2 group-hover:scale-110 transition-transform">🎫</div>
            <p class="font-semibold text-secondary text-sm">Booking Saya</p>
        </a>
        <a href="{{ route('customer.search') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center hover:shadow-md transition group">
            <div class="w-10 h-10 bg-primary-50 rounded-xl flex items-center justify-center text-lg mx-auto mb-2 group-hover:scale-110 transition-transform">🔍</div>
            <p class="font-semibold text-secondary text-sm">Cari Jadwal</p>
        </a>
        <a href="{{ route('listing') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center hover:shadow-md transition group">
            <div class="w-10 h-10 bg-primary-50 rounded-xl flex items-center justify-center text-lg mx-auto mb-2 group-hover:scale-110 transition-transform">🏢</div>
            <p class="font-semibold text-secondary text-sm">Agency</p>
        </a>
        <a href="{{ route('customer.profile') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center hover:shadow-md transition group">
            <div class="w-10 h-10 bg-primary-50 rounded-xl flex items-center justify-center text-lg mx-auto mb-2 group-hover:scale-110 transition-transform">👤</div>
            <p class="font-semibold text-secondary text-sm">Profil</p>
        </a>
    </div>

    {{-- Popular Routes --}}
    @if($popularRoutes->isNotEmpty())
    <h2 class="text-xl font-bold text-secondary mb-4">Rute Populer</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($popularRoutes as $route)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-md transition">
            @if($route->photo)
            <div class="h-32 overflow-hidden">
                <img src="{{ asset('storage/' . $route->photo) }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
            </div>
            @else
            <div class="h-32 bg-gradient-to-br from-primary-100 to-primary-50 flex items-center justify-center">
                <span class="text-3xl">🗺️</span>
            </div>
            @endif
            <div class="p-3">
                <p class="font-semibold text-sm text-secondary">{{ $route->route_name }}</p>
                <p class="text-xs text-gray-500">{{ $route->schedules_count }} jadwal</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection