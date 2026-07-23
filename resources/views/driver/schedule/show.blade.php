@extends('layouts.driver')

@section('title', 'Detail Jadwal')
@section('content')
<div class="max-w-2xl mx-auto">
    @php
        $schedule = \App\Models\Schedule::with(['route.stops', 'vehicle', 'bookings' => function($q) {
            $q->whereNotIn('status', ['cancelled'])->with(['originStop', 'destinationStop', 'passengers', 'customer']);
        }])->findOrFail(request()->route('schedule'));
        
        $isToday = $schedule->departure_date->isToday();
        $isFuture = $schedule->departure_date->isFuture();
        $isStarted = !is_null($schedule->started_at);
        $isFinished = !is_null($schedule->finished_at);
    @endphp

    <a href="{{ route('driver.schedule') }}" class="text-[#C1121F] text-sm mb-4 inline-block hover:underline">← Kembali</a>
    <h1 class="text-xl font-bold text-[#111111] mb-2">{{ $schedule->route->route_name }}</h1>
    <p class="text-gray-600 font-light mb-4">
        {{ $schedule->departure_date->format('d M Y') }} | 
        {{ $schedule->departure_time }} | 
        🚐 {{ $schedule->vehicle->plate_number }}
    </p>

    {{-- ═══════════════════════════════════════ --}}
    {{-- STATUS SCHEDULE --}}
    {{-- ═══════════════════════════════════════ --}}
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
            @if($isToday && $isFinished) Jadwal Selesai
            @elseif($isToday && $isStarted) Dalam Perjalanan
            @elseif($isToday && !$isStarted) Menunggu Agency Memulai
            @elseif($isFuture) Jadwal Mendatang — {{ $schedule->departure_date->diffForHumans() }}
            @else Jadwal Terlewat
            @endif
        </p>
    </div>

    {{-- HANYA TAMPILKAN PENUMPANG JIKA SUDAH DIMULAI --}}
    @if($isStarted || $isFinished)
        <h2 class="font-bold text-lg text-[#111111] mb-3 border-b border-[#E5E5E5] pb-2">
            Daftar Penumpang ({{ $schedule->bookings->sum('total_passengers') }})
        </h2>

        @if($schedule->bookings->isEmpty())
        <p class="text-gray-500 text-center py-8 font-light">Belum ada penumpang.</p>
        @else
        <div class="space-y-4">
            @foreach($schedule->bookings as $booking)
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 shadow-sm">
                <div class="flex justify-between mb-2">
                    <span class="font-semibold font-mono text-[#111111]">{{ $booking->booking_code }}</span>
                    <span class="text-sm text-gray-500 font-light">{{ $booking->originStop->city_name }} → {{ $booking->destinationStop->city_name }}</span>
                </div>
                <p class="text-sm text-gray-500 font-light mb-2">Jemput: {{ $booking->pickup_address }}</p>
                @foreach($booking->passengers as $p)
                <div class="flex justify-between items-center py-2 border-b border-[#F5F5F5] text-sm last:border-0">
                    <div>
                        <span class="font-medium text-[#111111]">{{ $p->passenger_name }}</span>
                        <span class="text-gray-400 ml-2 font-mono">Seat {{ $p->seat_number }}</span>
                    </div>
                    @if(!$isFinished)
                    <div class="flex gap-2">
                        @if(!$p->picked_up_at)
                        <form action="{{ route('driver.passenger.pickup', $p) }}" method="POST">
                            @csrf
                            <button class="bg-[#C1121F] text-white px-3 py-1 rounded-[8px] text-[10px] font-medium hover:bg-[#8A0F18]">Jemput</button>
                        </form>
                        @elseif(!$p->dropped_off_at)
                        <form action="{{ route('driver.passenger.dropoff', $p) }}" method="POST">
                            @csrf
                            <button class="bg-blue-600 text-white px-3 py-1 rounded-[8px] text-[10px] font-medium hover:bg-blue-700">Turunkan</button>
                        </form>
                        @else
                        <span class="text-green-600 text-[10px] font-mono uppercase tracking-wider">✅ Selesai</span>
                        @endif
                    </div>
                    @else
                    <span class="text-green-600 text-[10px] font-mono uppercase tracking-wider">✅ Selesai</span>
                    @endif
                </div>
                @endforeach
            </div>
            @endforeach
        </div>
        @endif
    @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-6 text-center">
            <p class="text-yellow-800 font-medium">
                @if($isFuture)
                    📅 Jadwal ini masih mendatang. Data penumpang akan muncul setelah agency memulai jadwal.
                @else
                    ⏳ Menunggu agency memulai jadwal. Data penumpang akan muncul setelah jadwal dimulai.
                @endif
            </p>
        </div>
    @endif
</div>
@endsection