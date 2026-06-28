@extends('layouts.agency')

@section('title', 'Transfer Penumpang')
@section('content')
@php
    $availableSchedules = $availableSchedules ?? collect();
    $selectedBookings = session('selectedBookings') ?? $selectedBookings ?? [];
    $selectedBookingModels = !empty($selectedBookings) 
        ? \App\Models\Booking::whereIn('id', $selectedBookings)->with(['originStop', 'destinationStop', 'passengers', 'customer'])->get()
        : collect();
@endphp

<div class="max-w-5xl mx-auto">
    <a href="{{ route('agency.transfers.index') }}" class="text-primary text-sm mb-4 inline-block">← Kembali ke Transfer</a>
    
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Transfer Penumpang</h1>
    <p class="text-gray-500 mb-6">Pindahkan penumpang dari jadwal yang sepi ke jadwal lain</p>

    <!-- Jadwal Asal -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h2 class="font-bold text-lg mb-3">📤 Jadwal Asal</h2>
        <div class="bg-red-50 rounded-lg p-4">
            <div class="flex justify-between">
                <div>
                    <p class="font-bold text-lg">{{ $schedule->route->route_name }}</p>
                    <p class="text-sm text-gray-600">{{ $schedule->departure_date->format('d M Y') }} | {{ $schedule->departure_time }}</p>
                    <p class="text-sm text-gray-600">{{ $schedule->vehicle->plate_number ?? '-' }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Terisi</p>
                    <p class="text-2xl font-bold {{ $schedule->occupancy_rate < 50 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $schedule->occupancy_rate }}%
                    </p>
                    <p class="text-xs text-gray-500">{{ $schedule->bookings->whereNotIn('status', ['cancelled'])->sum('total_passengers') }}/{{ $schedule->max_capacity }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pilih Booking -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h2 class="font-bold text-lg mb-3">👥 Pilih Penumpang yang Akan Ditransfer</h2>
        
        <form action="{{ route('agency.schedules.transfer.search', $schedule) }}" method="POST">
            @csrf
            <div class="space-y-3 mb-4">
                @foreach($bookings as $booking)
                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" name="booking_ids[]" value="{{ $booking->id }}" 
                           class="w-5 h-5 text-primary rounded mr-3"
                           {{ in_array($booking->id, old('booking_ids', $selectedBookings)) ? 'checked' : '' }}>
                    <div class="flex-1">
                        <div class="flex justify-between">
                            <span class="font-semibold">{{ $booking->booking_code }}</span>
                            <span class="text-sm text-gray-500">{{ $booking->total_passengers }} pax</span>
                        </div>
                        <p class="text-sm text-gray-600">{{ $booking->originStop->city_name }} → {{ $booking->destinationStop->city_name }}</p>
                        <p class="text-xs text-gray-500">{{ $booking->customer->name }} • Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                    </div>
                </label>
                @endforeach
            </div>
            
            <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg font-semibold hover:bg-primary-dark">
                🔍 CARI MOBIL TUJUAN
            </button>
        </form>
    </div>

    <!-- Hasil Pencarian Mobil Tujuan -->
    @if($availableSchedules->isNotEmpty())
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <h2 class="font-bold text-lg mb-3">🚐 Mobil Tersedia untuk Transfer</h2>
        <p class="text-sm text-gray-500 mb-4">Ditemukan {{ $availableSchedules->count() }} mobil yang bisa menerima transfer</p>
        
        <div class="space-y-4">
            @foreach($availableSchedules as $targetSchedule)
            <div class="border rounded-xl p-4 hover:shadow-md transition">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold">{{ $targetSchedule->agency->agency_name }}</h3>
                        <p class="text-sm text-gray-600">{{ $targetSchedule->route->route_name }}</p>
                        <p class="text-sm text-gray-500">
                            🕐 {{ $targetSchedule->departure_time }} | 
                            🚐 {{ $targetSchedule->vehicle->plate_number ?? '-' }} |
                            💺 {{ $targetSchedule->available_seats }} kursi kosong
                        </p>
                        <p class="text-sm mt-1">
                            💰 Biaya transfer: <strong>Rp {{ number_format($targetSchedule->transfer_fee_per_passenger, 0, ',', '.') }}/penumpang</strong>
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="text-yellow-400">⭐ {{ number_format($targetSchedule->agency->rating, 1) }}</span>
                        
                        <form action="{{ route('agency.schedules.transfer.request') }}" method="POST" class="mt-2">
                            @csrf
                            <input type="hidden" name="from_schedule_id" value="{{ $schedule->id }}">
                            <input type="hidden" name="to_schedule_id" value="{{ $targetSchedule->id }}">
                            @foreach($selectedBookings as $bid)
                            <input type="hidden" name="booking_ids[]" value="{{ $bid }}">
                            @endforeach
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700">
                                ✅ PILIH MOBIL INI
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @elseif(request()->isMethod('post') || session('availableSchedules'))
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
        <p class="text-yellow-800">Tidak ada mobil tersedia untuk transfer.</p>
        <p class="text-sm text-yellow-600">Pastikan ada jadwal lain yang searah, tanggal sama, dan masih ada kursi kosong.</p>
    </div>
    @endif
</div>
@endsection