@extends('layouts.driver')

@section('title', 'Detail Penumpang')
@section('content')
<div class="max-w-2xl mx-auto">
    <a href="{{ route('driver.schedule') }}" class="text-[#C1121F] text-sm mb-4 inline-block hover:underline">← Kembali ke Jadwal</a>

    @if(isset($schedule))
    <h1 class="text-xl font-bold text-[#111111] mb-2">{{ $schedule->route->route_name ?? 'Rute' }}</h1>
    <p class="text-gray-600 font-light mb-4">
        {{ $schedule->departure_date->format('d M Y') }} |
        {{ $schedule->departure_time }} |
        🚐 {{ $schedule->vehicle->plate_number ?? '-' }}
    </p>

    {{-- ═══════════════════════════════════════ --}}
    {{-- STATUS SCHEDULE --}}
    {{-- ═══════════════════════════════════════ --}}
    @php
        $isToday = $schedule->departure_date->isToday();
        $isFuture = $schedule->departure_date->isFuture();
        $isPast = $schedule->departure_date->isPast();
        $isStarted = !is_null($schedule->started_at);
        $isFinished = !is_null($schedule->finished_at);
    @endphp

    <div class="rounded-[12px] p-4 mb-6 text-center border
        @if($isToday && $isFinished) bg-green-50 border-green-200
        @elseif($isToday && $isStarted) bg-blue-50 border-blue-200
        @elseif($isToday && !$isStarted) bg-yellow-50 border-yellow-200
        @elseif($isFuture) bg-gray-50 border-gray-200
        @else bg-red-50 border-red-200
        @endif">
        <div class="text-3xl mb-2">
            @if($isToday && $isFinished) ✅
            @elseif($isToday && $isStarted) 🚐
            @elseif($isToday && !$isStarted) ⏳
            @elseif($isFuture) 📅
            @else ⚠️
            @endif
        </div>
        <p class="font-bold text-lg">
            @if($isToday && $isFinished)
                Jadwal Selesai
            @elseif($isToday && $isStarted)
                Dalam Perjalanan
            @elseif($isToday && !$isStarted)
                Menunggu Agency Memulai
            @elseif($isFuture)
                Jadwal Mendatang — {{ $schedule->departure_date->diffForHumans() }}
            @else
                Jadwal Terlewat
            @endif
        </p>
        @if($isToday && !$isStarted)
        <p class="text-sm mt-1 font-light text-yellow-700">
            Data penumpang akan muncul setelah agency mengklik tombol <strong>Mulai</strong>.
        </p>
        @endif
    </div>

    {{-- HANYA TAMPILKAN JIKA SUDAH DIMULAI --}}
    @if($isStarted || $isFinished)
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
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3 text-center">
                <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Total Booking</p>
                <p class="text-xl font-bold text-[#111111]">{{ $totalBookings }}</p>
                <p class="text-[10px] text-gray-400 font-light">{{ $totalPassengers }} penumpang</p>
            </div>
            <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-3 text-center">
                <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Sudah Dijemput</p>
                <p class="text-xl font-bold text-[#111111]">{{ $pickedUpBookings }}</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-[12px] p-3 text-center">
                <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Sudah Turun</p>
                <p class="text-xl font-bold text-[#111111]">{{ $droppedOffBookings }}</p>
            </div>
        </div>

        @if($totalBookings > 0)
        <div class="bg-[#E5E5E5] rounded-full h-3 mb-6 overflow-hidden">
            <div class="bg-[#C1121F] h-full rounded-full transition-all" style="width: {{ ($droppedOffBookings / $totalBookings) * 100 }}%"></div>
        </div>
        @endif

        @if($allBookings->isEmpty())
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-8 text-center text-gray-500 shadow-sm font-light">
            Belum ada penumpang.
        </div>
        @else
        <div class="space-y-4">
            @foreach($allBookings as $booking)
            @php
                $allPassengers = $booking->passengers;
                $allPickedUp = $allPassengers->every(fn($p) => $p->picked_up_at !== null);
                $allDroppedOff = $allPassengers->every(fn($p) => $p->dropped_off_at !== null);
                $anyPickedUp = $allPassengers->some(fn($p) => $p->picked_up_at !== null);
            @endphp

            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 shadow-sm border-l-4
                @if($allDroppedOff) border-[#C1121F]
                @elseif($anyPickedUp) border-blue-500
                @else border-yellow-500 @endif">

                <div class="flex justify-between items-start mb-3 pb-3 border-b border-[#E5E5E5]">
                    <div>
                        <span class="font-bold font-mono text-[#111111]">{{ $booking->booking_code }}</span>
                        <span class="text-sm text-gray-500 ml-2 font-light">
                            {{ $booking->originStop->city_name ?? '?' }} → {{ $booking->destinationStop->city_name ?? '?' }}
                        </span>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                        @if($allDroppedOff) bg-green-50 text-green-700 border-green-200
                        @elseif($anyPickedUp) bg-blue-50 text-blue-700 border-blue-200
                        @else bg-yellow-50 text-yellow-700 border-yellow-200 @endif">
                        @if($allDroppedOff) ✅ Selesai
                        @elseif($anyPickedUp) 🚗 Dalam Perjalanan
                        @else ⏳ Menunggu
                        @endif
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-3 text-sm">
                    <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-2">
                        <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">📍 Jemput</span>
                        <p class="font-medium text-[#111111] text-xs">{{ $booking->pickup_address }}</p>
                    </div>
                    <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-2">
                        <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">🎯 Tujuan</span>
                        <p class="font-medium text-[#111111] text-xs">{{ $booking->destination_address }}</p>
                    </div>
                </div>

                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3 mb-3">
                    <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mb-2">👥 Penumpang ({{ $booking->total_passengers }} orang)</p>
                    <div class="space-y-1">
                        @foreach($booking->passengers as $p)
                        <div class="flex justify-between text-sm py-1 border-b border-[#E5E5E5] last:border-0">
                            <span class="text-[#111111]">{{ $p->passenger_name }} (Seat {{ $p->seat_number }})</span>
                            <span class="text-gray-500 text-xs font-light">{{ $p->passenger_phone ?? '-' }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                @if(!$isFinished)
                <div class="flex gap-2 border-t border-[#E5E5E5] pt-3">
                    @if(!$anyPickedUp)
                    <form action="{{ route('driver.passenger.pickup', $booking->passengers->first()) }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full bg-[#C1121F] text-white py-2 rounded-[12px] text-sm font-medium hover:bg-[#8A0F18] transition">
                            ✅ JEMPUT ({{ $booking->total_passengers }} orang)
                        </button>
                    </form>
                    @elseif($anyPickedUp && !$allDroppedOff)
                    <form action="{{ route('driver.passenger.dropoff', $booking->passengers->first()) }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-[12px] text-sm font-medium hover:bg-blue-700 transition">
                            🚗 TURUNKAN ({{ $booking->total_passengers }} orang)
                        </button>
                    </form>
                    @endif

                    @if($allDroppedOff)
                    <div class="flex-1 bg-green-50 border border-green-200 text-green-700 py-2 rounded-[12px] text-sm font-medium text-center">
                        ✅ Selesai
                    </div>
                    @endif
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    @endif
    @endif
</div>
@endsection