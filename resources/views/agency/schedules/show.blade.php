@extends('layouts.agency')

@section('title', 'Detail Jadwal')
@section('content')
@php
    $scheduleData = $scheduleData ?? [];
    $schedule = $scheduleData['schedule'] ?? $schedule ?? null;
    $pricing_matrix = $scheduleData['pricing_matrix'] ?? [];
    
    if ($schedule) {
        $bookings = $schedule->bookings()
            ->whereNotIn('status', ['cancelled'])
            ->with(['originStop', 'destinationStop', 'passengers', 'customer', 'payment', 'cashPayment'])
            ->get();
        
        $totalPassengers = $bookings->sum('total_passengers');
        $totalRevenue = $bookings->where('status', 'paid')->sum('total_price');
        $occupancyRate = $schedule->max_capacity > 0 ? round(($totalPassengers / $schedule->max_capacity) * 100, 1) : 0;
        $canTransfer = app(\App\Services\PassengerTransferService::class)->canTransfer($schedule);
        
        $hasPP = !is_null($schedule->ppSchedule);
        $ppSchedule = $schedule->ppSchedule;
        $isReturn = !is_null($schedule->parentSchedule);
        $isRentalVehicle = $schedule->vehicle->rentalSetting && $schedule->vehicle->rentalSetting->is_available_for_rental;
        
        // Pricing pergi - langsung dari database sebagai fallback
        $goPricing = $schedule->routePricing()->with(['originStop', 'destinationStop'])->get();
        
        if ($hasPP) {
            $ppBookings = $ppSchedule->bookings()
                ->whereNotIn('status', ['cancelled'])
                ->with(['originStop', 'destinationStop', 'passengers', 'customer', 'payment'])
                ->get();
            $ppTotalPassengers = $ppBookings->sum('total_passengers');
            $ppTotalRevenue = $ppBookings->where('status', 'paid')->sum('total_price');
            $ppOccupancyRate = $ppSchedule->max_capacity > 0 ? round(($ppTotalPassengers / $ppSchedule->max_capacity) * 100, 1) : 0;
            
            // Pricing PP - langsung dari database
            $ppPricing = $ppSchedule->routePricing()->with(['originStop', 'destinationStop'])->get();
        }
    }
@endphp

@if(!$schedule)
<div class="text-center py-12">
    <p class="text-gray-500 font-light">Jadwal tidak ditemukan.</p>
    <a href="{{ route('agency.schedules.index') }}" class="text-[#BA1826] hover:underline mt-2 inline-block">← Kembali</a>
</div>
@else

<div class="max-w-5xl mx-auto" x-data="{ 
    activeTab: '{{ $activeTab ?? ($isReturn ? 'pulang' : 'pergi') }}',
    showStopConfigGo: false,
    showStopConfigPP: false
}">
    
    {{-- Header & Aksi --}}
    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-6">
        <div>
            <a href="{{ route('agency.schedules.index') }}" class="text-[#BA1826] text-sm mb-2 inline-block hover:underline">← Kembali ke Daftar</a>
            <h1 class="text-2xl font-bold text-[#111827]">
                {{ $schedule->route->route_name }}
                @if($hasPP)
                <span class="text-sm font-normal text-purple-600 ml-2 bg-purple-50 px-3 py-1 rounded-full border border-purple-200">🔄 PP</span>
                @endif
                @if($isReturn)
                <span class="text-sm font-normal text-purple-600 ml-2 bg-purple-50 px-3 py-1 rounded-full border border-purple-200">(Jadwal Pulang)</span>
                @endif
            </h1>
            <p class="text-gray-500 text-sm font-light mt-1">
                Jadwal #{{ $schedule->id }} 
                @if($hasPP)
                | PP: #{{ $ppSchedule->id }}
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(!$schedule->started_at && $schedule->departure_date->isToday() && $schedule->driver_id && !$isReturn)
            <form action="{{ route('agency.schedules.start', $schedule) }}" method="POST">
                @csrf
                <button type="submit" class="bg-[#BA1826] text-white px-5 py-2.5 rounded-[10px] text-sm font-semibold hover:bg-[#8A0F18] transition shadow-sm" onclick="return confirm('Mulai jadwal? Driver akan dapat mengakses data penumpang.')">
                    ▶️ Mulai Jadwal
                </button>
            </form>
            @elseif($schedule->started_at && !$schedule->finished_at)
            <span class="bg-blue-50 text-blue-700 px-4 py-2.5 rounded-[10px] text-sm font-semibold inline-flex items-center border border-blue-200 shadow-sm">
                <span class="w-2 h-2 bg-blue-500 rounded-full mr-2 animate-pulse"></span> Dalam Perjalanan
            </span>
            @elseif($schedule->finished_at)
            <span class="bg-green-50 text-green-700 px-4 py-2.5 rounded-[10px] text-sm font-semibold inline-flex items-center border border-green-200 shadow-sm">
                ✅ Selesai {{ $schedule->finished_at->format('d M H:i') }}
            </span>
            @endif

            @if($canTransfer && !$schedule->started_at && !$isReturn)
            <a href="{{ route('agency.schedules.transfer', $schedule) }}" class="bg-orange-500 text-white px-4 py-2.5 rounded-[10px] text-sm font-semibold hover:bg-orange-600 transition shadow-sm inline-flex items-center gap-1">
                🔄 Transfer
            </a>
            @endif

            @if(!$schedule->started_at && !$isReturn)
            <button type="button" onclick="confirmDeleteSchedule()" 
                    class="bg-red-500 text-white px-4 py-2.5 rounded-[10px] text-sm font-semibold hover:bg-red-600 transition shadow-sm inline-flex items-center gap-1">
                🗑️ Hapus
            </button>
            <form id="deleteScheduleForm" action="{{ route('agency.schedules.destroy', $schedule) }}" method="POST" style="display:none;">
                @csrf @method('DELETE')
            </form>
            @endif
        </div>
    </div>

    {{-- TAB NAVIGASI (HANYA JIKA PP) --}}
    @if($hasPP || $isReturn)
    <div class="flex gap-1 bg-[#F5F5F5] rounded-lg p-1 mb-6 w-fit">
        <button @click="activeTab = 'pergi'" 
                :class="activeTab === 'pergi' ? 'bg-white shadow text-[#BA1826]' : 'text-gray-500 hover:text-[#111827]'"
                class="px-5 py-2 rounded-md text-sm font-semibold transition">
            🚐 Jadwal Pergi
        </button>
        <button @click="activeTab = 'pulang'" 
                :class="activeTab === 'pulang' ? 'bg-white shadow text-[#BA1826]' : 'text-gray-500 hover:text-[#111827]'"
                class="px-5 py-2 rounded-md text-sm font-semibold transition">
            🔄 Jadwal Pulang
        </button>
    </div>
    @endif

    {{-- ═══════════════════════════════════════ --}}
    {{-- CONTENT: JADWAL PERGI --}}
    {{-- ═══════════════════════════════════════ --}}
    <div x-show="activeTab === 'pergi'">
        
        {{-- PP Info Banner --}}
        @if($hasPP)
        <div class="bg-purple-50 border border-purple-200 rounded-[12px] p-4 mb-6">
            <div class="flex items-center gap-2 mb-3">
                <span class="text-lg">🔄</span>
                <h3 class="font-bold text-purple-800">Jadwal Pulang-Pergi (PP)</h3>
            </div>
            <div class="grid md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-purple-700 font-light">
                        <strong>Pergi:</strong> {{ $schedule->route->route_name }}<br>
                        <span class="text-xs">{{ $schedule->departure_date->format('d M Y') }} {{ $schedule->departure_time }}</span>
                        @if($schedule->estimated_arrival)
                        <br><span class="text-xs text-purple-500">Estimasi tiba: {{ $schedule->estimated_arrival->format('d M Y H:i') }}</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-purple-700 font-light">
                        <strong>Pulang:</strong> {{ $ppSchedule->route->route_name }}<br>
                        <span class="text-xs">{{ $ppSchedule->departure_date->format('d M Y') }} {{ $ppSchedule->departure_time }}</span>
                        @if($ppSchedule->estimated_arrival)
                        <br><span class="text-xs text-purple-500">Estimasi tiba PP: {{ $ppSchedule->estimated_arrival->format('d M Y H:i') }}</span>
                        @endif
                    </p>
                </div>
            </div>
            @if($isRentalVehicle && ($schedule->available_for_rental_after || ($ppSchedule && $ppSchedule->available_for_rental_after)))
            @php $rentalDate = $ppSchedule->available_for_rental_after ?? $schedule->available_for_rental_after; @endphp
            <div class="mt-3 bg-white border border-purple-200 rounded-lg p-3 text-sm">
                <p class="text-purple-700 font-light">
                    🚗 <strong>Ketersediaan Rental:</strong> Kendaraan bisa dipakai rental lagi mulai 
                    <strong>{{ \Carbon\Carbon::parse($rentalDate)->format('d M Y') }}</strong>
                </p>
            </div>
            @endif
        </div>
        @endif

        {{-- Rental Info (non-PP) --}}
        @if(!$hasPP && $isRentalVehicle && $schedule->available_for_rental_after)
        <div class="bg-blue-50 border border-blue-200 rounded-[12px] p-4 mb-6">
            <div class="flex items-center gap-2">
                <span class="text-lg">🚗</span>
                <p class="text-blue-700 font-light text-sm">
                    <strong>Ketersediaan Rental:</strong> Kendaraan bisa dipakai rental lagi mulai 
                    <strong>{{ \Carbon\Carbon::parse($schedule->available_for_rental_after)->format('d M Y') }}</strong>
                </p>
            </div>
        </div>
        @endif

        @if(!$schedule->driver_id && !$isReturn)
        <div class="bg-yellow-50 border border-yellow-200 rounded-[10px] p-4 mb-6 text-sm text-yellow-800 font-light flex items-center justify-between">
            <span>⚠️ Belum ada driver yang ditugaskan.</span>
            <button onclick="openAssignDriverModal()" class="text-[#BA1826] underline font-medium text-sm">+ Tugaskan Driver</button>
        </div>
        @endif

        {{-- Ringkasan Jadwal Pergi --}}
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 mb-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-lg text-[#111827]">📋 Ringkasan Jadwal</h2>
                <button @click="showStopConfigGo = !showStopConfigGo" class="text-sm text-[#BA1826] hover:underline font-medium">
                    <span x-text="showStopConfigGo ? 'Sembunyikan Detail' : 'Lihat Detail Stop & Harga'"></span>
                </button>
            </div>
            
            {{-- Grid Info Utama --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Rute</span>
                    <p class="font-bold text-[#111827] text-sm">{{ $schedule->route->route_name }}</p>
                    <p class="text-xs text-gray-400">{{ $schedule->route->origin_city_name }} → {{ $schedule->route->destination_city_name }}</p>
                </div>
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Tanggal & Jam</span>
                    <p class="font-bold text-[#111827]">{{ $schedule->departure_date->format('d M Y') }}</p>
                    <p class="text-xs text-gray-500 font-mono">{{ $schedule->departure_time }}</p>
                </div>
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Kendaraan</span>
                    <p class="font-bold text-[#111827] font-mono">{{ $schedule->vehicle->plate_number ?? '-' }}</p>
                    <p class="text-xs text-gray-500">{{ $schedule->vehicle->brand ?? '' }} {{ $schedule->vehicle->model ?? '' }} ({{ $schedule->vehicle->capacity ?? '-' }} seat)</p>
                </div>
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Driver</span>
                    <p class="font-bold text-[#111827]">{{ $schedule->driver->name ?? 'Belum ditugaskan' }}</p>
                    @if($schedule->driver)
                    <p class="text-xs text-gray-500">{{ $schedule->driver->phone ?? '-' }}</p>
                    @endif
                </div>
            </div>

            {{-- Grid Info Tambahan --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Kelas</span>
                    <p class="font-bold text-[#111827] text-sm">{{ ucfirst($schedule->travel_class) }}</p>
                </div>
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Harga/Seat</span>
                    <p class="font-bold text-[#BA1826] font-mono">Rp {{ number_format($schedule->price_per_seat, 0, ',', '.') }}</p>
                </div>
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Max Overload</span>
                    <p class="font-bold text-[#111827] text-sm">{{ $schedule->max_overload }} orang</p>
                </div>
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Bagasi</span>
                    <p class="font-bold text-[#111827] text-sm">{{ number_format($schedule->baggage_limit_kg, 1) }} kg/orang</p>
                </div>
            </div>

            @if($schedule->estimated_arrival)
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Estimasi Tiba</span>
                    <p class="font-bold text-[#111827] text-sm">{{ $schedule->estimated_arrival->format('d M Y H:i') }}</p>
                </div>
                @if($schedule->available_for_rental_after && !$hasPP)
                <div class="bg-blue-50 border border-blue-200 rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">🚗 Siap Rental</span>
                    <p class="font-bold text-blue-700 text-sm">{{ \Carbon\Carbon::parse($schedule->available_for_rental_after)->format('d M Y') }}</p>
                </div>
                @endif
            </div>
            @endif

            {{-- Detail Stop & Harga (Collapsible) --}}
            <div x-show="showStopConfigGo" x-cloak class="border-t border-[#E5E7EB] pt-4 mt-2">
                {{-- Visualisasi Rute --}}
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">🛑 Rute & Stop</h3>
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[12px] p-4 mb-4">
                    <div class="flex items-center flex-wrap gap-1 text-sm">
                        @php $scheduleStops = $schedule->scheduleStops()->with('routeStop')->get()->sortBy(fn($ss) => $ss->routeStop->stop_order); @endphp
                        @foreach($scheduleStops as $index => $ss)
                            @php $stop = $ss->routeStop; @endphp
                            <span class="px-3 py-1.5 rounded-lg border text-xs font-medium
                                @if($stop->isFirst()) bg-green-50 text-green-700 border-green-300
                                @elseif($stop->isLast()) bg-red-50 text-red-700 border-red-300
                                @else bg-white text-[#111111] border-[#E5E7EB] @endif">
                                @if($ss->is_pickup_available && $ss->is_dropoff_available)
                                    🔄 {{ $stop->city_name }}
                                @elseif($ss->is_pickup_available)
                                    ✅ {{ $stop->city_name }}
                                @elseif($ss->is_dropoff_available)
                                    🎯 {{ $stop->city_name }}
                                @else
                                    ⚪ {{ $stop->city_name }}
                                @endif
                            </span>
                            @if($index < count($scheduleStops) - 1)
                            <span class="text-gray-400">→</span>
                            @endif
                        @endforeach
                    </div>
                    <div class="flex items-center gap-4 mt-3 text-[10px] text-gray-400 font-mono">
                        <span>✅ = Pickup</span>
                        <span>🎯 = Dropoff</span>
                        <span>🔄 = Pickup & Dropoff</span>
                        <span>⚪ = Tidak tersedia</span>
                    </div>
                </div>

                {{-- Matrix Harga Pergi --}}
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">💰 Matrix Harga</h3>
                @if($goPricing->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-[#E5E7EB] rounded-[12px] overflow-hidden">
                        <thead class="bg-[#F5F5F5]">
                            <tr>
                                <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Dari</th>
                                <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Ke</th>
                                <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-xs text-gray-500">Harga</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E5E7E5]">
                            @foreach($goPricing as $price)
                            <tr class="hover:bg-[#F9FAFB]">
                                <td class="px-4 py-3 font-medium text-[#111111]">{{ $price->originStop->city_name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $price->destinationStop->city_name }}</td>
                                <td class="px-4 py-3 text-right font-bold text-[#BA1826] font-mono">Rp {{ number_format($price->price, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-4 text-sm text-yellow-700 font-light">
                    ⚠️ Belum ada data harga untuk jadwal ini.
                </div>
                @endif
            </div>

            {{-- Okupansi --}}
            <div class="border-t border-[#E5E7EB] pt-4 mt-4">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-500 font-light">Okupansi</span>
                    <span class="font-semibold text-[#111827]">{{ $totalPassengers }}/{{ $schedule->max_capacity }} penumpang ({{ $occupancyRate }}%)</span>
                </div>
                <div class="bg-[#E5E7EB] rounded-full h-3 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $occupancyRate >= 80 ? 'bg-red-500' : ($occupancyRate >= 50 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                         style="width: {{ min($occupancyRate, 100) }}%"></div>
                </div>
            </div>
        </div>

        {{-- Metode Pembayaran --}}
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 mb-6 shadow-sm">
            <h2 class="font-bold text-lg text-[#111827] mb-4">💳 Metode Pembayaran</h2>
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-[#F9FAFB] rounded-[10px] p-4 text-center border border-[#E5E7EB]">
                    <div class="text-2xl mb-2">💳</div>
                    <p class="font-semibold text-[#111827] text-sm">Online (Midtrans)</p>
                    <p class="text-xs text-gray-500 mt-1 font-light">Selalu tersedia</p>
                </div>
                <div class="bg-[#F9FAFB] rounded-[10px] p-4 text-center border border-[#E5E7EB]">
                    <div class="text-2xl mb-2">🏪</div>
                    <p class="font-semibold text-[#111827] text-sm">Warung GoMad</p>
                    <p class="text-xs text-gray-500 mt-1 font-light">Selalu tersedia</p>
                </div>
                <div class="rounded-[10px] p-4 text-center border {{ $schedule->allow_cod ? 'bg-orange-50 border-orange-200' : 'bg-[#F9FAFB] border-[#E5E7EB]' }}">
                    <div class="text-2xl mb-2">🚗</div>
                    <p class="font-semibold text-sm {{ $schedule->allow_cod ? 'text-orange-700' : 'text-gray-400' }}">
                        COD (Bayar ke Sopir)
                    </p>
                    @if($schedule->allow_cod)
                    <p class="text-xs text-orange-600 mt-1 font-light">Tersedia</p>
                    <p class="text-xs text-orange-500 mt-1 font-mono">Min deposit: Rp {{ number_format($schedule->cod_min_balance, 0, ',', '.') }}</p>
                    @else
                    <p class="text-xs text-gray-400 mt-1 font-light">Tidak diaktifkan</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Status Timestamps --}}
        @if($schedule->started_at || $schedule->finished_at)
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-4 mb-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">⏱️ Status Perjalanan</h3>
            <div class="flex items-center gap-2 text-sm">
                @if($schedule->started_at)
                <span class="px-3 py-1.5 bg-blue-50 text-blue-700 rounded-full border border-blue-200 text-xs font-mono">
                    ▶️ Dimulai: {{ $schedule->started_at->format('d M Y H:i') }}
                </span>
                @endif
                @if($schedule->started_at && $schedule->finished_at)
                <span class="text-gray-300">→</span>
                @endif
                @if($schedule->finished_at)
                <span class="px-3 py-1.5 bg-green-50 text-green-700 rounded-full border border-green-200 text-xs font-mono">
                    ✅ Selesai: {{ $schedule->finished_at->format('d M Y H:i') }}
                </span>
                @endif
            </div>
        </div>
        @endif

        {{-- Daftar Penumpang --}}
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-bold text-lg text-[#111827]">👥 Penumpang</h2>
                <div class="text-right">
                    <p class="text-sm text-gray-500 font-light">{{ $bookings->count() }} booking, {{ $totalPassengers }} orang</p>
                    <p class="text-sm text-gray-500 font-light">Revenue: <strong class="text-[#BA1826]">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</strong></p>
                </div>
            </div>

            @if($bookings->isEmpty())
            <div class="text-center py-8">
                <div class="w-12 h-12 bg-[#F9FAFB] rounded-full flex items-center justify-center mx-auto mb-3 border border-[#E5E7EB]">
                    <span class="text-xl">🎫</span>
                </div>
                <p class="text-gray-500 font-light">Belum ada penumpang.</p>
            </div>
            @else
            <div class="space-y-3">
                @foreach($bookings as $booking)
                <div class="border border-[#E5E7EB] rounded-[12px] p-4 hover:border-[#BA1826] transition-colors">
                    <div class="flex flex-col md:flex-row md:justify-between gap-3 mb-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-bold text-base font-mono text-[#111827]">{{ $booking->booking_code }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                    @if($booking->status == 'paid') bg-green-50 text-green-700 border-green-200
                                    @elseif($booking->status == 'pending') bg-yellow-50 text-yellow-700 border-yellow-200
                                    @elseif($booking->status == 'on_going') bg-indigo-50 text-indigo-700 border-indigo-200
                                    @elseif($booking->status == 'completed') bg-green-50 text-green-700 border-green-200
                                    @else bg-[#F9FAFB] text-gray-600 border-[#E5E7EB] @endif">
                                    {{ $booking->status_label }}
                                </span>
                                @if($booking->payment)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                    @if($booking->payment->payment_type == 'midtrans') bg-blue-50 text-blue-700 border-blue-200
                                    @elseif($booking->payment->payment_type == 'cash') bg-green-50 text-green-700 border-green-200
                                    @elseif($booking->payment->payment_type == 'cod') bg-orange-50 text-orange-700 border-orange-200
                                    @endif">
                                    {{ $booking->payment->payment_type == 'midtrans' ? '💳 Online' : ($booking->payment->payment_type == 'cash' ? '🏪 Warung' : '🚗 COD') }}
                                    - {{ $booking->payment->status_label }}
                                </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 mt-1 font-light">
                                {{ $booking->originStop->city_name ?? '?' }} → {{ $booking->destinationStop->city_name ?? '?' }}
                            </p>
                            <p class="text-xs text-gray-500 font-light">
                                👤 {{ $booking->customer->name ?? '?' }} • 📞 {{ $booking->customer->phone ?? '?' }}
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="font-bold text-[#BA1826] font-mono text-lg">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                            <p class="text-xs text-gray-500 font-light">{{ $booking->total_passengers }} penumpang</p>
                            @if($booking->discount_amount > 0)
                            <p class="text-xs text-purple-600 font-mono">Diskon Rp {{ number_format($booking->discount_amount, 0, ',', '.') }}</p>
                            @endif
                        </div>
                    </div>
                    
                    <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-gray-400 text-[10px] font-mono uppercase tracking-wider">
                                    <th class="pb-2 text-left w-8">#</th>
                                    <th class="pb-2 text-left">Nama</th>
                                    <th class="pb-2 text-left">Telepon</th>
                                    <th class="pb-2 text-center w-20">Jemput</th>
                                    <th class="pb-2 text-center w-20">Turun</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($booking->passengers as $p)
                                <tr class="border-t border-[#E5E7EB]">
                                    <td class="py-2 text-gray-400 font-mono text-xs">S{{ $p->seat_number }}</td>
                                    <td class="py-2 font-medium text-[#111827]">{{ $p->passenger_name }}</td>
                                    <td class="py-2 text-gray-600 font-light text-xs">{{ $p->passenger_phone ?? '-' }}</td>
                                    <td class="py-2 text-center font-mono text-xs">
                                        @if($p->picked_up_at)
                                        <span class="text-green-600">✅ {{ $p->picked_up_at->format('H:i') }}</span>
                                        @else
                                        <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-center font-mono text-xs">
                                        @if($p->dropped_off_at)
                                        <span class="text-blue-600">✅ {{ $p->dropped_off_at->format('H:i') }}</span>
                                        @else
                                        <span class="text-gray-300">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════ --}}
    {{-- CONTENT: JADWAL PULANG (PP) --}}
    {{-- ═══════════════════════════════════════ --}}
    @if($hasPP)
    <div x-show="activeTab === 'pulang'" x-cloak>
        
        {{-- Rental Info PP --}}
        @if($isRentalVehicle && $ppSchedule->available_for_rental_after)
        <div class="bg-blue-50 border border-blue-200 rounded-[12px] p-4 mb-6">
            <div class="flex items-center gap-2">
                <span class="text-lg">🚗</span>
                <p class="text-blue-700 font-light text-sm">
                    <strong>Ketersediaan Rental:</strong> Kendaraan bisa dipakai rental lagi mulai 
                    <strong>{{ \Carbon\Carbon::parse($ppSchedule->available_for_rental_after)->format('d M Y') }}</strong>
                </p>
            </div>
        </div>
        @endif

        @if(!$ppSchedule->driver_id)
        <div class="bg-yellow-50 border border-yellow-200 rounded-[10px] p-4 mb-6 text-sm text-yellow-800 font-light">
            ⚠️ Belum ada driver. Driver akan mengikuti jadwal pergi.
        </div>
        @endif

        {{-- Ringkasan Jadwal PP --}}
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 mb-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-lg text-[#111827]">📋 Ringkasan Jadwal Pulang</h2>
                <button @click="showStopConfigPP = !showStopConfigPP" class="text-sm text-[#BA1826] hover:underline font-medium">
                    <span x-text="showStopConfigPP ? 'Sembunyikan Detail' : 'Lihat Detail Stop & Harga'"></span>
                </button>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Rute PP</span>
                    <p class="font-bold text-[#111827] text-sm">{{ $ppSchedule->route->route_name }}</p>
                    <p class="text-xs text-gray-400">{{ $ppSchedule->route->origin_city_name }} → {{ $ppSchedule->route->destination_city_name }}</p>
                </div>
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Tanggal & Jam</span>
                    <p class="font-bold text-[#111827]">{{ $ppSchedule->departure_date->format('d M Y') }}</p>
                    <p class="text-xs text-gray-500 font-mono">{{ $ppSchedule->departure_time }}</p>
                </div>
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Kendaraan</span>
                    <p class="font-bold text-[#111827] font-mono">{{ $ppSchedule->vehicle->plate_number ?? '-' }}</p>
                    <p class="text-xs text-gray-500">{{ $ppSchedule->vehicle->brand ?? '' }} {{ $ppSchedule->vehicle->model ?? '' }}</p>
                </div>
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Driver</span>
                    <p class="font-bold text-[#111827]">{{ $ppSchedule->driver->name ?? 'Mengikuti pergi' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Kelas</span>
                    <p class="font-bold text-[#111827] text-sm">{{ ucfirst($ppSchedule->travel_class) }}</p>
                </div>
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Harga/Seat</span>
                    <p class="font-bold text-[#BA1826] font-mono">Rp {{ number_format($ppSchedule->price_per_seat, 0, ',', '.') }}</p>
                </div>
                @if($ppSchedule->estimated_arrival)
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Estimasi Tiba PP</span>
                    <p class="font-bold text-[#111827] text-sm">{{ $ppSchedule->estimated_arrival->format('d M Y H:i') }}</p>
                </div>
                @endif
                @if($ppSchedule->available_for_rental_after)
                <div class="bg-blue-50 border border-blue-200 rounded-[10px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">🚗 Siap Rental</span>
                    <p class="font-bold text-blue-700 text-sm">{{ \Carbon\Carbon::parse($ppSchedule->available_for_rental_after)->format('d M Y') }}</p>
                </div>
                @endif
            </div>

            {{-- Detail Stop & Harga PP --}}
            <div x-show="showStopConfigPP" x-cloak class="border-t border-[#E5E7EB] pt-4 mt-2">
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">🛑 Rute & Stop PP</h3>
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[12px] p-4 mb-4">
                    <div class="flex items-center flex-wrap gap-1 text-sm">
                        @php $ppStops = $ppSchedule->scheduleStops()->with('routeStop')->get()->sortBy(fn($ss) => $ss->routeStop->stop_order); @endphp
                        @foreach($ppStops as $index => $ss)
                            @php $stop = $ss->routeStop; @endphp
                            <span class="px-3 py-1.5 rounded-lg border text-xs font-medium
                                @if($stop->isFirst()) bg-green-50 text-green-700 border-green-300
                                @elseif($stop->isLast()) bg-red-50 text-red-700 border-red-300
                                @else bg-white text-[#111111] border-[#E5E7EB] @endif">
                                @if($ss->is_pickup_available && $ss->is_dropoff_available)🔄 {{ $stop->city_name }}
                                @elseif($ss->is_pickup_available)✅ {{ $stop->city_name }}
                                @elseif($ss->is_dropoff_available)🎯 {{ $stop->city_name }}
                                @else ⚪ {{ $stop->city_name }}
                                @endif
                            </span>
                            @if($index < count($ppStops) - 1)<span class="text-gray-400">→</span>@endif
                        @endforeach
                    </div>
                </div>

                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">💰 Matrix Harga PP</h3>
                @if($ppPricing->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border border-[#E5E7EB] rounded-[12px] overflow-hidden">
                        <thead class="bg-[#F5F5F5]">
                            <tr>
                                <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Dari</th>
                                <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Ke</th>
                                <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-xs text-gray-500">Harga</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E5E7E5]">
                            @foreach($ppPricing as $price)
                            <tr class="hover:bg-[#F9FAFB]">
                                <td class="px-4 py-3 font-medium text-[#111111]">{{ $price->originStop->city_name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $price->destinationStop->city_name }}</td>
                                <td class="px-4 py-3 text-right font-bold text-[#BA1826] font-mono">Rp {{ number_format($price->price, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-4 text-sm text-yellow-700 font-light">
                    ⚠️ Belum ada data harga untuk jadwal PP.
                </div>
                @endif
            </div>

            {{-- Okupansi PP --}}
            <div class="border-t border-[#E5E7EB] pt-4 mt-4">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-500 font-light">Okupansi PP</span>
                    <span class="font-semibold text-[#111827]">{{ $ppTotalPassengers }}/{{ $ppSchedule->max_capacity }} penumpang ({{ $ppOccupancyRate }}%)</span>
                </div>
                <div class="bg-[#E5E7EB] rounded-full h-3 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ $ppOccupancyRate >= 80 ? 'bg-red-500' : ($ppOccupancyRate >= 50 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                         style="width: {{ min($ppOccupancyRate, 100) }}%"></div>
                </div>
            </div>
        </div>

        {{-- Status Timestamps PP --}}
        @if($ppSchedule->started_at || $ppSchedule->finished_at)
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-4 mb-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">⏱️ Status Perjalanan PP</h3>
            <div class="flex items-center gap-2 text-sm">
                @if($ppSchedule->started_at)
                <span class="px-3 py-1.5 bg-blue-50 text-blue-700 rounded-full border border-blue-200 text-xs font-mono">
                    ▶️ Dimulai: {{ $ppSchedule->started_at->format('d M Y H:i') }}
                </span>
                @endif
                @if($ppSchedule->started_at && $ppSchedule->finished_at)
                <span class="text-gray-300">→</span>
                @endif
                @if($ppSchedule->finished_at)
                <span class="px-3 py-1.5 bg-green-50 text-green-700 rounded-full border border-green-200 text-xs font-mono">
                    ✅ Selesai: {{ $ppSchedule->finished_at->format('d M Y H:i') }}
                </span>
                @endif
            </div>
        </div>
        @endif

        {{-- Penumpang PP --}}
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 shadow-sm">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-bold text-lg text-[#111827]">👥 Penumpang PP</h2>
                <div class="text-right">
                    <p class="text-sm text-gray-500 font-light">{{ $ppBookings->count() }} booking, {{ $ppTotalPassengers }} orang</p>
                    <p class="text-sm text-gray-500 font-light">Revenue: <strong class="text-[#BA1826]">Rp {{ number_format($ppTotalRevenue, 0, ',', '.') }}</strong></p>
                </div>
            </div>

            @if($ppBookings->isEmpty())
            <div class="text-center py-8">
                <div class="w-12 h-12 bg-[#F9FAFB] rounded-full flex items-center justify-center mx-auto mb-3 border border-[#E5E7EB]">
                    <span class="text-xl">🎫</span>
                </div>
                <p class="text-gray-500 font-light">Belum ada penumpang untuk jadwal PP.</p>
            </div>
            @else
            <div class="space-y-3">
                @foreach($ppBookings as $booking)
                <div class="border border-[#E5E7EB] rounded-[12px] p-4 hover:border-[#BA1826] transition-colors">
                    <div class="flex flex-col md:flex-row md:justify-between gap-3 mb-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-bold text-base font-mono text-[#111827]">{{ $booking->booking_code }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                    @if($booking->status == 'paid') bg-green-50 text-green-700 border-green-200
                                    @elseif($booking->status == 'pending') bg-yellow-50 text-yellow-700 border-yellow-200
                                    @elseif($booking->status == 'on_going') bg-indigo-50 text-indigo-700 border-indigo-200
                                    @elseif($booking->status == 'completed') bg-green-50 text-green-700 border-green-200
                                    @else bg-[#F9FAFB] text-gray-600 border-[#E5E7EB] @endif">
                                    {{ $booking->status_label }}
                                </span>
                                @if($booking->payment)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                    @if($booking->payment->payment_type == 'midtrans') bg-blue-50 text-blue-700 border-blue-200
                                    @elseif($booking->payment->payment_type == 'cash') bg-green-50 text-green-700 border-green-200
                                    @elseif($booking->payment->payment_type == 'cod') bg-orange-50 text-orange-700 border-orange-200
                                    @endif">
                                    {{ $booking->payment->payment_type == 'midtrans' ? '💳 Online' : ($booking->payment->payment_type == 'cash' ? '🏪 Warung' : '🚗 COD') }}
                                </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 mt-1 font-light">
                                {{ $booking->originStop->city_name ?? '?' }} → {{ $booking->destinationStop->city_name ?? '?' }}
                            </p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="font-bold text-[#BA1826] font-mono text-lg">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                            <p class="text-xs text-gray-500 font-light">{{ $booking->total_passengers }} penumpang</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- MODAL ASSIGN DRIVER --}}
<div id="assignDriverModal" style="display:none;" class="fixed inset-0 bg-[#111827]/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-[12px] shadow-xl p-6 max-w-md w-full border border-[#E5E7EB]">
        <h3 class="font-bold text-lg text-[#111827] mb-4">Tugaskan Driver</h3>
        <form action="{{ route('agency.schedules.assign-driver', $schedule) }}" method="POST">
            @csrf
            <select name="driver_id" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] mb-4" required>
                <option value="">Pilih Driver</option>
                @foreach(auth()->user()->agency->drivers()->where('is_active', true)->get() as $driver)
                <option value="{{ $driver->id }}">{{ $driver->name }} ({{ $driver->phone }})</option>
                @endforeach
            </select>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-[#BA1826] text-white py-2.5 rounded-[10px] font-semibold hover:bg-[#8A0F18] transition">Simpan</button>
                <button type="button" onclick="document.getElementById('assignDriverModal').style.display='none'" class="flex-1 border border-[#E5E7EB] py-2.5 rounded-[10px] font-medium hover:bg-[#F5F5F5] transition">Batal</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openAssignDriverModal() { 
    document.getElementById('assignDriverModal').style.display = 'flex'; 
}

function confirmDeleteSchedule() {
    if (confirm('Hapus jadwal ini?\n\nData tidak bisa dikembalikan.\nJika jadwal menggunakan COD, saldo deposit akan dikembalikan.\nJika jadwal memiliki PP, jadwal PP juga akan dihapus.')) {
        document.getElementById('deleteScheduleForm').submit();
    }
}

document.getElementById('assignDriverModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});
</script>
@endpush
@endif
@endsection