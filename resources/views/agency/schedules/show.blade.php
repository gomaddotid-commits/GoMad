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
    }
@endphp

@if(!$schedule)
<div class="text-center py-12">
    <p class="text-gray-500 font-light">Jadwal tidak ditemukan.</p>
    <a href="{{ route('agency.schedules.index') }}" class="text-[#BA1826] hover:underline mt-2 inline-block">← Kembali</a>
</div>
@else

<div class="max-w-5xl mx-auto">
    {{-- Header & Aksi --}}
    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-6">
        <div>
            <a href="{{ route('agency.schedules.index') }}" class="text-[#BA1826] text-sm mb-2 inline-block hover:underline">← Kembali ke Daftar</a>
            <h1 class="text-2xl font-bold text-[#111827]">{{ $schedule->route->route_name }}</h1>
            <p class="text-gray-500 text-sm font-light">Jadwal #{{ $schedule->id }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(!$schedule->started_at && $schedule->departure_date->isToday() && $schedule->driver_id)
            <form action="{{ route('agency.schedules.start', $schedule) }}" method="POST">
                @csrf
                <button type="submit" class="bg-[#BA1826] text-white px-5 py-2 rounded-[10px] text-sm font-semibold hover:bg-[#8A0F18] transition" onclick="return confirm('Mulai jadwal?')">Mulai Jadwal</button>
            </form>
            @elseif($schedule->started_at && !$schedule->finished_at)
            <span class="bg-blue-50 text-blue-700 px-4 py-2 rounded-[10px] text-sm font-semibold inline-flex items-center border border-blue-200">Dalam Perjalanan</span>
            @elseif($schedule->finished_at)
            <span class="bg-green-50 text-green-700 px-4 py-2 rounded-[10px] text-sm font-semibold inline-flex items-center border border-green-200">Selesai {{ $schedule->finished_at->format('d M H:i') }}</span>
            @endif

            @if($canTransfer && !$schedule->started_at)
            <a href="{{ route('agency.schedules.transfer', $schedule) }}" class="bg-orange-500 text-white px-4 py-2 rounded-[10px] text-sm font-semibold hover:bg-orange-600 transition inline-flex items-center">Transfer</a>
            @endif

            @if(!$schedule->started_at)
            <button type="button" onclick="confirmDeleteSchedule()" 
                    class="bg-[#BA1826] text-white px-4 py-2 rounded-[10px] text-sm font-semibold hover:bg-[#8A0F18] transition inline-flex items-center">
                🗑️ Hapus Jadwal
            </button>
            <form id="deleteScheduleForm" action="{{ route('agency.schedules.destroy', $schedule) }}" method="POST" style="display:none;">
                @csrf
                @method('DELETE')
            </form>
            @endif
        </div>
    </div>

    @if(!$schedule->driver_id)
    <div class="bg-yellow-50 border border-yellow-200 rounded-[10px] p-3 mb-6 text-sm text-yellow-800 font-light">
        Belum ada driver. <button onclick="openAssignDriverModal()" class="text-[#BA1826] underline font-medium">Tugaskan driver</button>
    </div>
    @endif

    {{-- Ringkasan Jadwal --}}
    <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 mb-6 shadow-gomad">
        <h2 class="font-bold text-lg text-[#111827] mb-4">Ringkasan Jadwal</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3"><span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Tanggal</span><p class="font-bold text-[#111827]">{{ $schedule->departure_date->format('d M Y') }}</p></div>
            <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3"><span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Jam</span><p class="font-bold text-[#111827]">{{ $schedule->departure_time }}</p></div>
            <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3"><span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Kendaraan</span><p class="font-bold text-[#111827] font-mono">{{ $schedule->vehicle->plate_number ?? '-' }}</p></div>
            <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3"><span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Driver</span><p class="font-bold text-[#111827]">{{ $schedule->driver->name ?? 'Belum' }}</p></div>
        </div>
        <div class="mt-4">
            <div class="flex justify-between text-sm mb-1"><span class="text-gray-500 font-light">Okupansi</span><span class="font-semibold text-[#111827]">{{ $totalPassengers }}/{{ $schedule->max_capacity }} ({{ $occupancyRate }}%)</span></div>
            <div class="bg-[#E5E7EB] rounded-full h-3 overflow-hidden">
                <div class="h-full rounded-full {{ $occupancyRate >= 80 ? 'bg-[#BA1826]' : ($occupancyRate >= 50 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min($occupancyRate, 100) }}%"></div>
            </div>
        </div>
    </div>

    {{-- Metode Pembayaran --}}
    <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 mb-6 shadow-gomad">
        <h2 class="font-bold text-lg text-[#111827] mb-4">Metode Pembayaran Tersedia</h2>
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
                <p class="text-xs text-orange-500 mt-1 font-light">Min deposit: Rp {{ number_format($schedule->cod_min_balance, 0, ',', '.') }}</p>
                @else
                <p class="text-xs text-gray-400 mt-1 font-light">Tidak diaktifkan</p>
                @endif
            </div>
        </div>
        
        @if(!$schedule->allow_cod && $schedule->route->cod_available)
        <div class="mt-3 text-xs text-gray-500 text-center font-light">
            COD belum diaktifkan untuk jadwal ini. 
            @if($schedule->departure_date->isFuture())
            <span class="text-gray-400">Fitur ini bisa diaktifkan saat membuat jadwal.</span>
            @endif
        </div>
        @endif
    </div>

    {{-- Daftar Penumpang --}}
    <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 mb-6 shadow-gomad">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold text-lg text-[#111827]">Penumpang ({{ $bookings->count() }} booking, {{ $totalPassengers }} orang)</h2>
            <span class="text-sm text-gray-500 font-light">Revenue: <strong class="text-[#BA1826]">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</strong></span>
        </div>

        @if($bookings->isEmpty())
        <p class="text-gray-500 text-center py-8 font-light">Belum ada penumpang.</p>
        @else
        <div class="space-y-4">
            @foreach($bookings as $booking)
            <div class="border border-[#E5E7EB] rounded-[12px] p-4">
                <div class="flex flex-col md:flex-row md:justify-between gap-3 mb-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-bold text-lg font-mono text-[#111827]">{{ $booking->booking_code }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                @if($booking->status == 'paid') bg-green-50 text-green-700 border-green-200
                                @elseif($booking->status == 'pending') bg-yellow-50 text-yellow-700 border-yellow-200
                                @elseif($booking->status == 'on_going') bg-indigo-50 text-indigo-700 border-indigo-200
                                @elseif($booking->status == 'completed') bg-green-50 text-green-700 border-green-200
                                @else bg-[#F9FAFB] text-gray-600 border-[#E5E7EB] @endif">{{ $booking->status_label }}</span>
                            
                            @if($booking->payment)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                @if($booking->payment->payment_type == 'midtrans') bg-blue-50 text-blue-700 border-blue-200
                                @elseif($booking->payment->payment_type == 'cash') bg-green-50 text-green-700 border-green-200
                                @elseif($booking->payment->payment_type == 'cod') bg-orange-50 text-orange-700 border-orange-200
                                @endif">
                                @if($booking->payment->payment_type == 'midtrans') Online
                                @elseif($booking->payment->payment_type == 'cash') Warung
                                @elseif($booking->payment->payment_type == 'cod') COD
                                @endif
                                - {{ $booking->payment->status_label }}
                            </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mt-1 font-light">{{ $booking->originStop->city_name ?? '?' }} → {{ $booking->destinationStop->city_name ?? '?' }}</p>
                        <p class="text-xs text-gray-500 font-light">{{ $booking->customer->name ?? '?' }} • {{ $booking->customer->phone ?? '?' }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-bold text-[#BA1826] font-mono">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500 font-light">{{ $booking->total_passengers }} penumpang</p>
                    </div>
                </div>
                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                    <table class="w-full text-sm">
                        <thead><tr class="text-gray-400 text-[10px] font-mono uppercase tracking-wider"><th class="pb-2 text-left">#</th><th class="pb-2 text-left">Nama</th><th class="pb-2 text-left">Telepon</th><th class="pb-2 text-center">Jemput</th><th class="pb-2 text-center">Turun</th></tr></thead>
                        <tbody>
                            @foreach($booking->passengers as $p)
                            <tr class="border-t border-[#E5E7EB]">
                                <td class="py-2 text-gray-400 font-mono text-xs">Seat {{ $p->seat_number }}</td>
                                <td class="py-2 font-medium text-[#111827]">{{ $p->passenger_name }}</td>
                                <td class="py-2 text-gray-600 font-light">{{ $p->passenger_phone ?? '-' }}</td>
                                <td class="py-2 text-center font-mono text-xs">{{ $p->picked_up_at ? '✅ '.$p->picked_up_at->format('H:i') : '-' }}</td>
                                <td class="py-2 text-center font-mono text-xs">{{ $p->dropped_off_at ? '✅ '.$p->dropped_off_at->format('H:i') : '-' }}</td>
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
                <button type="submit" class="flex-1 btn-gomad-primary">Simpan</button>
                <button type="button" onclick="closeAssignDriverModal()" class="flex-1 border border-[#E5E7EB] py-2 rounded-[10px]">Batal</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openAssignDriverModal() { 
    document.getElementById('assignDriverModal').style.display = 'flex'; 
}

function closeAssignDriverModal() { 
    document.getElementById('assignDriverModal').style.display = 'none'; 
}

function confirmDeleteSchedule() {
    if (confirm('Hapus jadwal ini?\n\nData tidak bisa dikembalikan.\nJika jadwal menggunakan COD, saldo deposit akan dikembalikan.')) {
        document.getElementById('deleteScheduleForm').submit();
    }
}

document.getElementById('assignDriverModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAssignDriverModal();
    }
});
</script>
@endpush
@endif
@endsection