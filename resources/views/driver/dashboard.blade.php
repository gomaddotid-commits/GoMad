@extends('layouts.driver')

@section('title', 'Dashboard')
@section('content')

@php
    $driver = auth()->user();
    $today = \Carbon\Carbon::today();
@endphp

<div>
    {{-- Welcome Banner --}}
    <div class="bg-gradient-to-r from-[#BA1826] to-[#8A0F18] rounded-[12px] p-6 mb-8 text-white shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Halo, {{ $driver->name }}! 👨‍✈️</h1>
                <p class="text-white/80 text-sm mt-1 font-light">
                    {{ $today->translatedFormat('l, d M Y') }}
                </p>
            </div>
            <div class="hidden md:block text-right">
                <p class="text-sm text-white/70 font-light">Total Perjalanan</p>
                <p class="text-3xl font-bold">{{ ($totalTrips ?? 0) + ($totalRentals ?? 0) }}</p>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════ --}}
    {{-- HIGHLIGHT: SEDANG BERLANGSUNG --}}
    {{-- ═══════════════════════════════════════ --}}

    {{-- Travel Sedang Berlangsung --}}
    @if($todaySchedule && $todaySchedule->started_at && !$todaySchedule->finished_at)
    <div class="mb-6">
        <h2 class="font-bold text-lg text-[#111111] mb-3 flex items-center gap-2">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            🚐 Perjalanan Sedang Berlangsung
        </h2>
        <a href="{{ route('driver.travel.show', $todaySchedule) }}" 
           class="block bg-white border-2 border-green-300 rounded-[12px] p-5 shadow-sm hover:shadow-md transition">
            <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-3">
                <div>
                    <h3 class="font-bold text-xl text-[#111111]">{{ $todaySchedule->route->route_name }}</h3>
                    <div class="flex items-center gap-3 text-sm text-gray-500 mt-1 font-light">
                        <span class="font-mono">{{ $todaySchedule->departure_time }}</span>
                        <span>|</span>
                        <span class="font-mono">{{ $todaySchedule->vehicle->plate_number }}</span>
                        <span>|</span>
                        <span>{{ $todaySchedule->vehicle->brand }} {{ $todaySchedule->vehicle->model }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1 font-light">
                        🏢 {{ $todaySchedule->agency->agency_name ?? '-' }}
                    </p>
                </div>
                <div class="text-right flex-shrink-0">
                    @php
                        $totalBookings = $todaySchedule->bookings->where('status', '!=', 'cancelled')->count();
                        $completedBookings = $todaySchedule->bookings->where('status', 'completed')->count();
                        $totalPax = $todaySchedule->bookings->where('status', '!=', 'cancelled')->sum('total_passengers');
                    @endphp
                    <p class="text-sm text-gray-500 font-light">{{ $totalPax }} penumpang</p>
                    <p class="text-xs text-gray-400 font-mono">{{ $completedBookings }}/{{ $totalBookings }} selesai</p>
                    @if($totalBookings > 0)
                    <div class="mt-2 bg-[#E5E5E5] rounded-full h-2 w-32 overflow-hidden">
                        <div class="bg-green-500 h-full rounded-full" style="width: {{ ($completedBookings / $totalBookings) * 100 }}%"></div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="mt-3 text-[#BA1826] text-sm font-medium">Lihat Penumpang →</div>
        </a>
    </div>
    @endif

    {{-- Rental Sedang Berlangsung --}}
    @if($activeRental && $activeRental->status == 'active')
    <div class="mb-6">
        <h2 class="font-bold text-lg text-[#111111] mb-3 flex items-center gap-2">
            <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
            🚗 Rental Sedang Berlangsung
        </h2>
        <a href="{{ route('driver.rentals.show', $activeRental) }}" 
           class="block bg-white border-2 border-indigo-300 rounded-[12px] p-5 shadow-sm hover:shadow-md transition">
            <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-xl font-mono text-[#111111]">{{ $activeRental->rental_code }}</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                            {{ $activeRental->status_label }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1 font-light">
                        🚗 {{ $activeRental->vehicle->brand }} {{ $activeRental->vehicle->model }} — 
                        <span class="font-mono">{{ $activeRental->vehicle->plate_number }}</span>
                    </p>
                    <p class="text-sm text-gray-500 font-light">
                        👤 {{ $activeRental->customer->name ?? '-' }} | 📞 {{ $activeRental->customer->phone ?? '-' }}
                    </p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-xs text-gray-400 font-light">
                        s/d {{ $activeRental->end_datetime->format('d M H:i') }}
                    </p>
                </div>
            </div>
            <div class="mt-3 text-[#BA1826] text-sm font-medium">Lihat Detail →</div>
        </a>
    </div>
    @endif

    {{-- Travel Menunggu --}}
    @if($todaySchedule && !$todaySchedule->started_at)
    <div class="mb-6">
        <h2 class="font-bold text-lg text-[#111111] mb-3 flex items-center gap-2">
            <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
            🚐 Jadwal Hari Ini (Menunggu Agency)
        </h2>
        <div class="bg-white border-2 border-yellow-300 rounded-[12px] p-5 shadow-sm">
            <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-3">
                <div>
                    <h3 class="font-bold text-xl text-[#111111]">{{ $todaySchedule->route->route_name }}</h3>
                    <p class="text-sm text-gray-500 mt-1 font-light">
                        🕐 {{ $todaySchedule->departure_time }} | 
                        🚐 {{ $todaySchedule->vehicle->plate_number }}
                    </p>
                    <p class="text-sm text-gray-500 font-light">
                        🏢 {{ $todaySchedule->agency->agency_name ?? '-' }}
                    </p>
                </div>
                <span class="px-3 py-1 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-full text-[10px] font-mono uppercase tracking-wider">
                    ⏳ Menunggu Agency
                </span>
            </div>
        </div>
    </div>
    @endif

    {{-- Rental Menunggu --}}
    @if($activeRental && $activeRental->status == 'paid')
    <div class="mb-6">
        <h2 class="font-bold text-lg text-[#111111] mb-3 flex items-center gap-2">
            <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
            🚗 Tugas Rental Hari Ini
        </h2>
        <a href="{{ route('driver.rentals.show', $activeRental) }}" 
           class="block bg-white border-2 border-blue-300 rounded-[12px] p-5 shadow-sm hover:shadow-md transition">
            <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-xl font-mono text-[#111111]">{{ $activeRental->rental_code }}</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200">
                            {{ $activeRental->status_label }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1 font-light">
                        🚗 {{ $activeRental->vehicle->brand }} {{ $activeRental->vehicle->model }} — 
                        <span class="font-mono">{{ $activeRental->vehicle->plate_number }}</span>
                    </p>
                    <p class="text-sm text-gray-500 font-light">
                        👤 {{ $activeRental->customer->name ?? '-' }} | 📞 {{ $activeRental->customer->phone ?? '-' }}
                    </p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-xs text-gray-400 font-light">
                        📅 {{ $activeRental->start_datetime->format('d M H:i') }}
                    </p>
                </div>
            </div>
            <div class="mt-3 text-[#BA1826] text-sm font-medium">Lihat Detail →</div>
        </a>
    </div>
    @endif

    {{-- Tidak Ada Tugas --}}
    @if(!$todaySchedule && !$activeRental)
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-8 text-center mb-6 shadow-sm">
        <div class="w-16 h-16 bg-[#BA1826]/5 rounded-[12px] flex items-center justify-center mx-auto mb-3 border border-[#E5E5E5]">
            <span class="text-2xl">☕</span>
        </div>
        <p class="text-gray-500 text-lg font-light">Tidak ada tugas hari ini.</p>
        <p class="text-sm text-gray-400 mt-1 font-light">Santai dulu, mungkin nanti ada penugasan.</p>
        <a href="{{ route('driver.assignments') }}" class="inline-block mt-3 text-[#BA1826] text-sm font-medium hover:underline">
            Lihat Penugasan Mendatang →
        </a>
    </div>
    @endif

    {{-- ═══════════════════════════════════════ --}}
    {{-- REKAP PERJALANAN --}}
    {{-- ═══════════════════════════════════════ --}}
    <div>
        <h2 class="font-bold text-lg text-[#111111] mb-3">📊 Rekap Perjalanan</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 text-center shadow-sm">
                <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Travel Selesai</p>
                <p class="text-2xl font-bold text-[#111111] mt-1">{{ $completedTrips ?? 0 }}</p>
                <p class="text-[10px] text-gray-400 font-light">dari {{ $totalTrips ?? 0 }}</p>
            </div>
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 text-center shadow-sm">
                <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Rental Selesai</p>
                <p class="text-2xl font-bold text-[#111111] mt-1">{{ $completedRentals ?? 0 }}</p>
                <p class="text-[10px] text-gray-400 font-light">dari {{ $totalRentals ?? 0 }}</p>
            </div>
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 text-center shadow-sm">
                <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Total Penumpang</p>
                <p class="text-2xl font-bold text-[#111111] mt-1">{{ $totalPassengers ?? 0 }}</p>
                <p class="text-[10px] text-gray-400 font-light">orang</p>
            </div>
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 text-center shadow-sm">
                <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Rating</p>
                <p class="text-2xl font-bold text-[#111111] mt-1">
                    ⭐ {{ number_format($averageRating ?? 0, 1) }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection