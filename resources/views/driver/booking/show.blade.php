@extends('layouts.driver')

@section('title', 'Detail Penumpang')
@section('content')
<div class="max-w-2xl mx-auto px-4 py-6">
    <a href="{{ route('driver.schedule') }}" class="text-primary text-sm mb-4 inline-block">← Kembali ke Jadwal</a>

    @if(isset($schedule))
    <h1 class="text-xl font-bold text-gray-900 mb-2">{{ $schedule->route->route_name ?? 'Rute' }}</h1>
    <p class="text-gray-600 mb-4">
        {{ $schedule->departure_date->format('d M Y') }} | 
        {{ $schedule->departure_time }} | 
        🚐 {{ $schedule->vehicle->plate_number ?? '-' }}
    </p>

    {{-- Status Jadwal --}}
    @php
        $allBookings = $schedule->bookings;
        $totalBookings = $allBookings->count();
        $totalPassengers = $allBookings->sum('total_passengers');
        $pickedUpBookings = $allBookings->filter(function($b) {
            return $b->passengers->every(fn($p) => $p->picked_up_at !== null);
        })->count();
        $droppedOffBookings = $allBookings->filter(function($b) {
            return $b->passengers->every(fn($p) => $p->dropped_off_at !== null);
        })->count();
    @endphp
    
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-blue-50 rounded-lg p-3 text-center">
            <p class="text-xs text-gray-500">Total Booking</p>
            <p class="text-xl font-bold">{{ $totalBookings }}</p>
            <p class="text-xs text-gray-500">{{ $totalPassengers }} penumpang</p>
        </div>
        <div class="bg-yellow-50 rounded-lg p-3 text-center">
            <p class="text-xs text-gray-500">Sudah Dijemput</p>
            <p class="text-xl font-bold">{{ $pickedUpBookings }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-3 text-center">
            <p class="text-xs text-gray-500">Sudah Turun</p>
            <p class="text-xl font-bold">{{ $droppedOffBookings }}</p>
        </div>
    </div>

    {{-- Progress Bar --}}
    @if($totalBookings > 0)
    <div class="bg-gray-200 rounded-full h-3 mb-6 overflow-hidden">
        <div class="bg-green-500 h-full rounded-full transition-all" style="width: {{ ($droppedOffBookings / $totalBookings) * 100 }}%"></div>
    </div>
    @endif

    @if($allBookings->isEmpty())
    <div class="bg-white rounded-xl shadow p-8 text-center text-gray-500">
        Belum ada penumpang.
    </div>
    @else
    <div class="space-y-4">
        @foreach($allBookings as $booking)
        @php
            // Cek status booking
            $allPassengers = $booking->passengers;
            $allPickedUp = $allPassengers->every(fn($p) => $p->picked_up_at !== null);
            $allDroppedOff = $allPassengers->every(fn($p) => $p->dropped_off_at !== null);
            $anyPickedUp = $allPassengers->some(fn($p) => $p->picked_up_at !== null);
        @endphp

        <div class="bg-white rounded-xl shadow p-4 border-l-4 
            @if($allDroppedOff) border-green-500
            @elseif($anyPickedUp) border-blue-500
            @else border-yellow-500 @endif">
            
            {{-- Header Booking --}}
            <div class="flex justify-between items-start mb-3 pb-3 border-b">
                <div>
                    <span class="font-bold">{{ $booking->booking_code }}</span>
                    <span class="text-sm text-gray-500 ml-2">
                        {{ $booking->originStop->city_name ?? '?' }} → {{ $booking->destinationStop->city_name ?? '?' }}
                    </span>
                </div>
                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                    @if($allDroppedOff) bg-green-100 text-green-800
                    @elseif($anyPickedUp) bg-blue-100 text-blue-800
                    @else bg-yellow-100 text-yellow-800 @endif">
                    @if($allDroppedOff) ✅ Selesai
                    @elseif($anyPickedUp) 🚗 Dalam Perjalanan
                    @else ⏳ Menunggu
                    @endif
                </span>
            </div>
            
            {{-- Alamat --}}
            <div class="grid grid-cols-2 gap-3 mb-3 text-sm">
                <div class="bg-gray-50 rounded p-2">
                    <span class="text-xs text-gray-500">📍 Jemput</span>
                    <p class="font-medium text-xs">{{ $booking->pickup_address }}</p>
                </div>
                <div class="bg-gray-50 rounded p-2">
                    <span class="text-xs text-gray-500">🎯 Tujuan</span>
                    <p class="font-medium text-xs">{{ $booking->destination_address }}</p>
                </div>
            </div>

            {{-- List Penumpang --}}
            <div class="bg-gray-50 rounded-lg p-3 mb-3">
                <p class="text-xs text-gray-500 mb-2 font-medium">👥 Penumpang ({{ $booking->total_passengers }} orang)</p>
                <div class="space-y-1">
                    @foreach($booking->passengers as $p)
                    <div class="flex justify-between text-sm">
                        <span>{{ $p->passenger_name }} (Seat {{ $p->seat_number }})</span>
                        <span class="text-gray-500 text-xs">{{ $p->passenger_phone ?? '-' }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- TOMBOL AKSI PER BOOKING --}}
            <div class="flex gap-2">
                @if(!$anyPickedUp)
                {{-- Belum ada yang dijemput → Tombol Jemput --}}
                <form action="{{ route('driver.passenger.pickup', $booking->passengers->first()) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full bg-green-500 text-white py-2 rounded-lg text-sm font-medium hover:bg-green-600 transition">
                        ✅ JEMPUT ({{ $booking->total_passengers }} orang)
                    </button>
                </form>

                @elseif($anyPickedUp && !$allDroppedOff)
                {{-- Sudah dijemput, belum turun → Tombol Turunkan --}}
                <form action="{{ route('driver.passenger.dropoff', $booking->passengers->first()) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition">
                        🚗 TURUNKAN ({{ $booking->total_passengers }} orang)
                    </button>
                </form>
                @endif

                @if($allDroppedOff)
                {{-- Sudah selesai --}}
                <div class="flex-1 bg-green-50 text-green-700 py-2 rounded-lg text-sm font-medium text-center">
                    ✅ Selesai
                </div>
                @endif

                {{-- Tombol navigasi ke Maps --}}
                @if($booking->pickup_maps_link && !$allPickedUp)
                <a href="{{ $booking->pickup_maps_link }}" target="_blank" 
                   class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">
                    🗺️ Maps
                </a>
                @endif
                @if($booking->destination_maps_link && $allPickedUp && !$allDroppedOff)
                <a href="{{ $booking->destination_maps_link }}" target="_blank" 
                   class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">
                    🗺️ Maps
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
    @endif
</div>
@endsection