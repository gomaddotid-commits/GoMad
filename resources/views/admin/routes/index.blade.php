@extends('layouts.admin')

@section('title', 'Rute')
@section('content')
<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <h1 class="text-2xl font-bold text-secondary">Daftar Rute</h1>
        <a href="{{ route('admin.routes.create') }}" class="btn-primary text-sm inline-flex items-center gap-2 self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Rute
        </a>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($routes as $route)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition group">
            <div class="h-40 bg-gray-100 flex items-center justify-center overflow-hidden">
                @if($route->photo)
                <img src="{{  $route->photo }}" alt="{{ $route->route_name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                @else
                <div class="text-center text-gray-400"><span class="text-5xl block mb-2">🗺️</span><span class="text-sm">Belum ada foto</span></div>
                @endif
            </div>
            <div class="p-5">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="font-bold text-secondary">{{ $route->route_name }}</h3>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $route->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $route->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-500 mb-3">
                    <span>{{ $route->origin_city }}</span><span>→</span><span>{{ $route->destination_city }}</span>
                </div>
                <div class="flex items-center gap-4 text-xs text-gray-500 mb-4">
                    <span>🛑 {{ $route->stops_count }} stops</span>
                    @if($route->distance_km)<span>📏 {{ $route->distance_km }} km</span>@endif
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.routes.show', $route) }}" class="flex-1 text-center bg-blue-50 text-blue-600 py-2 rounded-lg text-sm font-medium hover:bg-blue-100 transition">Detail</a>
                    <a href="{{ route('admin.routes.edit', $route) }}" class="flex-1 text-center bg-gray-50 text-gray-700 py-2 rounded-lg text-sm font-medium hover:bg-gray-100 transition">Edit</a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center text-gray-500">
            <p class="text-lg">Belum ada rute.</p>
            <a href="{{ route('admin.routes.create') }}" class="text-primary-600 hover:underline mt-2 inline-block">+ Tambah Rute</a>
        </div>
        @endforelse
    </div>
</div>
@endsection