@extends('layouts.customer')

@section('title', 'E-Ticket')
@section('content')
<div class="px-4 py-8">
    <a href="{{ route('customer.booking.show', $booking) }}" class="text-primary-600 text-sm font-medium mb-4 inline-block hover:underline">← Kembali</a>

    <div class="bg-white rounded-2xl shadow-sm border-2 border-primary-600 p-6">
        <!-- Header -->
        <div class="text-center border-b-2 border-dashed border-gray-200 pb-4 mb-4">
            <h2 class="text-2xl font-bold text-primary-600">GoMad</h2>
            <p class="text-sm text-gray-500 mt-1">E-Ticket - Booking {{ $booking->booking_code }}</p>
        </div>

        <!-- Info Utama -->
        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Kode Booking</span>
                <span class="font-bold text-secondary">{{ $booking->booking_code }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Rute</span>
                <span class="font-semibold text-secondary">{{ $booking->originStop->city_name }} → {{ $booking->destinationStop->city_name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Tanggal</span>
                <span class="font-semibold text-secondary">{{ $booking->schedule->departure_date->format('d M Y') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Jam</span>
                <span class="font-semibold text-secondary">{{ $booking->schedule->departure_time }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Agency</span>
                <span class="font-semibold text-secondary">{{ $booking->schedule->agency->agency_name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Kendaraan</span>
                <span class="font-semibold text-secondary">{{ $booking->schedule->vehicle->brand }} {{ $booking->schedule->vehicle->model }} ({{ $booking->schedule->vehicle->plate_number }})</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Total</span>
                <span class="font-bold text-primary-600 text-base">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Penumpang -->
        <div class="border-t border-dashed border-gray-200 pt-4 mt-4">
            <h4 class="font-bold text-secondary mb-2">Penumpang</h4>
            @foreach($booking->passengers as $p)
            <div class="flex justify-between text-sm py-1 border-b border-gray-100 last:border-0">
                <span class="text-secondary">{{ $p->passenger_name }}</span>
                <span class="text-gray-500">Seat {{ $p->seat_number }}</span>
            </div>
            @endforeach
        </div>

        <!-- Alamat -->
        <div class="border-t border-dashed border-gray-200 pt-4 mt-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500 text-xs">📍 Jemput</span>
                    <p class="font-medium text-secondary mt-0.5">{{ $booking->pickup_address }}</p>
                </div>
                <div>
                    <span class="text-gray-500 text-xs">🎯 Tujuan</span>
                    <p class="font-medium text-secondary mt-0.5">{{ $booking->destination_address }}</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="border-t-2 border-dashed border-gray-200 pt-4 mt-4 text-center">
            <p class="text-xs text-gray-400">E-Ticket ini adalah bukti booking resmi GoMad</p>
            <p class="text-xs text-gray-400">Dicetak: {{ now()->format('d M Y H:i') }}</p>
        </div>
    </div>

    <div class="text-center mt-6">
        <button onclick="window.print()" class="bg-primary-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-primary-700 transition active:scale-95">
            🖨️ CETAK E-TICKET
        </button>
    </div>
</div>
@endsection