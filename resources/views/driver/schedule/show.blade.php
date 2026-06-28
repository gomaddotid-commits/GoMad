@extends('layouts.driver')

@section('title', 'Detail Jadwal')
@section('content')
<!-- File: resources/views/driver/booking/show.blade.php -->
<!-- Deskripsi: Halaman detail jadwal & penumpang driver -->

<div class="max-w-2xl mx-auto">
    @php
        $schedule = \App\Models\Schedule::with(['route.stops', 'vehicle', 'bookings' => function($q) {
            $q->whereNotIn('status', ['cancelled'])->with(['originStop', 'destinationStop', 'passengers', 'customer']);
        }])->findOrFail(request()->route('schedule'));
    @endphp

    <a href="{{ route('driver.schedule') }}" class="text-primary text-sm mb-4 inline-block">← Kembali</a>
    <h1 class="text-xl font-bold text-gray-900 mb-2">{{ $schedule->route->route_name }}</h1>
    <p class="text-gray-600 mb-4">{{ $schedule->departure_date->format('d M Y') }} | {{ $schedule->departure_time }} | {{ $schedule->vehicle->plate_number }}</p>

    <h2 class="font-bold text-lg mb-3">Daftar Penumpang ({{ $schedule->bookings->sum('total_passengers') }})</h2>

    @if($schedule->bookings->isEmpty())
    <p class="text-gray-500 text-center py-8">Belum ada penumpang.</p>
    @else
    <div class="space-y-4">
        @foreach($schedule->bookings as $booking)
        <div class="bg-white rounded-xl shadow p-4">
            <div class="flex justify-between mb-2">
                <span class="font-semibold">{{ $booking->booking_code }}</span>
                <span class="text-sm text-gray-500">{{ $booking->originStop->city_name }} → {{ $booking->destinationStop->city_name }}</span>
            </div>
            <p class="text-sm text-gray-500 mb-2">Jemput: {{ $booking->pickup_address }}</p>
            @foreach($booking->passengers as $p)
            <div class="flex justify-between items-center py-2 border-b text-sm">
                <div>
                    <span class="font-medium">{{ $p->passenger_name }}</span>
                    <span class="text-gray-400 ml-2">Seat {{ $p->seat_number }}</span>
                </div>
                <div class="flex gap-2">
                    @if(!$p->picked_up_at)
                    <form action="{{ route('driver.passenger.pickup', $p) }}" method="POST">
                        @csrf
                        <button class="bg-green-500 text-white px-3 py-1 rounded text-xs">Jemput</button>
                    </form>
                    @elseif(!$p->dropped_off_at)
                    <form action="{{ route('driver.passenger.dropoff', $p) }}" method="POST">
                        @csrf
                        <button class="bg-blue-500 text-white px-3 py-1 rounded text-xs">Turunkan</button>
                    </form>
                    @else
                    <span class="text-green-600 text-xs">✅ Selesai</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection