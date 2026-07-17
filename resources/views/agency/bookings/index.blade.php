@extends('layouts.agency')

@section('title', 'Booking')
@section('content')
<div>
    <h1 class="text-2xl font-bold text-[#111827] mb-6">Daftar Booking</h1>

    <div class="bg-white border border-[#E5E7EB] rounded-[12px] shadow-gomad overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F9FAFB] border-b border-[#E5E7EB]">
                    <tr>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Kode</th>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Customer</th>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Rute</th>
                        <th class="px-4 py-3 text-center font-mono uppercase tracking-wider text-xs text-gray-500">Penumpang</th>
                        <th class="px-4 py-3 text-center font-mono uppercase tracking-wider text-xs text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-xs text-gray-500">Total</th>
                        <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-xs text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $booking)
                    <tr class="border-t border-[#E5E7EB] hover:bg-[#F9FAFB]">
                        <td class="px-4 py-3 font-mono text-xs text-[#111827]">{{ $booking->booking_code }}</td>
                        <td class="px-4 py-3 text-sm text-[#111827]">{{ $booking->customer->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600 font-light">{{ $booking->originStop->city_name ?? '?' }} → {{ $booking->destinationStop->city_name ?? '?' }}</td>
                        <td class="px-4 py-3 text-center font-mono text-[#111827]">{{ $booking->total_passengers }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                @if($booking->status == 'paid') bg-green-50 text-green-700 border-green-200
                                @elseif($booking->status == 'pending') bg-yellow-50 text-yellow-700 border-yellow-200
                                @elseif($booking->status == 'cancelled') bg-red-50 text-red-700 border-red-200
                                @else bg-blue-50 text-blue-700 border-blue-200 @endif">
                                {{ $booking->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-[#BA1826]">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('agency.bookings.show', $booking) }}" class="text-[#BA1826] hover:underline text-sm font-medium">Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500 font-light">Belum ada booking.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $bookings->links() }}</div>
</div>
@endsection