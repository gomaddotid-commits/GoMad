@extends('layouts.agency')

@section('title', 'Kendaraan')
@section('content')
<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <h1 class="text-2xl font-bold text-secondary">Kendaraan</h1>
        <a href="{{ route('agency.vehicles.create') }}" class="btn-primary text-sm inline-flex items-center gap-2 self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Kendaraan
        </a>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($vehicles as $vehicle)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
            <div class="h-40 bg-gray-100 flex items-center justify-center overflow-hidden">
                @if($vehicle->vehicle_image)
                <img src="{{ asset('storage/' . $vehicle->vehicle_image) }}" alt="{{ $vehicle->plate_number }}" class="w-full h-full object-cover">
                @else
                <span class="text-5xl">🚐</span>
                @endif
            </div>
            <div class="p-5">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="font-bold text-lg">{{ $vehicle->plate_number }}</h3>
                        <p class="text-sm text-gray-600">{{ $vehicle->brand }} {{ $vehicle->model }} ({{ $vehicle->year }})</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $vehicle->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $vehicle->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                <div class="flex items-center gap-3 text-sm text-gray-500 mb-4">
                    <span>{{ $vehicle->capacity }} seat</span>
                    <span>|</span>
                    <span class="capitalize">{{ $vehicle->type }}</span>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('agency.vehicles.edit', $vehicle) }}" class="flex-1 text-center bg-blue-50 text-blue-600 py-2 rounded-lg text-sm font-medium hover:bg-blue-100 transition">Edit</a>
                    <form action="{{ route('agency.vehicles.destroy', $vehicle) }}" method="POST" class="flex-1" onsubmit="return confirm('Hapus?')">
                        @csrf @method('DELETE')
                        <button class="w-full bg-red-50 text-red-600 py-2 rounded-lg text-sm font-medium hover:bg-red-100 transition">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center text-gray-500">
            <p class="text-lg">Belum ada kendaraan.</p>
            <a href="{{ route('agency.vehicles.create') }}" class="text-primary-600 hover:underline mt-2 inline-block">+ Tambah Kendaraan</a>
        </div>
        @endforelse
    </div>
</div>
@endsection