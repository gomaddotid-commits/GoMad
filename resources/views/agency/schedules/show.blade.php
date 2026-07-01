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
    <p class="text-gray-500">Jadwal tidak ditemukan.</p>
    <a href="{{ route('agency.schedules.index') }}" class="text-primary-600 hover:underline mt-2 inline-block">← Kembali</a>
</div>
@else

<div class="max-w-5xl mx-auto">
    {{-- Header & Aksi --}}
    <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-6">
        <div>
            <a href="{{ route('agency.schedules.index') }}" class="text-primary-600 text-sm mb-2 inline-block">← Kembali ke Daftar</a>
            <h1 class="text-2xl font-bold text-secondary">{{ $schedule->route->route_name }}</h1>
            <p class="text-gray-500 text-sm">Jadwal #{{ $schedule->id }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            {{-- Tombol Mulai Jadwal --}}
            @if(!$schedule->started_at && $schedule->departure_date->isToday() && $schedule->driver_id)
            <form action="{{ route('agency.schedules.start', $schedule) }}" method="POST">
                @csrf
                <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-green-700 transition" onclick="return confirm('Mulai jadwal?')">Mulai Jadwal</button>
            </form>
            @elseif($schedule->started_at && !$schedule->finished_at)
            <span class="bg-blue-100 text-blue-700 px-4 py-2 rounded-xl text-sm font-semibold inline-flex items-center">Dalam Perjalanan</span>
            @elseif($schedule->finished_at)
            <span class="bg-green-100 text-green-700 px-4 py-2 rounded-xl text-sm font-semibold inline-flex items-center">Selesai {{ $schedule->finished_at->format('d M H:i') }}</span>
            @endif

            {{-- Tombol Transfer --}}
            @if($canTransfer && !$schedule->started_at)
            <a href="{{ route('agency.schedules.transfer', $schedule) }}" class="bg-orange-500 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-orange-600 transition inline-flex items-center">Transfer</a>
            @endif

            {{-- Tombol Hapus Jadwal --}}
            @if(!$schedule->started_at)
            <button type="button" onclick="confirmDeleteSchedule()" 
                    class="bg-red-500 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-red-600 transition inline-flex items-center">
                🗑️ Hapus Jadwal
            </button>
            <form id="deleteScheduleForm" action="{{ route('agency.schedules.destroy', $schedule) }}" method="POST" style="display:none;">
                @csrf
                @method('DELETE')
            </form>
            @endif
        </div>
    </div>

    {{-- Peringatan driver --}}
    @if(!$schedule->driver_id)
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-3 mb-6 text-sm text-yellow-800">
        Belum ada driver. <button onclick="openAssignDriverModal()" class="text-primary-600 underline font-medium">Tugaskan driver</button>
    </div>
    @endif

    {{-- Ringkasan Jadwal --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h2 class="font-bold text-lg text-secondary mb-4">Ringkasan Jadwal</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 rounded-xl p-3"><span class="text-xs text-gray-500">Tanggal</span><p class="font-bold">{{ $schedule->departure_date->format('d M Y') }}</p></div>
            <div class="bg-gray-50 rounded-xl p-3"><span class="text-xs text-gray-500">Jam</span><p class="font-bold">{{ $schedule->departure_time }}</p></div>
            <div class="bg-gray-50 rounded-xl p-3"><span class="text-xs text-gray-500">Kendaraan</span><p class="font-bold">{{ $schedule->vehicle->plate_number ?? '-' }}</p></div>
            <div class="bg-gray-50 rounded-xl p-3"><span class="text-xs text-gray-500">Driver</span><p class="font-bold">{{ $schedule->driver->name ?? 'Belum' }}</p></div>
        </div>
        <div class="mt-4">
            <div class="flex justify-between text-sm mb-1"><span class="text-gray-500">Okupansi</span><span class="font-semibold">{{ $totalPassengers }}/{{ $schedule->max_capacity }} ({{ $occupancyRate }}%)</span></div>
            <div class="bg-gray-200 rounded-full h-3 overflow-hidden">
                <div class="h-full rounded-full {{ $occupancyRate >= 80 ? 'bg-red-500' : ($occupancyRate >= 50 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min($occupancyRate, 100) }}%"></div>
            </div>
        </div>
    </div>

    {{-- Metode Pembayaran Tersedia --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h2 class="font-bold text-lg text-secondary mb-4">Metode Pembayaran Tersedia</h2>
        <div class="grid grid-cols-3 gap-4">
            {{-- Online --}}
            <div class="bg-blue-50 rounded-xl p-4 text-center border border-blue-200">
                <div class="text-2xl mb-2">💳</div>
                <p class="font-semibold text-blue-700 text-sm">Online (Midtrans)</p>
                <p class="text-xs text-blue-600 mt-1">Selalu tersedia</p>
            </div>
            
            {{-- Warung GoMad --}}
            <div class="bg-green-50 rounded-xl p-4 text-center border border-green-200">
                <div class="text-2xl mb-2">🏪</div>
                <p class="font-semibold text-green-700 text-sm">Warung GoMad</p>
                <p class="text-xs text-green-600 mt-1">Selalu tersedia</p>
            </div>
            
            {{-- COD --}}
            <div class="rounded-xl p-4 text-center border {{ $schedule->allow_cod ? 'bg-orange-50 border-orange-200' : 'bg-gray-50 border-gray-200' }}">
                <div class="text-2xl mb-2">🚗</div>
                <p class="font-semibold text-sm {{ $schedule->allow_cod ? 'text-orange-700' : 'text-gray-400' }}">
                    COD (Bayar ke Sopir)
                </p>
                @if($schedule->allow_cod)
                <p class="text-xs text-orange-600 mt-1">Tersedia</p>
                <p class="text-xs text-orange-500 mt-1">Min deposit: Rp {{ number_format($schedule->cod_min_balance, 0, ',', '.') }}</p>
                @else
                <p class="text-xs text-gray-400 mt-1">Tidak diaktifkan</p>
                @endif
            </div>
        </div>
        
        @if(!$schedule->allow_cod && $schedule->route->cod_available)
        <div class="mt-3 text-xs text-gray-500 text-center">
            COD belum diaktifkan untuk jadwal ini. 
            @if($schedule->departure_date->isFuture())
            <span class="text-gray-400">Fitur ini bisa diaktifkan saat membuat jadwal.</span>
            @endif
        </div>
        @endif
    </div>

    {{-- Daftar Penumpang --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold text-lg text-secondary">Penumpang ({{ $bookings->count() }} booking, {{ $totalPassengers }} orang)</h2>
            <span class="text-sm text-gray-500">Revenue: <strong class="text-primary-600">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</strong></span>
        </div>

        @if($bookings->isEmpty())
        <p class="text-gray-500 text-center py-8">Belum ada penumpang.</p>
        @else
        <div class="space-y-4">
            @foreach($bookings as $booking)
            <div class="border rounded-xl p-4">
                <div class="flex flex-col md:flex-row md:justify-between gap-3 mb-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-bold text-lg">{{ $booking->booking_code }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                @if($booking->status == 'paid') bg-green-100 text-green-700
                                @elseif($booking->status == 'pending') bg-yellow-100 text-yellow-700
                                @elseif($booking->status == 'on_going') bg-indigo-100 text-indigo-700
                                @elseif($booking->status == 'completed') bg-green-100 text-green-700
                                @else bg-gray-100 text-gray-600 @endif">{{ $booking->status_label }}</span>
                            
                            {{-- BADGE METODE PEMBAYARAN --}}
                            @if($booking->payment)
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                @if($booking->payment->payment_type == 'midtrans') bg-blue-100 text-blue-700
                                @elseif($booking->payment->payment_type == 'cash') bg-green-100 text-green-700
                                @elseif($booking->payment->payment_type == 'cod') bg-orange-100 text-orange-700
                                @endif">
                                @if($booking->payment->payment_type == 'midtrans') Online
                                @elseif($booking->payment->payment_type == 'cash') Warung
                                @elseif($booking->payment->payment_type == 'cod') COD
                                @endif
                                - {{ $booking->payment->status_label }}
                            </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mt-1">{{ $booking->originStop->city_name ?? '?' }} → {{ $booking->destinationStop->city_name ?? '?' }}</p>
                        <p class="text-xs text-gray-500">{{ $booking->customer->name ?? '?' }} • {{ $booking->customer->phone ?? '?' }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-bold text-primary-600">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-500">{{ $booking->total_passengers }} penumpang</p>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <table class="w-full text-sm">
                        <thead><tr class="text-gray-500 text-xs"><th class="pb-2 text-left">#</th><th class="pb-2 text-left">Nama</th><th class="pb-2 text-left">Telepon</th><th class="pb-2 text-center">Jemput</th><th class="pb-2 text-center">Turun</th></tr></thead>
                        <tbody>
                            @foreach($booking->passengers as $p)
                            <tr class="border-t border-gray-200">
                                <td class="py-2 text-gray-400">Seat {{ $p->seat_number }}</td>
                                <td class="py-2 font-medium">{{ $p->passenger_name }}</td>
                                <td class="py-2 text-gray-600">{{ $p->passenger_phone ?? '-' }}</td>
                                <td class="py-2 text-center">{{ $p->picked_up_at ? '✅ '.$p->picked_up_at->format('H:i') : '-' }}</td>
                                <td class="py-2 text-center">{{ $p->dropped_off_at ? '✅ '.$p->dropped_off_at->format('H:i') : '-' }}</td>
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
<div id="assignDriverModal" style="display:none;" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 max-w-md w-full">
        <h3 class="font-bold text-lg mb-4">Tugaskan Driver</h3>
        <form action="{{ route('agency.schedules.assign-driver', $schedule) }}" method="POST">
            @csrf
            <select name="driver_id" class="w-full px-4 py-3 border rounded-xl mb-4 bg-gray-50" required>
                <option value="">Pilih Driver</option>
                @foreach(auth()->user()->agency->drivers()->where('is_active', true)->get() as $driver)
                <option value="{{ $driver->id }}">{{ $driver->name }} ({{ $driver->phone }})</option>
                @endforeach
            </select>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 btn-primary">Simpan</button>
                <button type="button" onclick="closeAssignDriverModal()" class="flex-1 border py-2 rounded-xl">Batal</button>
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

// 👇 Fungsi untuk konfirmasi dan submit form hapus
function confirmDeleteSchedule() {
    if (confirm('Hapus jadwal ini?\n\nData tidak bisa dikembalikan.\nJika jadwal menggunakan COD, saldo deposit akan dikembalikan.')) {
        document.getElementById('deleteScheduleForm').submit();
    }
}

// Tutup modal assign driver saat klik di luar
document.getElementById('assignDriverModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAssignDriverModal();
    }
});
</script>
@endpush
@endif
@endsection