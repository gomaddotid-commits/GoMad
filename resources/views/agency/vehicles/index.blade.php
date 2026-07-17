@extends('layouts.agency')

@section('title', 'Kendaraan')
@section('content')
<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 border-b border-[#E5E7EB] pb-3">
        <h1 class="text-2xl font-bold text-[#111827]">Kendaraan</h1>
        <a href="{{ route('agency.vehicles.create') }}" class="btn-gomad-primary text-sm inline-flex items-center gap-2 self-start rounded-[10px]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Kendaraan
        </a>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($vehicles as $vehicle)
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] overflow-hidden shadow-gomad hover:border-[#BA1826] transition-colors">
            <div class="h-40 bg-[#F9FAFB] flex items-center justify-center overflow-hidden border-b border-[#E5E7EB]">
                @if($vehicle->vehicle_image)
                <img src="{{ $vehicle->vehicle_image }}" alt="{{ $vehicle->plate_number }}" class="w-full h-full object-cover">
                @else
                <span class="text-5xl text-gray-300">🚐</span>
                @endif
            </div>
            <div class="p-5">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="font-bold text-lg text-[#111827] font-mono">{{ $vehicle->plate_number }}</h3>
                        <p class="text-sm text-gray-500 font-light">{{ $vehicle->brand }} {{ $vehicle->model }} ({{ $vehicle->year }})</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider {{ $vehicle->is_active ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">{{ $vehicle->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                <div class="flex items-center gap-3 text-sm text-gray-500 font-mono uppercase tracking-wider mb-4">
                    <span>{{ $vehicle->capacity }} seat</span>
                    <span>|</span>
                    <span class="capitalize">{{ $vehicle->type }}</span>
                </div>
                <div class="flex gap-2 border-t border-[#E5E7EB] pt-4">
                    <a href="{{ route('agency.vehicles.edit', $vehicle) }}" class="flex-1 text-center border border-[#E5E7EB] text-[#111827] py-2 rounded-[10px] text-sm font-medium hover:bg-[#F9FAFB] transition">Edit</a>
                    <form action="{{ route('agency.vehicles.destroy', $vehicle) }}" method="POST" class="flex-1" onsubmit="return confirm('Hapus?')">
                        @csrf @method('DELETE')
                        <button class="w-full border border-red-500 text-red-600 py-2 rounded-[10px] text-sm font-medium hover:bg-red-50 transition">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white border border-[#E5E7EB] rounded-[12px] p-12 text-center text-gray-500 shadow-gomad">
            <p class="text-lg font-light">Belum ada kendaraan.</p>
            <a href="{{ route('agency.vehicles.create') }}" class="text-[#BA1826] hover:underline mt-2 inline-block font-medium">+ Tambah Kendaraan</a>
        </div>
        @endforelse
    </div>
</div>
@endsection