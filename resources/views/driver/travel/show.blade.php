@extends('layouts.driver')

@section('title', 'Detail Penumpang')
@section('content')

<div class="max-w-2xl mx-auto">
    <a href="{{ route('driver.travel.index') }}" class="text-[#BA1826] text-sm mb-4 inline-block hover:underline">← Kembali ke Jadwal</a>

    @php
        $isStarted = !is_null($schedule->started_at);
        $isFinished = !is_null($schedule->finished_at);
        $allBookings = $schedule->bookings;
        $totalBookings = $allBookings->count();
        $totalPassengers = $allBookings->sum('total_passengers');
        $completedBookings = $allBookings->where('status', 'completed')->count();
    @endphp

    {{-- Header Jadwal --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-5 mb-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3">
            <div>
                <h1 class="text-xl font-bold text-[#111111]">{{ $schedule->route->route_name }}</h1>
                <p class="text-sm text-gray-600 font-light mt-1">
                    {{ $schedule->departure_date->format('d M Y') }} | {{ $schedule->departure_time }}
                </p>
                <p class="text-sm text-gray-600 font-light">
                    🚐 {{ $schedule->vehicle->plate_number ?? '-' }} — {{ $schedule->vehicle->brand ?? '' }} {{ $schedule->vehicle->model ?? '' }}
                </p>
                <p class="text-sm text-gray-600 font-light">🏢 {{ $schedule->agency->agency_name ?? '-' }}</p>
            </div>
            <div class="text-right flex-shrink-0">
                @if(!$isStarted)
                <span class="px-3 py-1 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-full text-[10px] font-mono uppercase tracking-wider">Menunggu Agency</span>
                @elseif($isFinished)
                <span class="px-3 py-1 bg-green-50 text-green-700 border border-green-200 rounded-full text-[10px] font-mono uppercase tracking-wider">Selesai</span>
                @else
                <span class="px-3 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-full text-[10px] font-mono uppercase tracking-wider">Dalam Perjalanan</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Statistik --}}
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-3 text-center shadow-sm">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Total</p>
            <p class="text-xl font-bold text-[#111111]">{{ $totalBookings }} booking</p>
            <p class="text-[10px] text-gray-400 font-light">{{ $totalPassengers }} pax</p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-3 text-center shadow-sm">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Selesai</p>
            <p class="text-xl font-bold text-green-600">{{ $completedBookings }}</p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-3 text-center shadow-sm">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Progress</p>
            <p class="text-xl font-bold text-[#BA1826]">{{ $totalBookings > 0 ? round(($completedBookings / $totalBookings) * 100) : 0 }}%</p>
        </div>
    </div>

    @if($totalBookings > 0)
    <div class="bg-[#E5E5E5] rounded-full h-2 mb-6 overflow-hidden">
        <div class="bg-[#BA1826] h-full rounded-full" style="width: {{ ($completedBookings / $totalBookings) * 100 }}%"></div>
    </div>
    @endif

    {{-- Daftar Penumpang --}}
    @if($allBookings->isEmpty())
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-8 text-center shadow-sm">
        <p class="text-gray-500 font-light">Belum ada penumpang.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($allBookings as $booking)
        @php
            $allPickedUp = $booking->passengers->every(fn($p) => $p->picked_up_at !== null);
            $allDroppedOff = $booking->passengers->every(fn($p) => $p->dropped_off_at !== null);
            $isCompleted = $booking->status === 'completed';
            $isCOD = $booking->payment && $booking->payment->payment_type == 'cod';
            $codPending = $booking->payment && $booking->payment->status == 'cod_pending';
            
            $pickupMapsUrl = $booking->pickup_maps_link ?: 'https://www.google.com/maps/search/?api=1&query=' . urlencode($booking->pickup_address);
            $pickupNavUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($booking->pickup_address);
            $destMapsUrl = $booking->destination_maps_link ?: 'https://www.google.com/maps/search/?api=1&query=' . urlencode($booking->destination_address);
        @endphp
        
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-5 shadow-sm border-l-4 
            @if($isCompleted) border-green-500 bg-green-50/20
            @elseif($allDroppedOff) border-blue-500 bg-blue-50/20
            @elseif($allPickedUp) border-yellow-500 bg-yellow-50/20
            @else border-gray-300 @endif">
            
            {{-- Header Booking --}}
            <div class="flex justify-between items-start mb-4 pb-3 border-b border-[#E5E5E5]">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-bold text-lg font-mono text-[#111111]">{{ $booking->booking_code }}</span>
                        @if($isCompleted)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider bg-green-50 text-green-700 border border-green-200">Selesai</span>
                        @elseif($allDroppedOff)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200">Sudah Turun</span>
                        @elseif($allPickedUp)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider bg-yellow-50 text-yellow-700 border border-yellow-200">Dalam Perjalanan</span>
                        @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider bg-gray-50 text-gray-600 border border-gray-200">Menunggu Jemput</span>
                        @endif
                        
                        @if($booking->payment)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                            @if($booking->payment->payment_type == 'cod') bg-orange-50 text-orange-700 border-orange-200
                            @elseif($booking->payment->payment_type == 'midtrans') bg-blue-50 text-blue-700 border-blue-200
                            @else bg-green-50 text-green-700 border-green-200 @endif">
                            @if($booking->payment->payment_type == 'cod')
                                🚗 COD
                                @if($booking->payment->status == 'cod_pending')
                                (Pending)
                                @elseif($booking->payment->status == 'cod_confirmed')
                                (Terkonfirmasi)
                                @endif
                            @elseif($booking->payment->payment_type == 'midtrans')
                                💳 Online
                            @else
                                🏪 Warung
                            @endif
                        </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600 font-light mt-1">
                        {{ $booking->originStop->city_name ?? '?' }} → {{ $booking->destinationStop->city_name ?? '?' }}
                    </p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="font-bold text-[#BA1826] font-mono">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-500 font-light">{{ $booking->total_passengers }} pax</p>
                </div>
            </div>

            {{-- Info Customer --}}
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3 mb-3">
                <p class="text-sm font-medium text-[#111111]">👤 {{ $booking->customer->name }}</p>
                <p class="text-sm text-gray-600 font-light">📞 {{ $booking->customer->phone }}</p>
            </div>

            {{-- List Penumpang --}}
            <div class="mb-4">
                <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mb-2">Daftar Penumpang:</p>
                <div class="space-y-2">
                    @foreach($booking->passengers as $p)
                    <div class="flex justify-between items-center text-sm bg-white rounded-[12px] p-2 border border-[#E5E5E5]">
                        <div>
                            <span class="font-medium text-[#111111]">{{ $p->passenger_name }}</span>
                            <span class="text-xs text-gray-400 ml-1 font-mono">Seat {{ $p->seat_number }}</span>
                        </div>
                        <span class="text-gray-500 text-xs font-light">{{ $p->passenger_phone ?? '-' }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Alamat Penjemputan --}}
            <div class="bg-[#F5F5F5] rounded-[12px] p-3 mb-3 border border-[#E5E5E5]">
                <div class="flex justify-between items-start mb-1">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-blue-700">📍 ALAMAT PENJEMPUTAN</span>
                    @if($allPickedUp)
                    <span class="text-[10px] font-mono uppercase tracking-wider text-green-600">✅ Dijemput {{ $booking->passengers->first()->picked_up_at?->format('H:i') }}</span>
                    @endif
                </div>
                <p class="text-sm font-medium text-[#111111]">{{ $booking->pickup_address }}</p>
                <div class="flex flex-wrap gap-2 mt-2">
                    <a href="{{ $pickupMapsUrl }}" target="_blank" 
                       class="inline-flex items-center gap-1.5 text-xs bg-[#BA1826] text-white px-3 py-2 rounded-[12px] hover:bg-[#8A0F18] transition font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        BUKA MAPS
                    </a>
                    @if(!$allPickedUp && !$isCompleted)
                    <a href="{{ $pickupNavUrl }}" target="_blank" 
                       class="inline-flex items-center gap-1.5 text-xs bg-green-500 text-white px-3 py-2 rounded-[12px] hover:bg-green-600 transition font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        NAVIGASI
                    </a>
                    @endif
                </div>
            </div>

            {{-- Alamat Tujuan --}}
            <div class="bg-[#F5F5F5] rounded-[12px] p-3 mb-4 border border-[#E5E5E5]">
                <div class="flex justify-between items-start mb-1">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-red-700">🎯 ALAMAT TUJUAN</span>
                    @if($allDroppedOff)
                    <span class="text-[10px] font-mono uppercase tracking-wider text-green-600">✅ Turun {{ $booking->passengers->first()->dropped_off_at?->format('H:i') }}</span>
                    @endif
                </div>
                <p class="text-sm font-medium text-[#111111]">{{ $booking->destination_address }}</p>
                <div class="flex flex-wrap gap-2 mt-2">
                    <a href="{{ $destMapsUrl }}" target="_blank" 
                       class="inline-flex items-center gap-1.5 text-xs bg-[#BA1826] text-white px-3 py-2 rounded-[12px] hover:bg-[#8A0F18] transition font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        BUKA MAPS
                    </a>
                </div>
            </div>

            {{-- ═══════════════════════════════════════ --}}
            {{-- TOMBOL AKSI --}}
            {{-- ═══════════════════════════════════════ --}}
            @if(!$isCompleted && $isStarted)
            <div class="flex flex-col sm:flex-row gap-2 border-t border-[#E5E5E5] pt-4">
                
                {{-- 1. JEMPUT — jika belum semua dijemput --}}
                @if(!$allPickedUp)
                <form action="{{ route('driver.travel.bookings.pickup', $booking) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full bg-[#BA1826] text-white py-3 rounded-[12px] font-semibold hover:bg-[#8A0F18] transition text-sm">
                        ✅ JEMPUT PENUMPANG
                    </button>
                </form>
                @endif

                {{-- 2. ANTAR — jika semua sudah dijemput, belum semua turun --}}
                @if($allPickedUp && !$allDroppedOff)
                <form action="{{ route('driver.travel.bookings.dropoff', $booking) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-[12px] font-semibold hover:bg-blue-700 transition text-sm">
                        🚗 ANTAR KE TUJUAN
                    </button>
                </form>
                @endif

                {{-- 3. SELESAI (Non-COD) — jika semua sudah turun dan bukan COD --}}
                @if($allDroppedOff && !$isCOD)
                <form action="{{ route('driver.travel.bookings.complete', $booking) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" 
                            class="w-full bg-green-600 text-white py-3 rounded-[12px] font-semibold hover:bg-green-700 transition text-sm"
                            onclick="return confirm('Selesaikan booking {{ $booking->booking_code }}?\n\nPastikan semua penumpang sudah diturunkan.')">
                        🎉 SELESAIKAN BOOKING
                    </button>
                </form>
                @endif

                {{-- 4. KONFIRMASI COD — jika COD pending dan semua sudah turun --}}
                @if($isCOD && $codPending && $allDroppedOff)
                <form action="{{ route('driver.travel.bookings.confirm-cod', $booking) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" 
                            class="w-full bg-orange-500 text-white py-3 rounded-[12px] font-semibold hover:bg-orange-600 transition text-sm"
                            onclick="return confirm('Konfirmasi pembayaran COD untuk {{ $booking->booking_code }}?\n\nTotal: Rp {{ number_format($booking->total_price, 0, ',', '.') }}')">
                        💰 KONFIRMASI COD
                    </button>
                </form>
                @endif

                {{-- 5. SELESAI (COD) — jika COD sudah dikonfirmasi dan semua sudah turun --}}
                @if($isCOD && !$codPending && $allDroppedOff)
                <form action="{{ route('driver.travel.bookings.complete', $booking) }}" method="POST" class="flex-1">
                    @csrf
                    <button type="submit" 
                            class="w-full bg-green-600 text-white py-3 rounded-[12px] font-semibold hover:bg-green-700 transition text-sm"
                            onclick="return confirm('Selesaikan booking {{ $booking->booking_code }}?')">
                        🎉 SELESAIKAN BOOKING
                    </button>
                </form>
                @endif

            </div>
            @endif

            {{-- Status Selesai --}}
            @if($isCompleted)
            <div class="bg-green-50 border border-green-300 text-green-700 py-3 rounded-[12px] text-sm font-semibold text-center">
                ✅ PERJALANAN SELESAI
            </div>
            @endif

            {{-- Menunggu Agency --}}
            @if(!$isStarted)
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 py-3 rounded-[12px] text-sm text-center font-light">
                ⏳ Menunggu agency memulai jadwal
            </div>
            @endif

            {{-- COD Info --}}
            @if($isCOD && !$isCompleted && !$codPending && $allDroppedOff && $booking->payment->status == 'cod_confirmed')
            <div class="bg-green-50 border border-green-200 text-green-700 py-3 rounded-[12px] text-sm text-center font-light mt-2">
                ✅ Pembayaran COD sudah dikonfirmasi. Silakan klik <strong>SELESAIKAN BOOKING</strong>.
            </div>
            @endif

        </div>
        @endforeach
    </div>

    {{-- Tombol Selesaikan Seluruh Jadwal --}}
    @if($isStarted && !$isFinished && $completedBookings >= $totalBookings && $totalBookings > 0)
    <form action="{{ route('driver.travel.finish', $schedule) }}" method="POST" class="mt-6" 
          onsubmit="return confirm('Selesaikan seluruh jadwal?\n\nSemua booking akan ditandai selesai dan saldo COD akan dilepas.')">
        @csrf
        <button type="submit" class="w-full bg-[#BA1826] text-white py-4 rounded-[12px] font-bold text-lg hover:bg-[#8A0F18] transition shadow-sm">
            🎉 SELESAIKAN SELURUH PERJALANAN
        </button>
    </form>
    @endif

    {{-- Info jika sudah selesai --}}
    @if($isFinished)
    <div class="mt-6 bg-green-50 border border-green-200 rounded-[12px] p-4 text-center">
        <p class="text-green-800 font-bold text-lg">✅ Jadwal Selesai</p>
        <p class="text-sm text-green-600 mt-1 font-light">Diselesaikan: {{ $schedule->finished_at->format('d M Y H:i') }}</p>
    </div>
    @endif
    @endif
</div>
@endsection