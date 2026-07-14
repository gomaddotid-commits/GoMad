@extends('layouts.customer')

@section('title', 'Rental Saya')
@section('content')

<div class="container-magazine py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-[#111111]">Rental Saya</h1>
        <a href="{{ route('customer.rental.browse') }}" class="btn-gomad-primary text-sm inline-flex items-center gap-2 rounded-[12px]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Cari Mobil
        </a>
    </div>

    @if($rentals->isEmpty())
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-12 text-center shadow-sm">
        <div class="w-16 h-16 bg-[#F5F5F5] rounded-[12px] flex items-center justify-center mx-auto mb-4 border border-[#E5E5E5]">
            <span class="text-2xl">🚗</span>
        </div>
        <p class="text-gray-500 text-lg font-light mb-4">Belum ada rental.</p>
        <a href="{{ route('customer.rental.browse') }}" class="btn-gomad-primary inline-block">Cari Mobil Rental</a>
    </div>
    @else
    <div class="space-y-4">
        @foreach($rentals as $rental)
        @php
            $vehicle = $rental->vehicle;
            $setting = $vehicle->rentalSetting ?? null;
        @endphp
        <a href="{{ route('customer.rental.show', $rental) }}" 
           class="block bg-white border border-[#E5E5E5] rounded-[12px] p-5 shadow-sm hover:border-[#C1121F] transition-colors group">
            <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
                <div class="flex gap-4 flex-1">
                    {{-- Foto --}}
                    <div class="w-24 h-20 bg-[#F5F5F5] rounded-[12px] overflow-hidden flex-shrink-0 border border-[#E5E5E5]">
                        @if($vehicle->vehicle_image)
                        <img src="{{ $vehicle->vehicle_image }}" class="w-full h-full object-cover">
                        @else
                        <div class="w-full h-full flex items-center justify-center text-2xl">🚗</div>
                        @endif
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="font-bold text-[#111111] font-mono">{{ $rental->rental_code }}</h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                @if($rental->status == 'active') bg-indigo-50 text-indigo-700 border-indigo-200
                                @elseif($rental->status == 'paid') bg-blue-50 text-blue-700 border-blue-200
                                @elseif($rental->status == 'completed') bg-green-50 text-green-700 border-green-200
                                @elseif($rental->status == 'cancelled') bg-red-50 text-red-700 border-red-200
                                @else bg-yellow-50 text-yellow-700 border-yellow-200 @endif">
                                {{ $rental->status_label }}
                            </span>
                        </div>
                        <p class="font-semibold text-[#111111] text-sm">{{ $vehicle->brand }} {{ $vehicle->model }}</p>
                        <p class="text-xs text-gray-500 font-mono">{{ $vehicle->plate_number }}</p>
                        <p class="text-xs text-gray-500 mt-1 font-light">
                            📅 {{ $rental->start_datetime->format('d M Y H:i') }} - {{ $rental->end_datetime->format('d M Y H:i') }}
                        </p>
                        <p class="text-xs text-gray-500 font-light">
                            🕐 {{ $rental->duration }} {{ $rental->duration_unit == 'hour' ? 'Jam' : 'Hari' }} 
                            | {{ $rental->type == 'self_drive' ? '🚗 Lepas Kunci' : '👨‍✈️ Dengan Supir' }}
                        </p>
                    </div>
                </div>
                
                <div class="text-right flex-shrink-0">
                    <p class="text-xl font-bold text-[#C1121F] font-mono">Rp {{ number_format($rental->total_price, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400 font-light">{{ $rental->agency->agency_name }}</p>
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @endif
</div>
@endsection