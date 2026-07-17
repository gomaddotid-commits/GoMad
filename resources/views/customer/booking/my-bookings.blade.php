@extends('layouts.customer')

@section('title', 'Booking Saya')
@section('content')
<div class="container-magazine py-8">
    <h1 class="text-2xl font-bold text-[#111827] mb-6">Booking Saya</h1>

    @if($bookings->isEmpty())
    <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-12 text-center shadow-gomad">
        <div class="w-16 h-16 bg-[#F9FAFB] rounded-[10px] flex items-center justify-center mx-auto mb-4 border border-[#E5E7EB]">
            <span class="text-2xl">🎫</span>
        </div>
        <p class="text-gray-500 text-lg font-light mb-4">Belum ada booking.</p>
        <a href="{{ route('customer.search') }}" class="btn-gomad-primary inline-block">Cari Jadwal Travel</a>
    </div>
    @else
    <div class="space-y-4">
        @foreach($bookings as $booking)
        <a href="{{ route('customer.booking.show', $booking) }}" class="block bg-white border border-[#E5E7EB] rounded-[12px] p-5 shadow-gomad hover:border-[#BA1826] transition-colors">
            <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <h3 class="font-bold text-[#111827] font-mono text-lg">{{ $booking->booking_code }}</h3>
                        @if($booking->status == 'paid' || $booking->status == 'on_going')
                        <span class="text-[10px] font-mono uppercase tracking-wider text-green-600 bg-green-50 px-2 py-1 rounded-full border border-green-200">Sudah Dibayar</span>
                        @elseif($booking->status == 'confirmed' && $booking->payment && $booking->payment->payment_type == 'cod')
                        <span class="text-[10px] font-mono uppercase tracking-wider text-orange-600 bg-orange-50 px-2 py-1 rounded-full border border-orange-200">COD</span>
                        @elseif($booking->status == 'confirmed')
                        <span class="text-[10px] font-mono uppercase tracking-wider text-blue-600 bg-blue-50 px-2 py-1 rounded-full border border-blue-200">Terkonfirmasi</span>
                        @elseif($booking->status == 'pending')
                        <span class="text-[10px] font-mono uppercase tracking-wider text-yellow-600 bg-yellow-50 px-2 py-1 rounded-full border border-yellow-200">Menunggu</span>
                        @endif
                    </div>
                    @if($booking->originStop && $booking->destinationStop)
                    <p class="text-[#111827] font-medium">{{ $booking->originStop->city_name }} → {{ $booking->destinationStop->city_name }}</p>
                    @endif
                    @if($booking->schedule)
                    <p class="text-sm text-gray-500 mt-1 font-light">📅 {{ $booking->schedule->departure_date->format('d M Y') }} | 🕐 {{ $booking->schedule->departure_time }} | 🏢 {{ $booking->schedule->agency->agency_name ?? '-' }}</p>
                    @endif
                    <p class="text-sm text-gray-500 mt-1 font-light">👥 {{ $booking->total_passengers }} penumpang</p>
                    @php $promoUsage = \App\Models\PromoUsage::where('booking_id', $booking->id)->first(); @endphp
                    @if($promoUsage && $promoUsage->discount_amount > 0)
                    <p class="text-xs text-[#BA1826] font-mono uppercase tracking-wider mt-1">Diskon Rp {{ number_format($promoUsage->discount_amount, 0, ',', '.') }}</p>
                    @endif
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="text-xl font-bold text-[#BA1826] font-mono">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @endif
</div>
@endsection