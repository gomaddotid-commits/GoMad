@extends('layouts.driver')

@section('title', 'Jadwal Saya')
@section('content')
@php
    $driverService = app(\App\Services\DriverService::class);
    $todaySchedule = $driverService->getDriverTodaySchedule(auth()->user());
    $upcomingSchedules = $driverService->getDriverUpcomingSchedules(auth()->user(), 7);
@endphp

<div>
    <h1 class="text-2xl font-bold text-secondary mb-6">Jadwal Saya</h1>

    {{-- Jadwal Hari Ini --}}
    <div class="mb-8">
        <h2 class="font-bold text-lg text-secondary mb-4">Hari Ini</h2>
        
        @if($todaySchedule)
        <a href="{{ route('driver.schedule.show', $todaySchedule) }}" class="block bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition border-l-4 border-primary-600">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                <div>
                    <h3 class="font-bold text-xl text-secondary">{{ $todaySchedule->route->route_name }}</h3>
                    <div class="flex items-center gap-3 text-sm text-gray-500 mt-2">
                        <span>{{ $todaySchedule->departure_time }}</span>
                        <span>|</span>
                        <span>{{ $todaySchedule->vehicle->plate_number ?? '-' }}</span>
                        <span>|</span>
                        <span>{{ $todaySchedule->vehicle->brand ?? '' }} {{ $todaySchedule->vehicle->model ?? '' }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $todaySchedule->route->origin_city }} → {{ $todaySchedule->route->destination_city }}
                    </p>
                </div>
                <div class="text-right flex-shrink-0">
                    @if(!$todaySchedule->started_at)
                    <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium">Menunggu Agency</span>
                    @elseif(!$todaySchedule->finished_at)
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">Dalam Perjalanan</span>
                    @else
                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">Selesai</span>
                    @endif
                    
                    @php
                        $totalPassengers = $todaySchedule->bookings->sum('total_passengers');
                        $completedBookings = $todaySchedule->bookings->where('status', 'completed')->count();
                        $totalBookings = $todaySchedule->bookings->count();
                    @endphp
                    <p class="text-sm text-gray-500 mt-2">{{ $totalPassengers }} penumpang</p>
                    @if($totalBookings > 0)
                    <p class="text-xs text-gray-400">{{ $completedBookings }}/{{ $totalBookings }} selesai</p>
                    @endif
                </div>
            </div>
            
            @if($totalBookings > 0)
            <div class="mt-4 bg-gray-100 rounded-full h-2 overflow-hidden">
                <div class="bg-green-500 h-full rounded-full transition-all" style="width: {{ ($completedBookings / $totalBookings) * 100 }}%"></div>
            </div>
            @endif
        </a>
        @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
            <div class="w-16 h-16 bg-primary-50 rounded-xl flex items-center justify-center mx-auto mb-3">
                <span class="text-2xl">📅</span>
            </div>
            <p class="text-gray-500">Tidak ada jadwal hari ini.</p>
        </div>
        @endif
    </div>

    {{-- Jadwal Mendatang --}}
    <div>
        <h2 class="font-bold text-lg text-secondary mb-4">Jadwal Mendatang</h2>
        
        @if($upcomingSchedules->isNotEmpty())
        <div class="space-y-3">
            @foreach($upcomingSchedules as $schedule)
            <a href="{{ route('driver.schedule.show', $schedule) }}" class="block bg-white rounded-2xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="font-semibold text-secondary">{{ $schedule->route->route_name }}</h3>
                        <p class="text-sm text-gray-500">{{ $schedule->departure_date->format('d M Y') }} {{ $schedule->departure_time }}</p>
                        <p class="text-xs text-gray-500">{{ $schedule->vehicle->plate_number ?? '-' }}</p>
                    </div>
                    <span class="text-xs text-gray-400">{{ $schedule->departure_date->diffForHumans() }}</span>
                </div>
            </a>
            @endforeach
        </div>
        @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
            <p class="text-gray-500">Tidak ada jadwal mendatang.</p>
        </div>
        @endif
    </div>
</div>
@endsection