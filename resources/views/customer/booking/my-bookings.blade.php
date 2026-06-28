@extends('layouts.customer')

@section('title', 'Booking Saya')
@section('content')
<div class="container-custom py-8">
    <h1 class="text-2xl font-bold text-secondary mb-6">Booking Saya</h1>

    @if($bookings->isEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
        <div class="w-16 h-16 bg-primary-50 rounded-xl flex items-center justify-center mx-auto mb-4">
            <span class="text-2xl">🎫</span>
        </div>
        <p class="text-gray-500 text-lg mb-4">Belum ada booking.</p>
        <a href="{{ route('customer.search') }}" class="btn-primary">Cari Jadwal Travel</a>
    </div>
    @else
    <div class="space-y-4">
        @foreach($bookings as $booking)
        <a href="{{ route('customer.booking.show', $booking) }}" class="block bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition">
            <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <h3 class="font-bold text-lg text-secondary">{{ $booking->booking_code }}</h3>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                            @if($booking->status == 'paid') bg-green-100 text-green-700
                            @elseif($booking->status == 'pending') bg-yellow-100 text-yellow-700
                            @elseif($booking->status == 'cancelled') bg-red-100 text-red-700
                            @elseif($booking->status == 'completed') bg-blue-100 text-blue-700
                            @else bg-gray-100 text-gray-600 @endif">{{ $booking->status_label }}</span>
                    </div>
                    @if($booking->originStop && $booking->destinationStop)
                    <p class="text-gray-700 font-medium">{{ $booking->originStop->city_name }} → {{ $booking->destinationStop->city_name }}</p>
                    @endif
                    @if($booking->schedule)
                    <p class="text-sm text-gray-500 mt-1">📅 {{ $booking->schedule->departure_date->format('d M Y') }} | 🕐 {{ $booking->schedule->departure_time }} | 🏢 {{ $booking->schedule->agency->agency_name ?? '-' }}</p>
                    @endif
                    <p class="text-sm text-gray-500 mt-1">👥 {{ $booking->total_passengers }} penumpang</p>
                    @php $promoUsage = \App\Models\PromoUsage::where('booking_id', $booking->id)->first(); @endphp
                    @if($promoUsage && $promoUsage->discount_amount > 0)
                    <p class="text-xs text-purple-600 mt-1">Diskon Rp {{ number_format($promoUsage->discount_amount, 0, ',', '.') }}</p>
                    @endif
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-xl font-bold text-primary-600">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                    @if($booking->status == 'paid' || $booking->status == 'on_going')
                    <span class="inline-block mt-2 text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">Sudah Dibayar</span>
                    @elseif($booking->status == 'pending')
                    <span class="inline-block mt-2 text-xs text-yellow-600 bg-yellow-50 px-2 py-1 rounded-full">Menunggu Pembayaran</span>
                    @endif
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @endif
</div>
@endsection