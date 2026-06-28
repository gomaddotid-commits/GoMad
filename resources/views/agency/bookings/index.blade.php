@extends('layouts.agency')

@section('title', 'Booking')
@section('content')
<!-- File: resources/views/agency/bookings/index.blade.php -->

<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Daftar Booking</h1>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">Kode</th>
                    <th class="px-4 py-3 text-left">Customer</th>
                    <th class="px-4 py-3 text-left">Rute</th>
                    <th class="px-4 py-3 text-center">Penumpang</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                <tr class="border-t">
                    <td class="px-4 py-3 font-mono text-xs">{{ $booking->booking_code }}</td>
                    <td class="px-4 py-3 text-sm">{{ $booking->customer->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm">{{ $booking->originStop->city_name ?? '?' }} → {{ $booking->destinationStop->city_name ?? '?' }}</td>
                    <td class="px-4 py-3 text-center">{{ $booking->total_passengers }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded text-xs 
                            @if($booking->status == 'paid') bg-green-100 text-green-800
                            @elseif($booking->status == 'pending') bg-yellow-100 text-yellow-800
                            @elseif($booking->status == 'cancelled') bg-red-100 text-red-800
                            @else bg-blue-100 text-blue-800 @endif">
                            {{ $booking->status_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('agency.bookings.show', $booking) }}" class="text-primary hover:underline text-sm">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Belum ada booking.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $bookings->links() }}</div>
</div>
@endsection