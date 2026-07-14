@extends('layouts.agency')

@section('title', 'Kendaraan Rental')
@section('content')

<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 border-b border-[#E5E5E5] pb-3">
        <div>
            <h1 class="text-2xl font-bold text-[#111111]">Kendaraan Rental</h1>
            <p class="text-sm text-gray-500 font-light mt-1">Atur kendaraan yang tersedia untuk disewakan</p>
        </div>
        <a href="{{ route('agency.rental.dashboard') }}" class="text-[#C1121F] text-sm hover:underline font-medium">
            ← Dashboard Rental
        </a>
    </div>

    @if($vehicles->isEmpty())
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-12 text-center shadow-sm">
        <div class="w-16 h-16 bg-[#C1121F]/5 rounded-[12px] flex items-center justify-center mx-auto mb-4 border border-[#E5E5E5]">
            <span class="text-2xl">🚗</span>
        </div>
        <p class="text-gray-500 text-lg font-light mb-4">Belum ada kendaraan.</p>
        <a href="{{ route('agency.vehicles.create') }}" class="btn-gomad-primary inline-block">Tambah Kendaraan</a>
    </div>
    @else
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($vehicles as $vehicle)
        @php
            $setting = $vehicle->rentalSetting;
            $isSetup = $setting && $setting->is_available_for_rental;
        @endphp
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] overflow-hidden shadow-sm hover:border-[#C1121F] transition-colors">
            {{-- Foto --}}
            <div class="h-40 bg-[#F5F5F5] flex items-center justify-center overflow-hidden border-b border-[#E5E5E5]">
                @if($vehicle->vehicle_image)
                <img src="{{ $vehicle->vehicle_image }}" class="w-full h-full object-cover">
                @else
                <span class="text-5xl text-gray-300">🚐</span>
                @endif
            </div>
            
            <div class="p-5">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h3 class="font-bold text-[#111111] font-mono">{{ $vehicle->plate_number }}</h3>
                        <p class="text-sm text-gray-500 font-light">{{ $vehicle->brand }} {{ $vehicle->model }} ({{ $vehicle->year }})</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                        {{ $isSetup ? 'bg-green-50 text-green-700 border-green-200' : 'bg-[#F5F5F5] text-gray-500 border-[#E5E5E5]' }}">
                        {{ $isSetup ? 'Aktif' : 'Belum Setup' }}
                    </span>
                </div>
                
                <div class="flex items-center gap-3 text-xs text-gray-500 font-mono uppercase tracking-wider mb-3">
                    <span>{{ $vehicle->capacity }} seat</span>
                    <span>|</span>
                    <span class="capitalize">{{ $vehicle->type }}</span>
                </div>

                {{-- Info Rental (jika sudah setup) --}}
                @if($isSetup)
                <div class="bg-[#F5F5F5] rounded-[12px] p-3 mb-3 border border-[#E5E5E5] space-y-1 text-xs">
                    @if($setting->price_per_day)
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-light">Harian</span>
                        <span class="font-semibold text-[#C1121F]">Rp {{ number_format($setting->price_per_day, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    @if($setting->price_per_hour)
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-light">Per Jam</span>
                        <span class="font-semibold text-[#C1121F]">Rp {{ number_format($setting->price_per_hour, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    
                    <div class="flex gap-1 mt-1">
                        @if($setting->allow_self_drive)
                        <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 text-[10px] rounded-full font-mono border border-blue-200">Lepas Kunci</span>
                        @endif
                        @if($setting->allow_with_driver)
                        <span class="px-1.5 py-0.5 bg-green-50 text-green-700 text-[10px] rounded-full font-mono border border-green-200">+Supir</span>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Tombol Aksi --}}
                <div class="flex gap-2 border-t border-[#E5E5E5] pt-4">
                    <a href="{{ route('agency.rental.vehicle-setup', $vehicle) }}" 
                       class="flex-1 text-center {{ $isSetup ? 'border border-[#E5E5E5] text-[#111111]' : 'bg-[#C1121F] text-white' }} py-2 rounded-[12px] text-sm font-medium hover:opacity-90 transition">
                        {{ $isSetup ? 'Edit Setup' : 'Setup Rental' }}
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection