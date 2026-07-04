@extends('layouts.admin')

@section('title', 'Detail Booking')
@section('content')
<!-- File: resources/views/admin/bookings/show.blade.php -->
<!-- Deskripsi: Halaman detail booking admin -->

<div class="max-w-4xl mx-auto">
    <a href="{{ route('admin.bookings.index') }}" class="text-primary text-sm mb-4 inline-block">← Kembali</a>

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
            <div><span class="font-medium">Agency:</span> {{ $booking->schedule->agency->agency_name ?? '-' }}</div>
            <div><span class="font-medium">Rute:</span> {{ $booking->originStop->city_name }} → {{ $booking->destinationStop->city_name }}</div>
            <div><span class="font-medium">Tanggal:</span> {{ $booking->schedule->departure_date->format('d M Y') }} {{ $booking->schedule->departure_time }}</div>
            <div><span class="font-medium">Kendaraan:</span> {{ $booking->schedule->vehicle->plate_number ?? '-' }}</div>
            <div><span class="font-medium">Driver:</span> {{ $booking->schedule->driver->name ?? '-' }}</div>
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
    </div>

    {{-- Refund Status Section --}}
    @if($booking->payment)
        {{-- Refund Pending Approval --}}
        @if($booking->payment->status === 'refund_pending')
        <div class="bg-yellow-50 border-2 border-yellow-300 rounded-2xl p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-yellow-800 text-lg">💰 Refund Menunggu Approval</h3>
                    @php $refundData = $booking->payment->payment_detail['refund'] ?? []; @endphp
                    <p class="text-sm text-yellow-700 mt-2">
                        Jumlah refund: <strong>Rp {{ number_format($refundData['amount'] ?? 0, 0, ',', '.') }}</strong>
                    </p>
                    <p class="text-sm text-yellow-700">
                        Biaya pembatalan: <strong>Rp {{ number_format($refundData['cancellation_fee'] ?? 0, 0, ',', '.') }}</strong>
                    </p>
                    <p class="text-xs text-yellow-600 mt-1">
                        Diminta: {{ \Carbon\Carbon::parse($refundData['requested_at'] ?? now())->format('d M Y H:i') }}
                    </p>
                </div>
                <div class="flex gap-3">
                    <form action="{{ route('admin.refund.approve', $booking) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-green-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-green-600 transition">
                            ✅ Setujui Refund
                        </button>
                    </form>
                    <button onclick="openRejectRefundModal()" class="bg-red-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-red-600 transition">
                        ❌ Tolak Refund
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal Tolak Refund --}}
        <div id="rejectRefundModal" style="display:none;" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-md w-full">
                <h3 class="font-bold text-lg mb-2">Tolak Refund</h3>
                <p class="text-sm text-gray-500 mb-4">Tulis alasan penolakan refund untuk booking {{ $booking->booking_code }}</p>
                <form action="{{ route('admin.refund.reject', $booking) }}" method="POST">
                    @csrf
                    <textarea name="reason" rows="3" class="w-full px-4 py-3 border rounded-xl mb-4 focus:ring-2 focus:ring-red-500" placeholder="Alasan penolakan..." required></textarea>
                    <div class="flex gap-3">
                        <button type="submit" class="flex-1 bg-red-500 text-white py-2 rounded-xl font-semibold hover:bg-red-600">Tolak Refund</button>
                        <button type="button" onclick="document.getElementById('rejectRefundModal').style.display='none'" class="flex-1 border py-2 rounded-xl">Batal</button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- Refund History --}}
        @if(in_array($booking->payment->status, ['refunded', 'refund_approved', 'refund_rejected']))
        <div class="bg-gray-50 rounded-xl p-4 mb-6">
            <h3 class="font-bold text-secondary mb-3">Status Refund</h3>
            @php $refund = $booking->payment->payment_detail['refund'] ?? []; @endphp
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>
                    <span class="font-semibold">{{ $booking->payment->status_label }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Jumlah</span>
                    <span class="font-semibold">Rp {{ number_format($refund['refund_amount'] ?? ($refund['amount'] ?? 0), 0, ',', '.') }}</span>
                </div>
                @if(isset($refund['approved_by_name']))
                <div class="flex justify-between">
                    <span class="text-gray-500">Disetujui oleh</span>
                    <span class="font-semibold">{{ $refund['approved_by_name'] }}</span>
                </div>
                @endif
                @if(isset($refund['rejected_by_name']))
                <div class="flex justify-between">
                    <span class="text-gray-500">Ditolak oleh</span>
                    <span class="font-semibold text-red-600">{{ $refund['rejected_by_name'] }}</span>
                </div>
                @endif
                @if(isset($refund['rejection_reason']))
                <div class="mt-2 bg-red-50 rounded-lg p-3">
                    <span class="text-xs text-red-500">Alasan penolakan:</span>
                    <p class="text-sm text-red-700">{{ $refund['rejection_reason'] }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif
    @endif
</div>
@endsection

@push('scripts')
<script>
function openRejectRefundModal() { document.getElementById('rejectRefundModal').style.display = 'flex'; }
document.getElementById('rejectRefundModal')?.addEventListener('click', function(e) { if (e.target === this) this.style.display = 'none'; });
</script>
@endpush