@extends('layouts.customer')

@section('title', 'Detail Rental')
@section('content')
@php
    $vehicle = $rental->vehicle;
    $setting = $vehicle->rentalSetting;
    $agency = $rental->agency;
    $snapToken = session('snap_token');
    
    // Alamat pengambilan untuk self_drive
    $pickupAddr = $setting?->pickup_address ?? $agency->address;
    $pickupMaps = $setting?->pickup_maps_url ?? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($pickupAddr);
@endphp

<div class="max-w-3xl mx-auto px-4 py-8">
    <a href="{{ route('customer.rentals') }}" class="text-[#C1121F] text-sm mb-4 inline-block hover:underline">
        ← Kembali ke Rental Saya
    </a>

    {{-- Status Banner --}}
    <div class="rounded-[12px] p-4 mb-6 text-center border
        @if(in_array($rental->status, ['paid'])) bg-blue-50 border-blue-200
        @elseif($rental->status == 'active') bg-indigo-50 border-indigo-200
        @elseif($rental->status == 'returned') bg-orange-50 border-orange-200
        @elseif($rental->status == 'completed') bg-green-50 border-green-200
        @elseif($rental->status == 'cancelled') bg-red-50 border-red-200
        @else bg-yellow-50 border-yellow-200 @endif">
        <div class="text-4xl mb-2">
            @if($rental->status == 'paid') 🚗
            @elseif($rental->status == 'active') 🏃
            @elseif($rental->status == 'returned') ✅
            @elseif($rental->status == 'completed') 🎉
            @elseif($rental->status == 'cancelled') ❌
            @else ⏳
            @endif
        </div>
        <h2 class="text-xl font-bold text-[#111111]">{{ $rental->status_label }}</h2>
        <p class="text-sm mt-1 font-light text-gray-600">
            @if($rental->status == 'pending' && $rental->payment && $rental->payment->status == 'pending')
                Menunggu pembayaran. Silakan selesaikan pembayaran di bawah.
            @elseif($rental->status == 'pending' && !$rental->payment)
                Silakan pilih metode pembayaran di bawah.
            @elseif($rental->status == 'paid')
                Pembayaran berhasil! 
                @if($rental->type == 'self_drive')
                    Silakan ambil mobil di lokasi agency.
                @else
                    Menunggu agency menugaskan supir.
                @endif
            @elseif($rental->status == 'active')
                Mobil sedang disewa. Kembalikan tepat waktu.
            @elseif($rental->status == 'returned')
                Mobil sudah dikembalikan. Menunggu verifikasi agency.
            @elseif($rental->status == 'completed')
                Rental selesai. Terima kasih!
            @endif
        </p>
    </div>

    {{-- Rental Card --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-2xl font-bold font-mono text-[#111111]">{{ $rental->rental_code }}</h1>
                <p class="text-gray-500 font-light">{{ $agency->agency_name }}</p>
            </div>
            <p class="text-2xl font-bold text-[#C1121F] font-mono">Rp {{ number_format($rental->total_price, 0, ',', '.') }}</p>
        </div>

        {{-- Info Kendaraan --}}
        <div class="flex items-center gap-4 mb-4 p-4 bg-[#F5F5F5] rounded-[12px] border border-[#E5E5E5]">
            <div class="w-20 h-16 bg-white rounded-[12px] overflow-hidden flex-shrink-0 border border-[#E5E5E5]">
                @if($vehicle->vehicle_image)
                <img src="{{ $vehicle->vehicle_image }}" class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center text-2xl">🚗</div>
                @endif
            </div>
            <div>
                <p class="font-bold text-[#111111]">{{ $vehicle->brand }} {{ $vehicle->model }}</p>
                <p class="text-sm text-gray-500 font-mono">{{ $vehicle->plate_number }}</p>
                <p class="text-xs text-gray-400 font-light">{{ $vehicle->year }}</p>
            </div>
        </div>

        {{-- Detail Rental --}}
        <div class="border-t border-[#E5E5E5] pt-4">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">Detail Sewa</h3>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Tipe</span>
                    <p class="font-semibold text-[#111111]">{{ $rental->type == 'self_drive' ? '🚗 Lepas Kunci' : '👨‍✈️ Dengan Supir' }}</p>
                </div>
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Durasi</span>
                    <p class="font-semibold text-[#111111]">{{ $rental->duration }} {{ $rental->duration_unit == 'hour' ? 'Jam' : 'Hari' }}</p>
                </div>
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Mulai</span>
                    <p class="font-semibold text-[#111111]">{{ $rental->start_datetime->format('d M Y H:i') }}</p>
                </div>
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Selesai</span>
                    <p class="font-semibold text-[#111111]">{{ $rental->end_datetime->format('d M Y H:i') }}</p>
                </div>
                
                @if($rental->started_at)
                <div class="bg-green-50 border border-green-200 rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Diambil</span>
                    <p class="font-semibold text-green-700">{{ $rental->started_at->format('d M Y H:i') }}</p>
                </div>
                @endif
                
                @if($rental->returned_at)
                <div class="bg-blue-50 border border-blue-200 rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Dikembalikan</span>
                    <p class="font-semibold text-blue-700">{{ $rental->returned_at->format('d M Y H:i') }}</p>
                </div>
                @endif
            </div>

            {{-- Rincian Harga --}}
            <div class="mt-4 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4">
                <h4 class="font-mono uppercase tracking-wider text-xs font-semibold text-[#111111] mb-2">Rincian Pembayaran</h4>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-light">Harga sewa ({{ $rental->duration }} {{ $rental->duration_unit }})</span>
                        <span class="text-[#111111]">Rp {{ number_format($rental->price_per_unit * $rental->duration, 0, ',', '.') }}</span>
                    </div>
                    @if($rental->driver_fee_per_unit > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-light">Biaya Supir ({{ $rental->duration }} {{ $rental->duration_unit }})</span>
                        <span class="text-[#111111]">Rp {{ number_format($rental->driver_fee_per_unit * $rental->duration, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-light">Subtotal</span>
                        <span class="font-semibold text-[#111111]">Rp {{ number_format($rental->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-light">Biaya Platform (3%)</span>
                        <span class="text-[#111111]">Rp {{ number_format($rental->platform_fee, 0, ',', '.') }}</span>
                    </div>
                    @if($rental->discount_amount > 0)
                    <div class="flex justify-between text-[#C1121F] font-medium bg-[#C1121F]/5 rounded-lg px-3 py-2 -mx-1">
                        <span>🎫 Diskon Promo @if($rental->promo)<span class="text-xs font-light">({{ $rental->promo->name }})</span>@endif</span>
                        <span class="font-bold">-Rp {{ number_format($rental->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <hr class="border-[#E5E5E5]">
                    <div class="flex justify-between font-bold text-base">
                        <span>Total</span>
                        <span class="text-[#C1121F] font-mono">Rp {{ number_format($rental->total_price, 0, ',', '.') }}</span>
                    </div>
                    @if($rental->promo)
                    <div class="mt-2 bg-purple-50 border border-purple-200 rounded-lg p-2 text-xs text-purple-700 font-light flex items-center gap-2">
                        <span>🏷️</span><span>Promo "<strong>{{ $rental->promo->name }}</strong>" berhasil diterapkan</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Info Supir (hanya with_driver) --}}
    @if($rental->type == 'with_driver')
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">👨‍✈️ Supir</h3>
        
        @if($rental->driver)
        <div class="flex items-center gap-4 p-4 bg-green-50 border border-green-200 rounded-[12px]">
            <div class="w-14 h-14 rounded-full bg-[#F5F5F5] flex items-center justify-center overflow-hidden border-2 border-green-300">
                @if($rental->driver->avatar_url)
                <img src="{{ $rental->driver->avatar_url }}" class="w-full h-full object-cover">
                @else
                <span class="text-2xl">👨‍✈️</span>
                @endif
            </div>
            <div>
                <p class="font-bold text-[#111111] text-lg">{{ $rental->driver->name }}</p>
                <p class="text-sm text-gray-500 font-light">📞 {{ $rental->driver->phone }}</p>
                <p class="text-xs text-green-600 mt-1 font-mono">✅ Supir telah ditugaskan</p>
            </div>
        </div>
        @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-4 text-center">
            <span class="text-2xl block mb-2">⏳</span>
            <p class="text-sm text-yellow-700 font-light">Menunggu agency menugaskan supir.</p>
            <p class="text-xs text-yellow-500 mt-1 font-light">Anda akan menerima notifikasi saat supir sudah ditugaskan.</p>
        </div>
        @endif
        
        @if($rental->pickup_address)
        <div class="mt-4 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
            <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">📍 Alamat Penjemputan</span>
            <p class="text-sm font-medium text-[#111111] mt-1">{{ $rental->pickup_address }}</p>
            @if($rental->pickup_maps_link)
            <a href="{{ $rental->pickup_maps_link }}" target="_blank" class="text-xs text-[#C1121F] hover:underline mt-1 inline-block">🗺️ Buka Google Maps</a>
            @endif
        </div>
        @endif
        
        @if($rental->destination_address)
        <div class="mt-3 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
            <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">🎯 Alamat Tujuan</span>
            <p class="text-sm font-medium text-[#111111] mt-1">{{ $rental->destination_address }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- Info Lokasi Pengambilan (hanya self_drive) --}}
    @if($rental->type == 'self_drive')
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">📍 Lokasi Pengambilan Mobil</h3>
        <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4">
            <p class="font-semibold text-[#111111]">{{ $rental->agency->agency_name }}</p>
            <p class="text-sm text-gray-500 mt-1 font-light">{{ $pickupAddr }}</p>
            <a href="{{ $pickupMaps }}" target="_blank" class="mt-3 inline-flex items-center gap-2 text-sm text-[#C1121F] hover:text-[#8A0F18] font-medium">
                🗺️ Buka Google Maps
            </a>
        </div>
        <div class="mt-3 bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-700">
            <p class="font-medium">⚠️ Informasi</p>
            <p class="font-light mt-1">Anda wajib datang ke lokasi di atas untuk mengambil mobil. Bawa KTP & SIM asli.</p>
        </div>
    </div>
    @endif

    {{-- Info Agency --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">Informasi Agency</h3>
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-[#F5F5F5] flex items-center justify-center overflow-hidden border border-[#E5E5E5]">
                @if($agency->logo)<img src="{{ $agency->logo }}" class="w-full h-full object-cover">@else<span class="text-xl">🏢</span>@endif
            </div>
            <div>
                <p class="font-semibold text-[#111111]">{{ $agency->agency_name }}</p>
                <p class="text-sm text-gray-500 font-light">{{ $agency->address }}</p>
                @if($agency->contact_alternate)<p class="text-sm text-gray-500 font-light">📞 {{ $agency->contact_alternate }}</p>@endif
            </div>
        </div>
    </div>

    {{-- Form Pembayaran --}}
    @if($rental->status == 'pending' && (!$rental->payment || $rental->payment->status == 'pending'))
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <h2 class="text-lg font-bold text-[#111111] mb-4">{{ $rental->payment ? 'Metode Pembayaran' : 'Pilih Metode Pembayaran' }}</h2>

        @if(!$rental->payment)
        <form action="{{ route('customer.rental.pay', $rental) }}" method="POST" id="paymentForm">
            @csrf
            <input type="hidden" name="payment_method" value="midtrans">
            <div class="bg-blue-50 border border-blue-200 rounded-[12px] p-4 mb-6">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">💳</span>
                    <div>
                        <p class="font-semibold text-[#111111]">Pembayaran Online (Midtrans)</p>
                        <p class="text-xs text-gray-500 font-light">Transfer Bank, Virtual Account, QRIS, E-Wallet</p>
                    </div>
                </div>
            </div>
            <button type="submit" id="btnPay" class="w-full bg-[#C1121F] text-white py-4 rounded-[12px] font-bold text-lg hover:bg-[#8A0F18] transition">💳 BAYAR SEKARANG</button>
        </form>
        @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-4 text-center">
            <span class="text-3xl block mb-2">⏳</span>
            <p class="font-bold text-yellow-800">Menunggu Pembayaran</p>
            <p class="text-sm text-yellow-600 font-light mt-1">Silakan selesaikan pembayaran online Anda</p>
            @if($snapToken)
            <button id="pay-button" class="mt-4 bg-[#C1121F] text-white px-8 py-3 rounded-[12px] font-semibold hover:bg-[#8A0F18] transition">💳 BAYAR SEKARANG (MIDTRANS)</button>
            @else
            <form action="{{ route('customer.rental.pay', $rental) }}" method="POST" class="mt-4 inline-block">@csrf <input type="hidden" name="payment_method" value="midtrans"> <button type="submit" class="bg-[#C1121F] text-white px-8 py-3 rounded-[12px] font-semibold hover:bg-[#8A0F18] transition">🔄 Muat Ulang Pembayaran</button></form>
            @endif
        </div>
        @endif
    </div>
    @endif

    {{-- Pembayaran Sukses --}}
    @if($rental->payment && $rental->payment->status == 'paid')
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <h2 class="text-lg font-bold text-[#111111] mb-4">Status Pembayaran</h2>
        <div class="bg-green-50 border border-green-200 rounded-[12px] p-4 text-center">
            <span class="text-3xl block mb-2">✅</span>
            <p class="font-bold text-green-800">Pembayaran Berhasil</p>
            <p class="text-sm text-green-600 font-light">Metode: 💳 Online (Midtrans)</p>
            @if($rental->payment->transaction_id)<p class="text-[10px] text-green-500 mt-1 font-mono">ID: {{ $rental->payment->transaction_id }}</p>@endif
        </div>
    </div>
    @endif

    {{-- Actions --}}
    <div class="space-y-3">
        @if($rental->status == 'pending')
            @if($rental->payment && $rental->payment->status == 'paid')
            <div class="bg-red-50 border border-red-200 rounded-[12px] p-4 mb-3">
                <h4 class="font-mono uppercase tracking-wider text-xs font-semibold text-red-800 mb-2">⚠️ Kebijakan Pembatalan</h4>
                <div class="text-sm text-red-700 space-y-1 font-light">
                    <p>• Biaya pembatalan: <strong>Rp {{ number_format(round($rental->total_price * 0.25), 0, ',', '.') }}</strong> (25%)</p>
                    <p>• Dana dikembalikan: <strong>Rp {{ number_format($rental->total_price - round($rental->total_price * 0.25), 0, ',', '.') }}</strong></p>
                </div>
            </div>
            @endif
            <form action="{{ route('customer.rental.cancel', $rental) }}" method="POST" onsubmit="return confirmCancel()">
                @csrf
                <button type="submit" class="w-full border border-red-500 text-red-600 py-3 rounded-[12px] font-semibold hover:bg-red-50 transition">
                    @if($rental->payment && $rental->payment->status == 'paid') ❌ Batalkan Rental (Biaya 25%) @else ❌ Batalkan Rental @endif
                </button>
            </form>
        @endif

        {{-- Info Refund --}}
        @if($rental->payment && $rental->payment->status == 'refunded')
        <div class="bg-purple-50 border border-purple-200 rounded-[12px] p-4 text-sm text-purple-700">
            <p class="font-semibold">💸 Refund Diproses</p>
            @php $refundData = $rental->payment->payment_detail['refund'] ?? []; $refundAmount = $refundData['refund_amount'] ?? $refundData['amount'] ?? 0; @endphp
            <p class="font-light mt-1">Jumlah refund: <strong>Rp {{ number_format($refundAmount, 0, ',', '.') }}</strong></p>
        </div>
        @endif

        <a href="{{ route('customer.rentals') }}" class="block w-full text-center border border-[#E5E5E5] text-gray-700 py-3 rounded-[12px] font-semibold hover:bg-[#F5F5F5] transition">← Kembali ke Rental Saya</a>
    </div>
</div>

@if($snapToken)
@push('scripts')
<script src="{{ config('gomad.midtrans.snap_url') }}" data-client-key="{{ config('gomad.midtrans.client_key') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var payButton = document.getElementById('pay-button');
    if (payButton) {
        payButton.addEventListener('click', function() {
            snap.pay('{{ $snapToken }}', {
                onSuccess: function(result) { window.location.reload(); },
                onPending: function(result) { alert('Menunggu pembayaran...'); },
                onError: function(result) { alert('Pembayaran gagal.'); window.location.reload(); }
            });
        });
    }
});
</script>
@endpush
@endif

@push('scripts')
<script>
var paymentForm = document.getElementById('paymentForm');
if (paymentForm) paymentForm.addEventListener('submit', function() { var btn = document.getElementById('btnPay'); if (btn) { btn.disabled = true; btn.textContent = '⏳ Memproses...'; btn.className = 'w-full bg-gray-400 text-white py-4 rounded-[12px] font-bold text-lg cursor-not-allowed transition'; } });
function confirmCancel() {
    @if($rental->payment && $rental->payment->status == 'paid')
    return confirm('⚠️ KONFIRMASI PEMBATALAN\n\nBiaya pembatalan: Rp {{ number_format(round($rental->total_price * 0.25), 0, ',', '.') }} (25%)\nDana dikembalikan: Rp {{ number_format($rental->total_price - round($rental->total_price * 0.25), 0, ',', '.') }}\n\nApakah Anda yakin?');
    @else
    return confirm('Apakah Anda yakin ingin membatalkan rental ini?');
    @endif
}
</script>
@endpush
@endsection