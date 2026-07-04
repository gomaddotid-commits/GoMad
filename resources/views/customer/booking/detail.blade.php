@extends('layouts.customer')

@section('title', 'Detail Booking')
@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    @if(isset($booking))
    
    {{-- Status Banner --}}
    {{-- Status Banner --}}
    <div class="rounded-2xl p-4 mb-6 text-center
        @if(in_array($booking->status, ['paid', 'on_going', 'completed'])) bg-green-50 border border-green-200
        @elseif(in_array($booking->status, ['pending', 'confirmed'])) bg-yellow-50 border border-yellow-200
        @elseif($booking->status == 'cancelled') bg-red-50 border border-red-200
        @else bg-gray-50 border border-gray-200 @endif">
        <div class="text-4xl mb-2">
            @if(in_array($booking->status, ['paid', 'on_going', 'completed'])) ✅
            @elseif(in_array($booking->status, ['pending', 'confirmed'])) ⏳
            @elseif($booking->status == 'cancelled') ❌
            @else 🚗
            @endif
        </div>
        <h2 class="text-xl font-bold">
            @if($booking->status == 'confirmed' && $booking->payment && $booking->payment->payment_type == 'cod')
                Menunggu Pembayaran COD
            @else
                {{ $booking->status_label }}
            @endif
        </h2>
        <p class="text-sm mt-1">
            @if($booking->status == 'pending' && !$booking->payment && !$booking->cashPayment)
                Pilih metode pembayaran dan promo di bawah ini.
            @elseif($booking->status == 'confirmed' && $booking->payment && $booking->payment->payment_type == 'cod')
                🚗 Pembayaran COD - Bayar tunai ke sopir saat penjemputan. E-Ticket tersedia setelah sopir konfirmasi.
            @elseif($booking->payment && $booking->payment->payment_type == 'midtrans' && $booking->payment->status == 'pending')
                Silakan selesaikan pembayaran online.
            @elseif($booking->cashPayment && $booking->cashPayment->status == 'pending')
                Tunjukkan kode bayar ke Warung GoMad terdekat.
            @elseif(in_array($booking->status, ['paid', 'on_going', 'completed']))
                Booking sudah dikonfirmasi. E-Ticket tersedia.
            @endif
        </p>
    </div>

    {{-- Booking Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-2xl font-bold">{{ $booking->booking_code }}</h1>
                <p class="text-gray-500">{{ $booking->schedule->agency->agency_name ?? '-' }}</p>
            </div>
            <p class="text-2xl font-bold text-primary-600">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
        </div>

        {{-- Detail Pesanan --}}
        <div class="border-t pt-4">
            <h3 class="font-bold text-secondary mb-3">Detail Pesanan</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div class="bg-gray-50 rounded-xl p-3">
                    <span class="text-xs text-gray-500">Rute</span>
                    <p class="font-semibold">{{ $booking->originStop->city_name ?? '?' }} → {{ $booking->destinationStop->city_name ?? '?' }}</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-3">
                    <span class="text-xs text-gray-500">Jadwal</span>
                    <p class="font-semibold">{{ $booking->schedule->departure_date->format('d M Y') }} {{ $booking->schedule->departure_time }}</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-3">
                    <span class="text-xs text-gray-500">Kendaraan</span>
                    <p class="font-semibold">{{ $booking->schedule->vehicle->plate_number ?? '-' }} ({{ $booking->schedule->vehicle->brand ?? '' }} {{ $booking->schedule->vehicle->model ?? '' }})</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-3">
                    <span class="text-xs text-gray-500">Jumlah Penumpang</span>
                    <p class="font-semibold">{{ $booking->total_passengers }} orang</p>
                </div>
            </div>

            {{-- Alamat --}}
            <div class="grid grid-cols-2 gap-3 mt-3 text-sm">
                <div class="bg-blue-50 rounded-xl p-3">
                    <span class="text-xs text-gray-500">📍 Jemput</span>
                    <p class="font-medium text-xs">{{ $booking->pickup_address }}</p>
                </div>
                <div class="bg-red-50 rounded-xl p-3">
                    <span class="text-xs text-gray-500">🎯 Tujuan</span>
                    <p class="font-medium text-xs">{{ $booking->destination_address }}</p>
                </div>
            </div>

            {{-- List Penumpang --}}
            <div class="mt-4">
                <h4 class="font-semibold text-sm mb-2">Daftar Penumpang</h4>
                <div class="space-y-1">
                    @forelse($booking->passengers as $p)
                    <div class="flex justify-between text-sm py-1.5 px-3 bg-gray-50 rounded-lg">
                        <span>{{ $p->passenger_name }} <span class="text-gray-400 text-xs">Seat {{ $p->seat_number }}</span></span>
                        <span class="text-gray-500 text-xs">{{ $p->passenger_phone }}</span>
                    </div>
                    @empty
                    <p class="text-gray-400 text-sm">-</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- HANYA TAMPIL JIKA STATUS PENDING & BELUM PILIH PEMBAYARAN --}}
    {{-- =========================================================== --}}
    @if($booking->status == 'pending' && !$booking->payment && !$booking->cashPayment)
    
        {{-- Pilih Metode Pembayaran (DROPDOWN) --}}
        {{-- Pilih Metode Pembayaran --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
            <h2 class="text-lg font-bold text-secondary mb-3">Metode Pembayaran</h2>
            
            @php
                // Ambil payment methods dari route
                $routePaymentMethods = $booking->schedule->route->payment_methods_array;
            @endphp
            
            <select id="paymentMethod" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50 text-sm" onchange="updatePaymentInfo()">
                <option value="">-- Pilih Metode Pembayaran --</option>
                
                @if(in_array('midtrans', $routePaymentMethods))
                <option value="midtrans">💳 Bayar Online (Transfer Bank, VA, QRIS, E-Wallet)</option>
                @endif
                
                @if(in_array('cash', $routePaymentMethods))
                <option value="cash">🏪 Bayar di Warung GoMad (Cash)</option>
                @endif
                
                @if(in_array('cod', $routePaymentMethods) && $booking->schedule->allow_cod && $booking->schedule->route->cod_available)
                <option value="cod">🚗 COD - Bayar ke Sopir saat Penjemputan</option>
                @endif
            </select>
            
            @if(count($routePaymentMethods) < 3)
            <div class="mt-2 bg-blue-50 rounded-lg p-2 text-xs text-blue-700">
                ℹ️ Beberapa metode pembayaran tidak tersedia untuk rute ini.
            </div>
            @endif
            
            <div id="paymentInfo" class="mt-3 text-sm text-gray-600 hidden"></div>
        </div>

        {{-- Pilih Promo (DROPDOWN dengan filter metode pembayaran) --}}
        @php
            $promoService = app(\App\Services\PromoService::class);
            $availablePromos = $promoService->getAvailablePromosForCustomer(auth()->user(), $booking->schedule_id);
        @endphp
        @if($availablePromos->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
            <h2 class="text-lg font-bold text-secondary mb-3">Promo Tersedia</h2>
            <select id="promoSelect" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 bg-gray-50 text-sm" onchange="updatePromoInfo()">
                <option value="">-- Tanpa Promo --</option>
                @foreach($availablePromos as $promo)
                @php
                    $methods = $promo->getApplicablePaymentMethodsAttribute();
                    $methodLabels = [];
                    if (in_array('midtrans', $methods)) $methodLabels[] = 'Online';
                    if (in_array('cash', $methods)) $methodLabels[] = 'Warung';
                    if (in_array('cod', $methods)) $methodLabels[] = 'COD';
                    $allMethods = count($methods) >= 3;
                    $methodText = !$allMethods ? ' (' . implode(', ', $methodLabels) . ' only)' : '';
                @endphp
                <option value="{{ $promo->id }}" 
                        data-percent="{{ $promo->discount_percent }}" 
                        data-max="{{ $promo->max_discount }}"
                        data-name="{{ $promo->name }}"
                        data-methods="{{ implode(',', $methods) }}">
                    {{ $promo->name }} - Diskon {{ $promo->discount_percent }}% (Maks Rp {{ number_format($promo->max_discount, 0, ',', '.') }}){{ $methodText }}
                </option>
                @endforeach
            </select>
            <div id="promoInfo" class="mt-3 text-sm text-purple-700 hidden"></div>
            <div id="promoWarning" class="mt-2 text-sm text-red-600 hidden"></div>
        </div>
        @endif

        {{-- Detail Kalkulasi Harga --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-lg font-bold text-secondary mb-4">Rincian Pembayaran</h2>
            <div class="space-y-2 text-sm">
                @php
                    $basePrice = $booking->base_price ?? ($booking->total_price - ($booking->service_fee ?? 0) - ($booking->platform_fee ?? 0) + ($booking->discount_amount ?? 0));
                    $pricePerPerson = $booking->total_passengers > 0 ? $basePrice / $booking->total_passengers : 0;
                @endphp
                <div class="flex justify-between">
                    <span class="text-gray-600">Harga Tiket ({{ $booking->total_passengers }} × Rp {{ number_format($pricePerPerson, 0, ',', '.') }})</span>
                    <span class="font-medium">Rp {{ number_format($basePrice, 0, ',', '.') }}</span>
                </div>
                
                @if(($booking->service_fee ?? 0) > 0)
                <div class="flex justify-between">
                    <span class="text-gray-600">Biaya Layanan</span>
                    <span class="font-medium">Rp {{ number_format($booking->service_fee, 0, ',', '.') }}</span>
                </div>
                @endif
                
                @if(($booking->platform_fee ?? 0) > 0)
                <div class="flex justify-between">
                    <span class="text-gray-600">Biaya Platform</span>
                    <span class="font-medium">Rp {{ number_format($booking->platform_fee, 0, ',', '.') }}</span>
                </div>
                @endif
                
                <div id="discountRow" class="flex justify-between text-green-600 hidden">
                    <span>Diskon Promo</span>
                    <span class="font-bold" id="discountAmount">-Rp 0</span>
                </div>
                
                <hr class="border-gray-200">
                <div class="flex justify-between text-base font-bold">
                    <span>Total</span>
                    <span class="text-primary-600" id="finalTotal">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Tombol Bayar --}}
        <form id="payForm" action="{{ route('customer.booking.pay-process', $booking) }}" method="POST" onsubmit="return validateAndSubmit()">
            @csrf
            <input type="hidden" name="payment_method" id="fPaymentMethod">
            <input type="hidden" name="promo_id" id="fPromoId">
            <button type="submit" id="btnPay" disabled
                    class="w-full bg-gray-300 text-gray-500 py-4 rounded-xl font-bold text-lg cursor-not-allowed transition">
                💳 BAYAR SEKARANG
            </button>
        </form>

    @endif

    {{-- =========================================================== --}}
    {{-- MIDTRANS: Tombol Snap --}}
    {{-- =========================================================== --}}
    @if($booking->payment && $booking->payment->payment_type == 'midtrans' && $booking->payment->status == 'pending')
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h2 class="text-lg font-bold text-secondary mb-4">Pembayaran Online</h2>
        <div class="bg-blue-50 rounded-xl p-4 mb-4 text-sm text-blue-800">
            <p>💳 <strong>Midtrans</strong> - Transfer Bank, Virtual Account, QRIS, E-Wallet</p>
        </div>
        @if(isset($snapToken))
        <button id="pay-button" class="w-full bg-primary-600 text-white py-4 rounded-xl font-bold text-lg hover:bg-primary-700 transition">
            💳 BAYAR SEKARANG (MIDTRANS)
        </button>
        @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center text-sm text-yellow-800">
            Menghubungkan ke gateway pembayaran...
            <a href="{{ route('customer.booking.show', $booking) }}" class="text-primary-600 underline font-medium ml-2">Muat Ulang Halaman</a>
        </div>
        @endif
    </div>
    @endif

    {{-- =========================================================== --}}
    {{-- CASH: Kode Bayar + Peta Warung --}}
    {{-- =========================================================== --}}
    @if($booking->cashPayment && $booking->cashPayment->status == 'pending')
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h2 class="text-lg font-bold text-secondary mb-4">Pembayaran di Warung GoMad</h2>
        
        <div class="bg-green-50 border-2 border-green-300 rounded-2xl p-6 text-center mb-6">
            <p class="text-sm text-green-700 mb-2">Tunjukkan kode ini ke Warung GoMad terdekat</p>
            <p class="text-4xl font-mono font-bold text-primary-600 tracking-widest mb-2">{{ $booking->cashPayment->payment_code }}</p>
            <p class="text-xs text-gray-500">Expired: {{ $booking->cashPayment->expired_at ? $booking->cashPayment->expired_at->format('d M Y H:i') : '-' }}</p>
            <button onclick="copyPaymentCode()" class="mt-3 bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition">
                📋 Salin Kode
            </button>
        </div>

        <div>
            <h3 class="font-bold text-secondary mb-3">Warung GoMad Terdekat</h3>
            <p class="text-sm text-gray-500 mb-4">Kunjungi salah satu Warung GoMad di bawah ini untuk melakukan pembayaran</p>
            <div id="warungMap" style="height: 400px;" class="rounded-xl border border-gray-200 mb-4"></div>
            <div id="warungList" class="space-y-3">
                <p class="text-sm text-gray-500 text-center">Memuat data warung...</p>
            </div>
        </div>
    </div>
    @endif

    {{-- =========================================================== --}}
    {{-- COD: Info Pembayaran ke Sopir --}}
    {{-- =========================================================== --}}
    {{-- COD: Info Pembayaran ke Sopir --}}
    @if($booking->payment && $booking->payment->payment_type == 'cod')
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h2 class="text-lg font-bold text-secondary mb-4">Pembayaran ke Sopir (COD)</h2>
        
        @if($booking->payment->status == 'cod_pending')
        <div class="bg-orange-50 border-2 border-orange-300 rounded-2xl p-6 text-center">
            <div class="text-4xl mb-3">🚗</div>
            <p class="font-bold text-orange-800 text-lg mb-2">Bayar ke Sopir saat Penjemputan</p>
            <p class="text-sm text-orange-700 mb-4">
                Siapkan uang tunai sejumlah <strong>Rp {{ number_format($booking->total_price, 0, ',', '.') }}</strong> 
                dan bayarkan langsung ke sopir saat Anda dijemput.
            </p>
            <div class="bg-white rounded-xl p-4 text-left text-sm space-y-2">
                <p><strong>Nama Sopir:</strong> {{ $booking->schedule->driver->name ?? 'Akan ditentukan' }}</p>
                <p><strong>Kendaraan:</strong> {{ $booking->schedule->vehicle->plate_number ?? '-' }} ({{ $booking->schedule->vehicle->brand ?? '' }} {{ $booking->schedule->vehicle->model ?? '' }})</p>
                <p><strong>Jemput:</strong> {{ $booking->schedule->departure_date->format('d M Y') }} {{ $booking->schedule->departure_time }}</p>
                <p><strong>Alamat:</strong> {{ $booking->pickup_address }}</p>
            </div>
            {{-- ⚠️ Peringatan: E-Ticket belum tersedia --}}
            <div class="mt-4 bg-yellow-100 border border-yellow-300 rounded-xl p-3 text-sm text-yellow-800">
                ⚠️ E-Ticket akan tersedia setelah sopir mengkonfirmasi pembayaran.
            </div>
        </div>
        @elseif($booking->payment->status == 'cod_confirmed')
        <div class="bg-green-50 border-2 border-green-300 rounded-2xl p-6 text-center">
            <div class="text-4xl mb-3">✅</div>
            <p class="font-bold text-green-800 text-lg mb-2">Pembayaran COD Dikonfirmasi</p>
            <p class="text-sm text-green-700">Pembayaran telah diterima oleh sopir. Booking Anda sudah aktif.</p>
        </div>
        @endif
    </div>
    @endif

    {{-- =========================================================== --}}
    {{-- PAYMENT SUCCESS --}}
    {{-- =========================================================== --}}
    @if($booking->payment && in_array($booking->payment->status, ['paid', 'cod_confirmed']))
    <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 text-center">
        <p class="font-bold text-green-800">✅ Pembayaran Berhasil</p>
        <p class="text-sm text-green-600">
            Metode: 
            @if($booking->payment->payment_type == 'midtrans') Online (Midtrans)
            @elseif($booking->payment->payment_type == 'cash') Warung GoMad
            @elseif($booking->payment->payment_type == 'cod') COD (Sopir)
            @endif
        </p>
    </div>
    @endif

    {{-- Promo Used Info --}}
    @if(($booking->discount_amount ?? 0) > 0)
    <div class="bg-purple-50 border border-purple-200 rounded-xl p-3 mb-6 text-sm">
        <span class="text-purple-800">🎫 Diskon Rp {{ number_format($booking->discount_amount, 0, ',', '.') }} telah diterapkan</span>
    </div>
    @endif

    {{-- =========================================================== --}}
    {{-- ACTIONS --}}
    {{-- =========================================================== --}}
    <div class="space-y-3">
        @if(in_array($booking->status, ['paid', 'on_going', 'completed']))
        <a href="{{ route('customer.booking.e-ticket', $booking) }}" 
           class="block w-full text-center bg-blue-600 text-white py-3 rounded-xl font-bold text-lg hover:bg-blue-700 transition">
            🎫 Lihat E-Ticket
        </a>
        @endif

        {{-- Tombol Batalkan Booking --}}
        @php
            $canCancel = $booking->can_cancel;
            $cancellationFee = $booking->cancellation_fee ?? 0;
            $cancellationRefund = $booking->cancellation_refund ?? 0;
            
            // Hitung jam sampai keberangkatan
            if ($booking->schedule) {
                $departureDateTime = \Carbon\Carbon::parse(
                    $booking->schedule->departure_date->format('Y-m-d') . ' ' . $booking->schedule->departure_time
                );
                $hoursUntilDeparture = now()->diffInHours($departureDateTime, false);
            } else {
                $hoursUntilDeparture = 999;
            }
        @endphp

        @if($canCancel)
            @if($booking->status === 'paid')
            {{-- Info biaya pembatalan untuk booking paid --}}
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-3">
                <h4 class="font-semibold text-red-800 text-sm mb-2">⚠️ Kebijakan Pembatalan</h4>
                <div class="text-sm text-red-700 space-y-1">
                    <p>• Biaya pembatalan: <strong>Rp {{ number_format($cancellationFee, 0, ',', '.') }}</strong> (25% dari total)</p>
                    <p>• Dana dikembalikan: <strong>Rp {{ number_format($cancellationRefund, 0, ',', '.') }}</strong></p>
                    @if($hoursUntilDeparture > 24 && $hoursUntilDeparture < 48)
                    <p>• ⏰ Batas pembatalan: <strong>{{ round($hoursUntilDeparture) }} jam lagi</strong></p>
                    @endif
                </div>
            </div>
            @endif

            <form action="{{ route('customer.booking.cancel', $booking) }}" method="POST" onsubmit="return confirmCancel()">
                @csrf
                <button type="submit" class="w-full border border-red-500 text-red-600 py-3 rounded-xl font-semibold hover:bg-red-50 transition">
                    @if($booking->status === 'paid')
                        ❌ Batalkan Booking (Biaya Rp {{ number_format($cancellationFee, 0, ',', '.') }})
                    @else
                        ❌ Batalkan Booking
                    @endif
                </button>
            </form>
        @elseif($booking->status === 'paid' && $hoursUntilDeparture <= 24)
            {{-- Tidak bisa cancel karena sudah H-24 --}}
            <div class="bg-gray-100 border border-gray-300 rounded-xl p-4 text-center">
                <p class="text-gray-600 text-sm font-medium">🔒 Pembatalan tidak tersedia</p>
                <p class="text-gray-500 text-xs mt-1">Kurang dari 24 jam sebelum keberangkatan. Hubungi agency untuk bantuan.</p>
                @if($booking->schedule && $booking->schedule->agency)
                <p class="text-gray-500 text-xs mt-1">
                    📞 {{ $booking->schedule->agency->contact_alternate ?? $booking->schedule->agency->user->phone ?? '-' }}
                </p>
                @endif
            </div>
        @endif

        <a href="{{ route('customer.bookings') }}" 
           class="block w-full text-center border border-gray-300 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-50 transition">
            ← Kembali ke Booking Saya
        </a>
    </div>
    @endif
</div>

{{-- =========================================================== --}}
{{-- SCRIPTS --}}
{{-- =========================================================== --}}

{{-- Midtrans Snap --}}
@if(isset($snapToken))
@push('scripts')
<script src="{{ config('gomad.midtrans.snap_url') }}" data-client-key="{{ config('gomad.midtrans.client_key') }}"></script>
<script>
document.getElementById('pay-button').addEventListener('click', function() {
    snap.pay('{{ $snapToken }}', {
        onSuccess: function(result) { window.location.reload(); },
        onPending: function(result) { alert('Menunggu pembayaran...'); },
        onError: function(result) { alert('Pembayaran gagal. Silakan coba lagi.'); }
    });
});
</script>
@endpush
@endif

{{-- Payment Method & Promo Script --}}
@if($booking->status == 'pending' && !$booking->payment && !$booking->cashPayment)
@push('scripts')
<script>
var basePrice = {{ $basePrice }};
var serviceFee = {{ $booking->service_fee ?? 0 }};
var platformFee = {{ $booking->platform_fee ?? 0 }};
var selectedPromoPercent = 0;
var selectedPromoMax = 0;
var selectedPromoName = '';

function updatePaymentInfo() {
    var method = document.getElementById('paymentMethod').value;
    var info = document.getElementById('paymentInfo');
    var btn = document.getElementById('btnPay');
    var fMethod = document.getElementById('fPaymentMethod');
    
    if (method) {
        fMethod.value = method;
        btn.disabled = false;
        btn.className = 'w-full bg-green-600 text-white py-4 rounded-xl font-bold text-lg hover:bg-green-700 cursor-pointer transition';
        
        if (method === 'midtrans') {
            info.innerHTML = '💳 <strong>Bayar Online</strong> - Transfer Bank, Virtual Account, QRIS, E-Wallet via Midtrans.';
            btn.textContent = '💳 BAYAR ONLINE';
        } else if (method === 'cash') {
            info.innerHTML = '🏪 <strong>Bayar di Warung GoMad</strong> - Dapatkan kode bayar dan tunjukkan ke warung terdekat.';
            btn.textContent = '🏪 BAYAR DI WARUNG';
        } else if (method === 'cod') {
            info.innerHTML = '🚗 <strong>COD - Bayar ke Sopir</strong> - Siapkan uang tunai dan bayarkan langsung ke sopir saat penjemputan.';
            btn.textContent = '🚗 BAYAR KE SOPIR';
        }
        info.classList.remove('hidden');
    } else {
        fMethod.value = '';
        btn.disabled = true;
        btn.textContent = '💳 BAYAR SEKARANG';
        btn.className = 'w-full bg-gray-300 text-gray-500 py-4 rounded-xl font-bold text-lg cursor-not-allowed transition';
        info.classList.add('hidden');
    }
    
    // Re-check promo setelah metode berubah
    updatePromoInfo();
}

function updatePromoInfo() {
    var select = document.getElementById('promoSelect');
    if (!select) return;
    
    var option = select.options[select.selectedIndex];
    var info = document.getElementById('promoInfo');
    var warning = document.getElementById('promoWarning');
    var discountRow = document.getElementById('discountRow');
    var discountAmount = document.getElementById('discountAmount');
    var finalTotal = document.getElementById('finalTotal');
    var fPromoId = document.getElementById('fPromoId');
    var paymentMethod = document.getElementById('paymentMethod').value;
    
    warning.classList.add('hidden');
    
    if (option.value) {
        // Cek apakah promo berlaku untuk metode pembayaran yang dipilih
        var promoMethods = option.getAttribute('data-methods') || '';
        if (paymentMethod && promoMethods && !promoMethods.includes(paymentMethod)) {
            warning.innerHTML = '⚠️ Promo ini tidak berlaku untuk metode pembayaran yang dipilih. Silakan ganti metode pembayaran atau pilih promo lain.';
            warning.classList.remove('hidden');
            fPromoId.value = '';
            discountRow.classList.add('hidden');
            finalTotal.textContent = 'Rp ' + formatRupiah(basePrice + serviceFee + platformFee);
            return;
        }
        
        selectedPromoPercent = parseFloat(option.getAttribute('data-percent'));
        selectedPromoMax = parseFloat(option.getAttribute('data-max'));
        selectedPromoName = option.getAttribute('data-name');
        
        var discount = Math.min(basePrice * (selectedPromoPercent / 100), selectedPromoMax);
        var newTotal = Math.max(0, (basePrice + serviceFee + platformFee) - discount);
        
        fPromoId.value = option.value;
        info.innerHTML = '🎫 <strong>' + selectedPromoName + '</strong> - Diskon ' + selectedPromoPercent + '% (Maks Rp ' + formatRupiah(selectedPromoMax) + ')';
        info.classList.remove('hidden');
        
        discountRow.classList.remove('hidden');
        discountAmount.textContent = '-Rp ' + formatRupiah(discount);
        finalTotal.textContent = 'Rp ' + formatRupiah(newTotal);
    } else {
        selectedPromoPercent = 0;
        selectedPromoMax = 0;
        selectedPromoName = '';
        fPromoId.value = '';
        
        info.classList.add('hidden');
        discountRow.classList.add('hidden');
        finalTotal.textContent = 'Rp ' + formatRupiah(basePrice + serviceFee + platformFee);
    }
}

function validateAndSubmit() {
    var method = document.getElementById('paymentMethod').value;
    if (!method) {
        alert('Silakan pilih metode pembayaran terlebih dahulu!');
        return false;
    }
    var btn = document.getElementById('btnPay');
    btn.disabled = true;
    btn.textContent = '⏳ Memproses...';
    return true;
}

function formatRupiah(num) { return new Intl.NumberFormat('id-ID').format(num || 0); }
</script>
@endpush
@endif

{{-- Leaflet Map untuk Cash Payment --}}
@if($booking->cashPayment && $booking->cashPayment->status == 'pending')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var map = L.map('warungMap').setView([-7.0051, 113.8586], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap', maxZoom: 18 }).addTo(map);

    var pickupLat = {{ $booking->pickup_latitude ?? -7.0051 }};
    var pickupLng = {{ $booking->pickup_longitude ?? 113.8586 }};
    
    var customerIcon = L.divIcon({
        html: '<div style="background:#DC2626;color:white;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);">📍</div>',
        className: '', iconSize: [30, 30], iconAnchor: [15, 15],
    });
    L.marker([pickupLat, pickupLng], { icon: customerIcon }).addTo(map).bindPopup('<strong>Lokasi Anda</strong>');

    fetch('/api/v1/nearby-warungs?latitude=' + pickupLat + '&longitude=' + pickupLng + '&radius=300')
        .then(res => res.json())
        .then(data => {
            var warungs = data.data || [];
            var warungList = document.getElementById('warungList');
            if (!warungs.length) {
                warungList.innerHTML = '<p class="text-gray-500 text-center py-4">Tidak ada Warung GoMad terdekat.</p>';
                return;
            }
            var listHtml = '', bounds = L.latLngBounds();
            warungs.forEach(function(w) {
                if (w.latitude && w.longitude) {
                    var icon = L.divIcon({
                        html: '<div style="background:#16a34a;color:white;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);">🏪</div>',
                        className: '', iconSize: [32, 32], iconAnchor: [16, 16],
                    });
                    L.marker([w.latitude, w.longitude], { icon: icon }).addTo(map).bindPopup(
                        '<div style="min-width:180px;"><strong>' + w.agent_name + '</strong><br>' +
                        (w.address || '') + '<br>📞 ' + (w.owner_phone || '-') + '<br>' +
                        '<a href="' + (w.maps_link || 'https://www.google.com/maps?q=' + w.latitude + ',' + w.longitude) + '" target="_blank" style="display:inline-block;margin-top:6px;background:#DC2626;color:white;padding:6px 12px;border-radius:8px;text-decoration:none;font-size:12px;font-weight:600;">🗺️ Google Maps</a></div>'
                    );
                    bounds.extend([w.latitude, w.longitude]);
                }
                listHtml += '<div class="bg-white rounded-xl border p-4 hover:shadow-md">' +
                    '<div class="flex items-start gap-3"><div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-lg">🏪</div>' +
                    '<div><h4 class="font-bold text-sm">' + w.agent_name + '</h4><p class="text-xs text-gray-500">' + (w.address || '') + '</p>' +
                    '<div class="flex gap-2 mt-2">' +
                    '<button onclick="event.stopPropagation();map.setView([' + w.latitude + ',' + w.longitude + '],16)" class="text-xs bg-green-100 text-green-700 px-3 py-1.5 rounded-lg">Lihat Peta</button>' +
                    '<a href="' + (w.maps_link || 'https://www.google.com/maps?q=' + w.latitude + ',' + w.longitude) + '" target="_blank" class="text-xs bg-red-100 text-red-700 px-3 py-1.5 rounded-lg">🗺️ Maps</a>' +
                    '</div></div></div></div>';
            });
            warungList.innerHTML = listHtml;
            bounds.extend([pickupLat, pickupLng]);
            if (warungs.length) map.fitBounds(bounds, { padding: [50, 50], maxZoom: 14 });
        })
        .catch(function() {
            document.getElementById('warungList').innerHTML = '<p class="text-red-500 text-center py-4">Gagal memuat data warung.</p>';
        });
});

function copyPaymentCode() {
    navigator.clipboard.writeText('{{ $booking->cashPayment->payment_code }}');
    alert('Kode bayar berhasil disalin!');
}
</script>
@endpush
@endif

{{-- Modal Ganti Pembayaran --}}
@if($booking->status == 'pending' || ($booking->payment && $booking->payment->payment_type == 'cod' && $booking->payment->status == 'cod_pending'))
<div id="changePaymentModal" style="display:none;" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
        <h3 class="font-bold text-lg mb-4">Ganti Metode Pembayaran</h3>
        <form action="{{ route('customer.booking.change-payment', $booking) }}" method="POST" class="space-y-3">
            @csrf
            <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50">
                <input type="radio" name="new_method" value="midtrans" class="text-primary-600"> 💳 Bayar Online
            </label>
            <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50">
                <input type="radio" name="new_method" value="cash" class="text-primary-600"> 🏪 Warung GoMad
            </label>
            @if($booking->schedule->allow_cod)
            <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-gray-50">
                <input type="radio" name="new_method" value="cod" class="text-primary-600"> 🚗 COD
            </label>
            @endif
            <div class="flex gap-3 mt-4">
                <button type="submit" class="flex-1 btn-primary">Ganti</button>
                <button type="button" onclick="document.getElementById('changePaymentModal').style.display='none'" class="flex-1 border py-2 rounded-xl">Batal</button>
            </div>
        </form>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function confirmCancel() {
    @if($booking->status === 'paid')
    return confirm(
        '⚠️ KONFIRMASI PEMBATALAN\n\n' +
        'Biaya pembatalan: Rp {{ number_format($cancellationFee, 0, ',', '.') }} (25%)\n' +
        'Dana dikembalikan: Rp {{ number_format($cancellationRefund, 0, ',', '.') }}\n\n' +
        'Apakah Anda yakin ingin membatalkan booking ini?'
    );
    @else
    return confirm('Apakah Anda yakin ingin membatalkan booking ini?');
    @endif
}
</script>
@endpush