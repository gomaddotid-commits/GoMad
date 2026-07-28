@extends('layouts.driver')

@section('title', 'Penugasan')
@section('content')

<div x-data="{ activeTab: 'travel' }">
    <h1 class="text-2xl font-bold text-[#111111] mb-6">📋 Penugasan</h1>

    {{-- Tab Header --}}
    <div class="flex items-center gap-2 mb-6 border-b border-[#E5E5E5] pb-3">
        <button @click="activeTab = 'travel'" 
                :class="activeTab === 'travel' ? 'border-b-2 border-[#BA1826] text-[#BA1826]' : 'text-gray-500 hover:text-[#111827]'"
                class="px-4 py-2 text-sm font-semibold transition">
            🚐 Travel
        </button>
        <button @click="activeTab = 'rental'" 
                :class="activeTab === 'rental' ? 'border-b-2 border-[#BA1826] text-[#BA1826]' : 'text-gray-500 hover:text-[#111827]'"
                class="px-4 py-2 text-sm font-semibold transition">
            🚗 Rental
        </button>
    </div>

    {{-- ======================== --}}
    {{-- TAB: TRAVEL --}}
    {{-- ======================== --}}
    <div x-show="activeTab === 'travel'">
        
        {{-- Jadwal Mendatang --}}
        <h2 class="font-bold text-lg text-[#111111] mb-4">📅 Jadwal Mendatang</h2>
        @if(($upcomingSchedules ?? collect())->isNotEmpty())
        <div class="space-y-3 mb-8">
            @foreach($upcomingSchedules as $schedule)
            <a href="{{ route('driver.travel.show', $schedule) }}" 
               class="block bg-white border border-[#E5E5E5] rounded-[12px] p-4 shadow-sm hover:border-[#BA1826] transition">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <div>
                        <h3 class="font-bold text-[#111111]">{{ $schedule->route->route_name }}</h3>
                        <p class="text-sm text-gray-500 font-light mt-1">
                            📅 {{ $schedule->departure_date->format('d M Y') }} | 
                            🕐 {{ $schedule->departure_time }}
                        </p>
                        <p class="text-xs text-gray-400 font-mono mt-1">
                            🚐 {{ $schedule->vehicle->plate_number ?? '-' }} — 
                            {{ $schedule->vehicle->brand ?? '' }} {{ $schedule->vehicle->model ?? '' }}
                        </p>
                        @if($schedule->driver)
                        <p class="text-xs text-gray-400 font-mono mt-0.5">
                            👨‍✈️ {{ $schedule->driver->name }}
                        </p>
                        @endif
                    </div>
                    <div class="text-right flex-shrink-0">
                        <span class="text-xs text-gray-400 font-light">{{ $schedule->departure_date->diffForHumans() }}</span>
                        <p class="text-xs mt-1 {{ ($schedule->available_seats ?? 0) > 0 ? 'text-green-600' : 'text-red-600' }} font-mono">
                            💺 {{ $schedule->available_seats ?? 0 }} kursi
                        </p>
                        <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $schedule->travel_class_label ?? ucfirst($schedule->travel_class) }}</p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @else
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-8 text-center mb-8 shadow-sm">
            <p class="text-gray-500 font-light">Tidak ada jadwal mendatang.</p>
        </div>
        @endif

        {{-- Travel Selesai --}}
        @if(($recentCompletedSchedules ?? collect())->isNotEmpty())
        <h2 class="font-bold text-lg text-[#111111] mb-4">✅ Riwayat Travel Selesai</h2>
        <div class="space-y-2 opacity-70">
            @foreach($recentCompletedSchedules as $schedule)
            <a href="{{ route('driver.travel.show', $schedule) }}" 
               class="block bg-white border border-[#E5E5E5] rounded-[12px] p-3 shadow-sm">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="font-medium text-sm text-[#111111]">{{ $schedule->route->route_name }}</span>
                        <span class="text-xs text-gray-500 ml-2 font-light">
                            {{ $schedule->departure_date->format('d M Y') }} {{ $schedule->departure_time }}
                        </span>
                        <span class="text-xs text-gray-400 ml-2 font-mono">{{ $schedule->vehicle->plate_number ?? '-' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($schedule->finished_at)
                        <span class="text-[10px] text-gray-400 font-light">{{ $schedule->finished_at->format('d M H:i') }}</span>
                        @endif
                        <span class="text-[10px] font-mono uppercase tracking-wider text-green-600">Selesai</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ======================== --}}
    {{-- TAB: RENTAL --}}
    {{-- ======================== --}}
    <div x-show="activeTab === 'rental'" x-cloak>

        {{-- Rental Aktif --}}
        @if(($activeRentals ?? collect())->isNotEmpty())
        <h2 class="font-bold text-lg text-[#111111] mb-4">🏃 Rental Sedang Berlangsung</h2>
        <div class="space-y-3 mb-8">
            @foreach($activeRentals as $rental)
            <a href="{{ route('driver.rentals.show', $rental) }}" 
               class="block bg-white border-2 border-indigo-300 rounded-[12px] p-4 shadow-sm hover:shadow-md transition">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-bold font-mono text-[#111111]">{{ $rental->rental_code }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                                {{ $rental->status_label }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 font-light mt-1">
                            🚗 {{ $rental->vehicle->brand }} {{ $rental->vehicle->model }} — 
                            <span class="font-mono">{{ $rental->vehicle->plate_number }}</span>
                        </p>
                        <p class="text-xs text-gray-400 font-light mt-1">
                            👤 {{ $rental->customer->name ?? '-' }} | 📞 {{ $rental->customer->phone ?? '-' }}
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-xs text-gray-400 font-light">
                            s/d {{ $rental->end_datetime->format('d M H:i') }}
                        </p>
                        <p class="text-xs text-gray-400 font-light">
                            {{ $rental->duration }} {{ $rental->duration_unit == 'hour' ? 'Jam' : 'Hari' }}
                        </p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @endif

        {{-- Rental Mendatang --}}
        <h2 class="font-bold text-lg text-[#111111] mb-4">📅 Tugas Rental Mendatang</h2>
        @if(($upcomingRentals ?? collect())->isNotEmpty())
        <div class="space-y-3 mb-8">
            @foreach($upcomingRentals as $rental)
            <a href="{{ route('driver.rentals.show', $rental) }}" 
               class="block bg-white border border-[#E5E5E5] rounded-[12px] p-4 shadow-sm hover:border-[#BA1826] transition">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <div>
                        <h3 class="font-bold font-mono text-[#111111]">{{ $rental->rental_code }}</h3>
                        <p class="text-sm text-gray-500 font-light mt-1">
                            🚗 {{ $rental->vehicle->brand }} {{ $rental->vehicle->model }} — 
                            <span class="font-mono">{{ $rental->vehicle->plate_number }}</span>
                        </p>
                        <p class="text-xs text-gray-400 font-light mt-1">
                            👤 {{ $rental->customer->name ?? '-' }} | 📞 {{ $rental->customer->phone ?? '-' }}
                        </p>
                        <p class="text-xs text-gray-400 font-light mt-0.5">
                            🏢 {{ $rental->agency->agency_name ?? '-' }}
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <span class="text-xs text-gray-400 font-light">{{ $rental->start_datetime->diffForHumans() }}</span>
                        <p class="text-xs text-gray-400 font-light mt-1">
                            📅 {{ $rental->start_datetime->format('d M H:i') }}
                        </p>
                        <p class="text-xs text-gray-400 font-light">
                            ⏱️ {{ $rental->duration }} {{ $rental->duration_unit == 'hour' ? 'Jam' : 'Hari' }}
                        </p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @else
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-8 text-center mb-8 shadow-sm">
            <p class="text-gray-500 font-light">Tidak ada tugas rental mendatang.</p>
        </div>
        @endif

        {{-- Rental Selesai --}}
        @if(($recentCompletedRentals ?? collect())->isNotEmpty())
        <h2 class="font-bold text-lg text-[#111111] mb-4">✅ Riwayat Rental Selesai</h2>
        <div class="space-y-2 opacity-70">
            @foreach($recentCompletedRentals as $rental)
            <a href="{{ route('driver.rentals.show', $rental) }}" 
               class="block bg-white border border-[#E5E5E5] rounded-[12px] p-3 shadow-sm">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="font-medium text-sm font-mono text-[#111111]">{{ $rental->rental_code }}</span>
                        <span class="text-xs text-gray-500 ml-2 font-light">
                            {{ $rental->vehicle->plate_number }} — {{ $rental->customer->name ?? '-' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] text-gray-400 font-light">{{ $rental->start_datetime->format('d M') }}</span>
                        <span class="text-[10px] font-mono uppercase tracking-wider text-green-600">Selesai</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection