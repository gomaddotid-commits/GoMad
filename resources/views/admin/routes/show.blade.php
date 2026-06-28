@extends('layouts.admin')

@section('title', 'Detail Rute')
@section('content')
<!-- File: resources/views/admin/routes/show.blade.php -->
<!-- Deskripsi: Halaman detail rute admin -->

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <a href="{{ route('admin.routes.index') }}" class="text-primary text-sm">← Kembali</a>
        <div class="flex gap-2">
            <a href="{{ route('admin.routes.edit', $route) }}" class="bg-blue-500 text-white px-4 py-2 rounded text-sm hover:bg-blue-600">Edit</a>
            <form action="{{ route('admin.routes.destroy', $route) }}" method="POST" onsubmit="return confirm('Nonaktifkan rute?')">
                @csrf @method('DELETE')
                <button class="bg-red-500 text-white px-4 py-2 rounded text-sm hover:bg-red-600">Nonaktifkan</button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ $route->route_name }}</h1>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <span class="text-gray-500">Kota Asal</span>
                <p class="font-semibold">{{ $route->origin_city }}</p>
            </div>
            <div>
                <span class="text-gray-500">Kota Tujuan</span>
                <p class="font-semibold">{{ $route->destination_city }}</p>
            </div>
            <div>
                <span class="text-gray-500">Jarak</span>
                <p class="font-semibold">{{ $route->distance_km ?? '-' }} km</p>
            </div>
            <div>
                <span class="text-gray-500">Estimasi</span>
                <p class="font-semibold">{{ $route->estimated_duration ?? '-' }} menit</p>
            </div>
            <div>
                <span class="text-gray-500">Status</span>
                <p><span class="px-2 py-1 rounded text-xs {{ $route->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $route->is_active ? 'Aktif' : 'Nonaktif' }}
                </span></p>
            </div>
        </div>
    </div>

    <!-- Stops -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold">Stops ({{ $route->stops->count() }})</h2>
            <button onclick="document.getElementById('addStopForm').classList.toggle('hidden')" 
                    class="bg-primary text-white px-3 py-1 rounded text-sm hover:bg-primary-dark">
                + Tambah Stop
            </button>
        </div>

        <!-- Add Stop Form -->
        <form id="addStopForm" action="{{ route('admin.routes.stops.add', $route) }}" method="POST" class="hidden bg-gray-50 p-4 rounded-lg mb-4">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <input type="text" name="city_name" placeholder="Nama Kota" class="px-3 py-2 border rounded" required>
                <input type="number" name="stop_order" placeholder="Urutan" class="px-3 py-2 border rounded">
                <input type="number" name="latitude" step="0.0000001" placeholder="Latitude" class="px-3 py-2 border rounded">
                <input type="number" name="longitude" step="0.0000001" placeholder="Longitude" class="px-3 py-2 border rounded">
            </div>
            <button type="submit" class="mt-3 bg-primary text-white px-4 py-2 rounded text-sm">Simpan Stop</button>
        </form>

        <!-- Stops List -->
        <div class="space-y-2">
            @foreach($route->stops as $stop)
            <div class="flex justify-between items-center bg-gray-50 px-4 py-3 rounded">
                <div>
                    <span class="font-medium">{{ $stop->city_name }}</span>
                    <span class="text-xs text-gray-500 ml-2">Order: {{ $stop->stop_order }}</span>
                    @if($stop->latitude && $stop->longitude)
                    <span class="text-xs text-gray-400 ml-2">({{ $stop->latitude }}, {{ $stop->longitude }})</span>
                    @endif
                </div>
                <form action="{{ route('admin.routes.stops.remove', [$route, $stop]) }}" method="POST" onsubmit="return confirm('Hapus stop?')">
                    @csrf @method('DELETE')
                    <button class="text-red-500 text-sm hover:underline">Hapus</button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection