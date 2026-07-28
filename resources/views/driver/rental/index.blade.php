@extends('layouts.driver')

@section('title', 'Tugas Rental')
@section('content')

<div>
    <h1 class="text-2xl font-bold text-[#111111] mb-6">Tugas Rental Saya</h1>

    {{-- Rental Hari Ini --}}
    <div class="mb-8">
        <h2 class="font-bold text-lg text-[#111111] mb-4 border-b border-[#E5E5E5] pb-2">
            🚗 Hari Ini
        </h2>

        @if($todayRentals->isEmpty())
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-8 text-center shadow-sm">
            <div class="w-16 h-16 bg-[#C1121F]/5 rounded-[12px] flex items-center justify-center mx-auto mb-3 border border-[#E5E5E5]">
                <span class="text-2xl">🚗</span>
            </div>
            <p class="text-gray-500 font-light">Tidak ada tugas rental hari ini.</p>
        </div>
        @else
        <div class="space-y-3">
            @foreach($todayRentals as $rental)
            <a href="{{ route('driver.rental.show', $rental) }}" 
               class="block bg-white border border-[#E5E5E5] rounded-[12px] p-5 shadow-sm hover:border-[#C1121F] transition border-l-4
                   @if($rental->status == 'active') border-green-500
                   @else border-[#C1121F] @endif">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold font-mono text-[#111111]">{{ $rental->rental_code }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                @if($rental->status == 'active') bg-indigo-50 text-indigo-700 border-indigo-200
                                @else bg-blue-50 text-blue-700 border-blue-200 @endif">
                                {{ $rental->status_label }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1 font-light">
                            {{ $rental->vehicle->brand }} {{ $rental->vehicle->model }} — 
                            <span class="font-mono">{{ $rental->vehicle->plate_number }}</span>
                        </p>
                        <p class="text-sm text-gray-500 font-light">
                            👤 {{ $rental->customer->name ?? '-' }} | 📞 {{ $rental->customer->phone ?? '-' }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1 font-light">
                            📅 {{ $rental->start_datetime->format('d M Y H:i') }} — 
                            {{ $rental->end_datetime->format('d M Y H:i') }}
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <span class="text-xs text-gray-400 font-light">
                            {{ $rental->duration }} {{ $rental->duration_unit == 'hour' ? 'Jam' : 'Hari' }}
                        </span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Rental Mendatang --}}
    @if($upcomingRentals->isNotEmpty())
    <div class="mb-8">
        <h2 class="font-bold text-lg text-[#111111] mb-4 border-b border-[#E5E5E5] pb-2">
            📅 Mendatang
        </h2>
        <div class="space-y-3">
            @foreach($upcomingRentals as $rental)
            <a href="{{ route('driver.rental.show', $rental) }}" 
               class="block bg-white border border-[#E5E5E5] rounded-[12px] p-4 shadow-sm hover:border-[#C1121F] transition">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="font-semibold font-mono text-[#111111]">{{ $rental->rental_code }}</span>
                        <span class="text-sm text-gray-500 ml-2 font-light">
                            {{ $rental->vehicle->plate_number }} — {{ $rental->customer->name ?? '-' }}
                        </span>
                    </div>
                    <span class="text-xs text-gray-400 font-light">
                        {{ $rental->start_datetime->diffForHumans() }}
                    </span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Rental Selesai --}}
    @if($pastRentals->isNotEmpty())
    <div>
        <h2 class="font-bold text-lg text-gray-400 mb-4 border-b border-[#E5E5E5] pb-2">
            ✅ Selesai
        </h2>
        <div class="space-y-2 opacity-60">
            @foreach($pastRentals as $rental)
            <a href="{{ route('driver.rental.show', $rental) }}" 
               class="block bg-white border border-[#E5E5E5] rounded-[12px] p-3 shadow-sm">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="font-mono text-sm text-[#111111]">{{ $rental->rental_code }}</span>
                        <span class="text-xs text-gray-500 ml-2 font-light">
                            {{ $rental->vehicle->plate_number }} — {{ $rental->customer->name ?? '-' }}
                        </span>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider bg-green-50 text-green-700 border border-green-200">
                        {{ $rental->status_label }}
                    </span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection