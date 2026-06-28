@extends('layouts.agency')

@section('title', 'Detail Booking')
@section('content')
<!-- File: resources/views/agency/bookings/show.blade.php -->

<div class="max-w-4xl mx-auto">
    <a href="{{ route('agency.bookings.index') }}" class="text-primary text-sm mb-4 inline-block">← Kembali</a>

    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-2xl font-bold">{{ $booking->booking_code }}</h1>
                <span class="px-2 py-1 rounded text-xs 
                    @if($booking->status == 'paid') bg-green-100 text-green-800
                    @elseif($booking->status == 'pending') bg-yellow-100 text-yellow-800
                    @else bg-gray-100 text-gray-800 @endif">
                    {{ $booking->status_label }}
                </span>
            </div>
            <p class="text-2xl font-bold text-primary">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
        </div>

        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div><span class="font-medium">Customer:</span> {{ $booking->customer->name ?? '-' }} ({{ $booking->customer->phone ?? '-' }})</div>
            <div><span class="font-medium">Rute:</span> {{ $booking->originStop->city_name }} → {{ $booking->destinationStop->city_name }}</div>
            <div><span class="font-medium">Tanggal:</span> {{ $booking->schedule->departure_date->format('d M Y') }} {{ $booking->schedule->departure_time }}</div>
            <div><span class="font-medium">Kendaraan:</span> {{ $booking->schedule->vehicle->plate_number ?? '-' }}</div>
            <div><span class="font-medium">Jemput:</span> {{ $booking->pickup_address }}</div>
            <div><span class="font-medium">Tujuan:</span> {{ $booking->destination_address }}</div>
        </div>

        <h3 class="font-bold mt-6 mb-2">Penumpang ({{ $booking->total_passengers }})</h3>
        @foreach($booking->passengers as $p)
        <div class="flex justify-between text-sm py-1 border-b">
            <span>{{ $p->passenger_name }} (Seat {{ $p->seat_number }})</span>
            <span class="text-gray-500">{{ $p->passenger_phone }}</span>
        </div>
        @endforeach

        <!-- Payment Info -->
        @if($booking->payment)
        <div class="mt-4 p-3 rounded text-sm 
            @if($booking->payment->status == 'paid') bg-green-50
            @elseif($booking->payment->status == 'pending') bg-yellow-50
            @else bg-gray-50 @endif">
            <span class="font-medium">Pembayaran:</span> {{ $booking->payment->status_label }} 
            @if($booking->payment->payment_type == 'cash' && $booking->cashPayment)
            (Cash - {{ $booking->cashPayment->payment_code }})
            @endif
        </div>
        @endif
    </div>
</div>
@endsection