@extends('layouts.public')

@section('title', 'Daftar Agency')
@section('meta_description', 'Daftar agency travel terpercaya di Madura. Pilih agency favorit Anda untuk perjalanan yang nyaman.')
@section('og_image', asset('images/og-agency.jpg'))

@section('content')
@php
    $viewMode = request('view', 'grid');
    $agencies = \App\Models\Agency::where('is_verified', true)
        ->when(request('search'), fn($q) => $q->where('agency_name', 'like', '%' . request('search') . '%')->orWhere('address', 'like', '%' . request('search') . '%'))
        ->orderByDesc('rating')->paginate(12);
@endphp

<div class="section mt-10 mb-20">
    <div class="container-custom">
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-secondary mb-2">Daftar Agency Travel</h1>
            <p class="text-gray-600">Temukan agency travel terpercaya di Madura</p>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-8">
            <form action="{{ route('listing') }}" method="GET" class="flex-1 max-w-md">
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari agency..." 
                           class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-600 bg-gray-50">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </form>
            <div class="flex bg-gray-100 rounded-lg p-1">
                <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}" 
                   class="px-3 py-1.5 rounded-md text-sm {{ $viewMode == 'grid' ? 'bg-white shadow text-primary-600 font-medium' : 'text-gray-500' }}">
                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                </a>
                <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}" 
                   class="px-3 py-1.5 rounded-md text-sm {{ $viewMode == 'list' ? 'bg-white shadow text-primary-600 font-medium' : 'text-gray-500' }}">
                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </a>
            </div>
        </div>

        @if($agencies->isEmpty())
        <div class="card p-12 text-center"><p class="text-gray-500 text-lg">Belum ada agency.</p></div>
        @else
            @if($viewMode == 'grid')
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($agencies as $agency)
                <a href="{{ route('agency.profile', $agency->slug) }}" class="card overflow-hidden group block">
                    <div class="h-32 bg-gradient-to-br from-primary-100 to-primary-50 flex items-center justify-center overflow-hidden">
                        @if($agency->cover_image)
                        <img src="{{ asset('storage/' . $agency->cover_image) }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        @else <span class="text-4xl">🏢</span> @endif
                    </div>
                    <div class="p-5">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-12 h-12 rounded-full border-2 border-white -mt-10 bg-white shadow overflow-hidden">
                                @if($agency->logo)<img src="{{ asset('storage/' . $agency->logo) }}" alt="" class="w-full h-full object-cover">
                                @else <div class="w-full h-full bg-primary-50 flex items-center justify-center text-lg">🏢</div> @endif
                            </div>
                            <div>
                                <h3 class="font-bold text-secondary group-hover:text-primary-600 transition">{{ $agency->agency_name }}</h3>
                                @if($agency->is_verified)<span class="text-xs text-blue-600 font-medium">Terverifikasi</span>@endif
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 line-clamp-2 mb-3">{{ $agency->description ?? 'Belum ada deskripsi.' }}</p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-yellow-500">⭐ {{ number_format($agency->rating, 1) }}</span>
                            <span class="text-gray-500">{{ $agency->total_bookings }} booking</span>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
            @else
            <div class="space-y-4">
                @foreach($agencies as $agency)
                <a href="{{ route('agency.profile', $agency->slug) }}" class="card p-5 flex flex-col sm:flex-row items-start gap-4 group">
                    <div class="w-16 h-16 rounded-xl bg-primary-50 flex items-center justify-center text-2xl overflow-hidden flex-shrink-0">
                        @if($agency->logo)<img src="{{ asset('storage/' . $agency->logo) }}" alt="" class="w-full h-full object-cover">@else 🏢 @endif
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-secondary group-hover:text-primary-600 transition">{{ $agency->agency_name }}</h3>
                            @if($agency->is_verified)<span class="text-xs text-blue-600">✓</span>@endif
                        </div>
                        <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ $agency->description ?? 'Belum ada deskripsi.' }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $agency->address }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-yellow-500">⭐ {{ number_format($agency->rating, 1) }}</p>
                        <p class="text-xs text-gray-500">{{ $agency->total_bookings }} booking</p>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
            <div class="mt-8">{{ $agencies->links() }}</div>
        @endif
    </div>
</div>
@endsection