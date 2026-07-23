@extends('layouts.agency')

@section('title', 'Jadwal')
@section('content')
<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 border-b border-[#E5E5E5] pb-3">
        <h1 class="text-2xl font-bold text-[#111111]">Daftar Jadwal</h1>
        <a href="{{ route('agency.schedules.create') }}" class="btn-gomad-primary text-sm inline-flex items-center gap-2 self-start rounded-[12px]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Jadwal
        </a>
    </div>

    @if($schedules->isEmpty())
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-12 text-center shadow-sm">
        <div class="w-16 h-16 bg-[#C1121F]/5 rounded-[12px] flex items-center justify-center mx-auto mb-4 border border-[#E5E5E5]">
            <span class="text-2xl">📅</span>
        </div>
        <p class="text-gray-500 text-lg font-light mb-4">Belum ada jadwal.</p>
        <a href="{{ route('agency.schedules.create') }}" class="btn-gomad-primary">Buat Jadwal Pertama</a>
    </div>
    @else

    @php
        // Kelompokkan jadwal
        $todaySchedules = $schedules->filter(fn($s) => $s->departure_date->isToday());
        $futureSchedules = $schedules->filter(fn($s) => $s->departure_date->isFuture());
        $pastSchedules = $schedules->filter(fn($s) => $s->departure_date->isPast());
    @endphp

    {{-- ═══════════════════════════════════════ --}}
    {{-- JADWAL HARI INI (HIGHLIGHT) --}}
    {{-- ═══════════════════════════════════════ --}}
    @if($todaySchedules->isNotEmpty())
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 bg-[#C1121F] rounded-full flex items-center justify-center">
                <span class="text-white text-sm">📅</span>
            </div>
            <h2 class="text-lg font-bold text-[#111111]">Hari Ini — {{ now()->translatedFormat('d M Y') }}</h2>
        </div>

        @foreach($todaySchedules as $schedule)
        @php
            $rate = $schedule->occupancy_rate;
            $isStarted = !is_null($schedule->started_at);
            $isFinished = !is_null($schedule->finished_at);
            $canTransfer = app(\App\Services\PassengerTransferService::class)->canTransfer($schedule);

            // Status badge
            if ($isFinished) {
                $statusBadge = 'bg-green-50 text-green-700 border-green-200';
                $statusText = '✅ Selesai';
                $borderColor = 'border-green-300';
            } elseif ($isStarted) {
                $statusBadge = 'bg-blue-50 text-blue-700 border-blue-200';
                $statusText = '🚐 Dalam Perjalanan';
                $borderColor = 'border-blue-300';
            } else {
                $statusBadge = 'bg-yellow-50 text-yellow-700 border-yellow-200';
                $statusText = '⏳ Menunggu';
                $borderColor = 'border-yellow-300';
            }
        @endphp

        <div class="bg-white border-2 {{ $borderColor }} rounded-[12px] p-5 mb-4 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-4">
                {{-- Info Kiri --}}
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <h3 class="font-bold text-lg text-[#111111]">{{ $schedule->route->route_name }}</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border {{ $statusBadge }}">
                            {{ $statusText }}
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 font-light mb-3">
                        <span class="font-mono font-medium text-[#111111]">{{ $schedule->departure_time }}</span>
                        <span>|</span>
                        <span class="font-mono">{{ $schedule->vehicle->plate_number ?? '-' }}</span>
                        <span>|</span>
                        <span>{{ $schedule->vehicle->brand ?? '' }} {{ $schedule->vehicle->model ?? '' }}</span>
                        <span>|</span>
                        <span>👨‍✈️ {{ $schedule->driver->name ?? 'Belum ada' }}</span>
                    </div>

                    {{-- Okupansi Bar --}}
                    <div class="mb-1 flex justify-between text-xs">
                        <span class="text-gray-400 font-light">Okupansi</span>
                        <span class="font-semibold {{ $rate >= 80 ? 'text-red-600' : ($rate >= 50 ? 'text-yellow-600' : 'text-green-600') }}">{{ $rate }}%</span>
                    </div>
                    <div class="bg-[#E5E5E5] rounded-full h-2 overflow-hidden">
                        <div class="h-full rounded-full transition-all {{ $rate >= 80 ? 'bg-red-500' : ($rate >= 50 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                             style="width: {{ min($rate, 100) }}%"></div>
                    </div>
                </div>

                {{-- Aksi Kanan --}}
                <div class="flex flex-row lg:flex-col gap-2 flex-shrink-0">
                    {{-- Tombol Mulai --}}
                    @if(!$schedule->started_at && !$schedule->finished_at)
                    <form action="{{ route('agency.schedules.start', $schedule) }}" method="POST">
                        @csrf
                        <button type="submit" 
                                class="w-full lg:w-auto bg-[#C1121F] text-white px-4 py-2 rounded-[12px] text-sm font-semibold hover:bg-[#8A0F18] transition whitespace-nowrap"
                                onclick="return confirm('Mulai jadwal ini? Driver akan dapat mengakses data penumpang.')">
                            ▶️ Mulai Jadwal
                        </button>
                    </form>
                    @endif

                    <a href="{{ route('agency.schedules.show', $schedule) }}" 
                       class="w-full lg:w-auto text-center border border-[#E5E5E5] text-[#111111] px-4 py-2 rounded-[12px] text-sm font-medium hover:bg-[#F5F5F5] transition whitespace-nowrap">
                        📋 Detail
                    </a>

                    @if($canTransfer && !$schedule->started_at)
                    <a href="{{ route('agency.schedules.transfer', $schedule) }}" 
                       class="w-full lg:w-auto text-center border border-orange-300 text-orange-600 px-4 py-2 rounded-[12px] text-sm font-medium hover:bg-orange-50 transition whitespace-nowrap">
                        🔄 Transfer
                    </a>
                    @endif

                    @if(!$schedule->started_at)
                    <form action="{{ route('agency.schedules.destroy', $schedule) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" 
                                class="w-full lg:w-auto text-center border border-red-300 text-red-600 px-4 py-2 rounded-[12px] text-sm font-medium hover:bg-red-50 transition whitespace-nowrap"
                                onclick="return confirm('Hapus jadwal ini?')">
                            🗑️ Hapus
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            {{-- Info Booking Cepat --}}
            @php
                $totalBookings = $schedule->bookings()->whereNotIn('status', ['cancelled'])->count();
                $totalPassengers = $schedule->bookings()->whereNotIn('status', ['cancelled'])->sum('total_passengers');
            @endphp
            @if($totalBookings > 0)
            <div class="mt-3 pt-3 border-t border-[#E5E5E5] flex items-center gap-4 text-xs text-gray-500 font-light">
                <span>🎫 <strong class="text-[#111111]">{{ $totalBookings }}</strong> booking</span>
                <span>👥 <strong class="text-[#111111]">{{ $totalPassengers }}</strong> penumpang</span>
                <span>💰 <strong class="text-[#C1121F]">Rp {{ number_format($schedule->price_per_seat, 0, ',', '.') }}</strong>/seat</span>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- ═══════════════════════════════════════ --}}
    {{-- JADWAL MENDATANG --}}
    {{-- ═══════════════════════════════════════ --}}
    @if($futureSchedules->isNotEmpty())
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                <span class="text-gray-600 text-sm">📅</span>
            </div>
            <h2 class="text-lg font-bold text-[#111111]">Jadwal Mendatang</h2>
        </div>

        <div class="bg-white border border-[#E5E5E5] rounded-[12px] shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F5F5F5] border-b border-[#E5E5E5]">
                        <tr>
                            <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-gray-500 text-xs">Rute</th>
                            <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-gray-500 text-xs">Tanggal</th>
                            <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-gray-500 text-xs">Kendaraan</th>
                            <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-gray-500 text-xs">Driver</th>
                            <th class="px-4 py-3 text-center font-mono uppercase tracking-wider text-gray-500 text-xs">Okupansi</th>
                            <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-gray-500 text-xs">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E5E5E5]">
                        @foreach($futureSchedules as $schedule)
                        @php
                            $rate = $schedule->occupancy_rate;
                            $colorClass = $rate >= 80 ? 'text-red-600 bg-red-50 border-red-200' : ($rate >= 50 ? 'text-yellow-600 bg-yellow-50 border-yellow-200' : 'text-green-600 bg-green-50 border-green-200');
                            $canTransfer = app(\App\Services\PassengerTransferService::class)->canTransfer($schedule);
                        @endphp
                        <tr class="hover:bg-[#F5F5F5]">
                            <td class="px-4 py-3 font-medium text-[#111111]">{{ $schedule->route->route_name }}</td>
                            <td class="px-4 py-3 text-xs font-mono text-[#111111]">
                                {{ $schedule->departure_date->format('d M Y') }} 
                                <span class="text-gray-400">{{ $schedule->departure_time }}</span>
                                <br><span class="text-[10px] text-gray-400">{{ $schedule->departure_date->diffForHumans() }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ $schedule->vehicle->plate_number ?? '-' }}</td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ $schedule->driver->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded-full text-[10px] font-mono uppercase tracking-wider border {{ $colorClass }}">{{ $rate }}%</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('agency.schedules.show', $schedule) }}" class="text-[#C1121F] hover:underline text-xs font-medium">Detail</a>
                                    @if($canTransfer)
                                    <a href="{{ route('agency.schedules.transfer', $schedule) }}" class="text-orange-500 hover:underline text-xs font-medium">Transfer</a>
                                    @endif
                                    <form action="{{ route('agency.schedules.destroy', $schedule) }}" method="POST" class="inline" onsubmit="return confirm('Hapus jadwal?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 hover:underline text-xs font-medium">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════ --}}
    {{-- JADWAL TERLEWAT --}}
    {{-- ═══════════════════════════════════════ --}}
    @if($pastSchedules->isNotEmpty())
    <div>
        <div class="flex items-center gap-3 mb-4">
            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                <span class="text-gray-400 text-sm">📅</span>
            </div>
            <h2 class="text-lg font-bold text-gray-400">Jadwal Terlewat</h2>
            <span class="text-xs text-gray-400 font-light">({{ $pastSchedules->count() }})</span>
        </div>

        <div class="bg-white border border-[#E5E5E5] rounded-[12px] shadow-sm overflow-hidden opacity-60">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F5F5F5] border-b border-[#E5E5E5]">
                        <tr>
                            <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-gray-500 text-xs">Rute</th>
                            <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-gray-500 text-xs">Tanggal</th>
                            <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-gray-500 text-xs">Kendaraan</th>
                            <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-gray-500 text-xs">Driver</th>
                            <th class="px-4 py-3 text-center font-mono uppercase tracking-wider text-gray-500 text-xs">Status</th>
                            <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-gray-500 text-xs">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E5E5E5]">
                        @foreach($pastSchedules as $schedule)
                        @php
                            $isFinished = !is_null($schedule->finished_at);
                            $isStarted = !is_null($schedule->started_at);
                        @endphp
                        <tr class="hover:bg-[#F5F5F5]">
                            <td class="px-4 py-3 font-medium text-[#111111]">{{ $schedule->route->route_name }}</td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-500">
                                {{ $schedule->departure_date->format('d M Y') }} {{ $schedule->departure_time }}
                            </td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-400">{{ $schedule->vehicle->plate_number ?? '-' }}</td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-400">{{ $schedule->driver->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded-full text-[10px] font-mono uppercase tracking-wider border 
                                    {{ $isFinished ? 'bg-green-50 text-green-700 border-green-200' : ($isStarted ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-red-50 text-red-700 border-red-200') }}">
                                    {{ $isFinished ? '✅ Selesai' : ($isStarted ? '🚐 Jalan' : '⚠️ Terlewat') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('agency.schedules.show', $schedule) }}" class="text-gray-400 hover:underline text-xs font-medium">Detail</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @endif
</div>
@endsection