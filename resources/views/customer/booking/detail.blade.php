@extends('layouts.customer')

@section('title', 'Detail Booking')
@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    @if(isset($booking))
    
    {{-- Status Banner --}}
    <div class="rounded-[12px] p-4 mb-6 text-center border
        @if(in_array($booking->status, ['paid', 'on_going', 'completed'])) bg-green-50 border-green-200
        @elseif(in_array($booking->status, ['pending', 'confirmed'])) bg-yellow-50 border-yellow-200
        @elseif($booking->status == 'cancelled') bg-red-50 border-red-200
        @else bg-[#F5F5F5] border-[#E5E5E5] @endif">
        <div class="text-4xl mb-2">
            @if(in_array($booking->status, ['paid', 'on_going', 'completed'])) ✅
            @elseif(in_array($booking->status, ['pending', 'confirmed'])) ⏳
            @elseif($booking->status == 'cancelled') ❌
            @else 🚗
            @endif
        </div>
        <h2 class="text-xl font-bold text-[#111111]">
            @if($booking->status == 'confirmed' && $booking->payment && $booking->payment->payment_type == 'cod')
                Menunggu Pembayaran COD
            @else
                {{ $booking->status_label }}
            @endif
        </h2>
        <p class="text-sm mt-1 font-light text-gray-600">
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
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-2xl font-bold font-mono text-[#111111]">{{ $booking->booking_code }}</h1>
                <p class="text-gray-500 font-light">{{ $booking->schedule->agency->agency_name ?? '-' }}</p>
            </div>
            <p class="text-2xl font-bold text-[#C1121F] font-mono">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
        </div>

        {{-- Detail Pesanan --}}
        <div class="border-t border-[#E5E5E5] pt-4">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">Detail Pesanan</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Rute</span>
                    <p class="font-semibold text-[#111111]">{{ $booking->originStop->city_name ?? '?' }} → {{ $booking->destinationStop->city_name ?? '?' }}</p>
                </div>
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Jadwal</span>
                    <p class="font-semibold text-[#111111]">{{ $booking->schedule->departure_date->format('d M Y') }} {{ $booking->schedule->departure_time }}</p>
                </div>
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Kendaraan</span>
                    <p class="font-semibold text-[#111111]">{{ $booking->schedule->vehicle->plate_number ?? '-' }}</p>
                </div>
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Penumpang</span>
                    <p class="font-semibold text-[#111111]">{{ $booking->total_passengers }} orang</p>
                </div>
            </div>

            {{-- Alamat --}}
            <div class="grid grid-cols-2 gap-3 mt-3 text-sm">
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">📍 Jemput</span>
                    <p class="font-medium text-xs text-[#111111]">{{ $booking->pickup_address }}</p>
                </div>
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">🎯 Tujuan</span>
                    <p class="font-medium text-xs text-[#111111]">{{ $booking->destination_address }}</p>
                </div>
            </div>

            {{-- List Penumpang --}}
            <div class="mt-4">
                <h4 class="font-mono uppercase tracking-wider text-xs font-semibold text-[#111111] mb-2">Daftar Penumpang</h4>
                <div class="space-y-1">
                    @forelse($booking->passengers as $p)
                    <div class="flex justify-between text-sm py-1.5 px-3 bg-[#F5F5F5] border border-[#E5E5E5] rounded-lg">
                        <span class="text-[#111111]">{{ $p->passenger_name }} <span class="text-gray-400 text-xs font-mono">Seat {{ $p->seat_number }}</span></span>
                        <span class="text-gray-500 text-xs font-light">{{ $p->passenger_phone }}</span>
                    </div>
                    @empty
                    <p class="text-gray-400 text-sm font-light">-</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- HANYA TAMPIL JIKA STATUS PENDING & BELUM PILIH PEMBAYARAN --}}
    {{-- =========================================================== --}}
    @if($booking->status == 'pending' && !$booking->payment && !$booking->cashPayment)
    
        {{-- Pilih Metode Pembayaran --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-4 shadow-sm">
            <h2 class="text-lg font-bold text-[#111111] mb-3">Metode Pembayaran</h2>
            
            @php
                $routePaymentMethods = $booking->schedule->route->payment_methods_array;
            @endphp
            
            <select id="paymentMethod" class="w-full px-4 py-3 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-[#F5F5F5] text-sm transition-colors" onchange="updatePaymentInfo()">
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
            <div class="mt-2 bg-[#F5F5F5] border border-blue-200 rounded-lg p-2 text-xs text-blue-700 font-light">
                ℹ️ Beberapa metode pembayaran tidak tersedia untuk rute ini.
            </div>
            @endif
            
            <div id="paymentInfo" class="mt-3 text-sm text-gray-600 hidden font-light"></div>
        </div>

        {{-- Pilih Promo --}}
        @php
            $promoService = app(\App\Services\PromoService::class);
            $availablePromos = $promoService->getAvailablePromosForCustomer(auth()->user(), $booking->schedule_id);
        @endphp
        @if($availablePromos->isNotEmpty())
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-4 shadow-sm">
            <h2 class="text-lg font-bold text-[#111111] mb-3">Promo Tersedia</h2>
            <select id="promoSelect" class="w-full px-4 py-3 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-[#F5F5F5] text-sm transition-colors" onchange="updatePromoInfo()">
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
            <div id="promoInfo" class="mt-3 text-sm text-[#C1121F] hidden font-medium"></div>
            <div id="promoWarning" class="mt-2 text-sm text-red-600 hidden font-light"></div>
        </div>
        @endif

        {{-- Detail Kalkulasi Harga --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
            <h2 class="text-lg font-bold text-[#111111] mb-4">Rincian Pembayaran</h2>
            <div class="space-y-2 text-sm">
                @php
                    $basePrice = $booking->base_price ?? ($booking->total_price - ($booking->service_fee ?? 0) - ($booking->platform_fee ?? 0) + ($booking->discount_amount ?? 0));
                    $pricePerPerson = $booking->total_passengers > 0 ? $basePrice / $booking->total_passengers : 0;
                @endphp
                <div class="flex justify-between">
                    <span class="text-gray-500 font-light">Harga Tiket ({{ $booking->total_passengers }} × Rp {{ number_format($pricePerPerson, 0, ',', '.') }})</span>
                    <span class="font-medium text-[#111111]">Rp {{ number_format($basePrice, 0, ',', '.') }}</span>
                </div>
                
                @if(($booking->service_fee ?? 0) > 0)
                <div class="flex justify-between">
                    <span class="text-gray-500 font-light">Biaya Layanan</span>
                    <span class="font-medium text-[#111111]">Rp {{ number_format($booking->service_fee, 0, ',', '.') }}</span>
                </div>
                @endif
                
                @if(($booking->platform_fee ?? 0) > 0)
                <div class="flex justify-between">
                    <span class="text-gray-500 font-light">Biaya Platform</span>
                    <span class="font-medium text-[#111111]">Rp {{ number_format($booking->platform_fee, 0, ',', '.') }}</span>
                </div>
                @endif
                
                <div id="discountRow" class="flex justify-between text-[#C1121F] hidden">
                    <span>Diskon Promo</span>
                    <span class="font-bold" id="discountAmount">-Rp 0</span>
                </div>
                
                <hr class="border-[#E5E5E5]">
                <div class="flex justify-between text-base font-bold">
                    <span>Total</span>
                    <span class="text-[#C1121F] font-mono" id="finalTotal">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- Tombol Bayar --}}
        <form id="payForm" action="{{ route('customer.booking.pay-process', $booking) }}" method="POST" onsubmit="return validateAndSubmit()">
            @csrf
            <input type="hidden" name="payment_method" id="fPaymentMethod">
            <input type="hidden" name="promo_id" id="fPromoId">
            <button type="submit" id="btnPay" disabled
                    class="w-full bg-[#E5E5E5] text-gray-500 py-4 rounded-[12px] font-bold text-lg cursor-not-allowed transition">
                💳 BAYAR SEKARANG
            </button>
        </form>

    @endif

    {{-- =========================================================== --}}
    {{-- MIDTRANS: Tombol Snap --}}
    {{-- =========================================================== --}}
    @if($booking->payment && $booking->payment->payment_type == 'midtrans' && $booking->payment->status == 'pending')
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <h2 class="text-lg font-bold text-[#111111] mb-4">Pembayaran Online</h2>
        <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4 mb-4 text-sm text-[#111111]">
            <p>💳 <strong>Midtrans</strong> - Transfer Bank, Virtual Account, QRIS, E-Wallet</p>
        </div>
        @if(isset($snapToken) && $snapToken)
        <button id="pay-button" class="w-full bg-[#C1121F] text-white py-4 rounded-[12px] font-bold text-lg hover:bg-[#8A0F18] transition">
            💳 BAYAR SEKARANG (MIDTRANS)
        </button>
        @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-4 text-center text-sm text-yellow-800 font-light">
            ⏳ Menyiapkan pembayaran...
            <button onclick="window.location.reload()" class="block w-full mt-2 bg-[#C1121F] text-white py-2 rounded-[12px] font-medium hover:bg-[#8A0F18] transition">
                🔄 Muat Ulang Halaman
            </button>
        </div>
        @endif
    </div>
    @endif

    {{-- =========================================================== --}}
    {{-- CASH: Kode Bayar + Peta Warung --}}
    {{-- =========================================================== --}}
    @if($booking->cashPayment && $booking->cashPayment->status == 'pending')
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <h2 class="text-lg font-bold text-[#111111] mb-4">Pembayaran di Warung GoMad</h2>
        
        <div class="bg-green-50 border-2 border-green-300 rounded-[12px] p-6 text-center mb-6">
            <p class="text-sm text-green-700 font-mono uppercase tracking-wider mb-2">Tunjukkan kode ini ke Warung GoMad terdekat</p>
            <p class="text-4xl font-mono font-bold text-[#C1121F] tracking-widest mb-2">{{ $booking->cashPayment->payment_code }}</p>
            <p class="text-xs text-gray-500 font-light">Expired: {{ $booking->cashPayment->expired_at ? $booking->cashPayment->expired_at->format('d M Y H:i') : '-' }}</p>
            <button onclick="copyPaymentCode()" class="mt-3 bg-[#C1121F] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#8A0F18] transition">
                📋 Salin Kode
            </button>
        </div>

        <div>
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">Warung GoMad Terdekat</h3>
            <p class="text-sm text-gray-500 font-light mb-4">Kunjungi salah satu Warung GoMad di bawah ini untuk melakukan pembayaran</p>
            <div id="warungMap" style="height: 350px; z-index: 1;" class="rounded-[12px] border border-[#E5E5E5] mb-3 w-full"></div>
            <div id="warungInfo" class="text-center mb-3 font-light text-sm text-gray-500"></div>
            <div id="warungList" class="space-y-3">
                <p class="text-sm text-gray-500 text-center font-light">⏳ Memuat data warung...</p>
            </div>
        </div>
    </div>
    @endif

    {{-- =========================================================== --}}
    {{-- COD: Info Pembayaran ke Sopir --}}
    {{-- =========================================================== --}}
    @if($booking->payment && $booking->payment->payment_type == 'cod')
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <h2 class="text-lg font-bold text-[#111111] mb-4">Pembayaran ke Sopir (COD)</h2>
        
        @if($booking->payment->status == 'cod_pending')
        <div class="bg-orange-50 border-2 border-orange-300 rounded-[12px] p-6 text-center">
            <div class="text-4xl mb-3">🚗</div>
            <p class="font-bold text-orange-800 text-lg mb-2">Bayar ke Sopir saat Penjemputan</p>
            <p class="text-sm text-orange-700 mb-4 font-light">
                Siapkan uang tunai sejumlah <strong>Rp {{ number_format($booking->total_price, 0, ',', '.') }}</strong> 
                dan bayarkan langsung ke sopir saat Anda dijemput.
            </p>
            <div class="bg-white rounded-[12px] p-4 text-left text-sm space-y-2 border border-orange-200">
                <p><strong class="font-mono uppercase tracking-wider text-xs">Nama Sopir:</strong> {{ $booking->schedule->driver->name ?? 'Akan ditentukan' }}</p>
                <p><strong class="font-mono uppercase tracking-wider text-xs">Kendaraan:</strong> {{ $booking->schedule->vehicle->plate_number ?? '-' }}</p>
                <p><strong class="font-mono uppercase tracking-wider text-xs">Jemput:</strong> {{ $booking->schedule->departure_date->format('d M Y') }} {{ $booking->schedule->departure_time }}</p>
                <p><strong class="font-mono uppercase tracking-wider text-xs">Alamat:</strong> {{ $booking->pickup_address }}</p>
            </div>
            <div class="mt-4 bg-yellow-100 border border-yellow-300 rounded-[12px] p-3 text-sm text-yellow-800 font-light">
                ⚠️ E-Ticket akan tersedia setelah sopir mengkonfirmasi pembayaran.
            </div>
        </div>
        @elseif($booking->payment->status == 'cod_confirmed')
        <div class="bg-green-50 border-2 border-green-300 rounded-[12px] p-6 text-center">
            <div class="text-4xl mb-3">✅</div>
            <p class="font-bold text-green-800 text-lg mb-2">Pembayaran COD Dikonfirmasi</p>
            <p class="text-sm text-green-700 font-light">Pembayaran telah diterima oleh sopir. Booking Anda sudah aktif.</p>
        </div>
        @endif
    </div>
    @endif

    {{-- =========================================================== --}}
    {{-- PAYMENT SUCCESS --}}
    {{-- =========================================================== --}}
    @if($booking->payment && in_array($booking->payment->status, ['paid', 'cod_confirmed']))
    <div class="bg-green-50 border border-green-200 rounded-[12px] p-4 mb-6 text-center">
        <p class="font-bold text-green-800">✅ Pembayaran Berhasil</p>
        <p class="text-sm text-green-600 font-light">
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
    <div class="bg-purple-50 border border-purple-200 rounded-[12px] p-3 mb-6 text-sm">
        <span class="text-purple-800 font-medium">🎫 Diskon Rp {{ number_format($booking->discount_amount, 0, ',', '.') }} telah diterapkan</span>
    </div>
    @endif

    {{-- =========================================================== --}}
    {{-- ACTIONS --}}
    {{-- =========================================================== --}}
    <div class="space-y-3">
        @if(in_array($booking->status, ['paid', 'on_going', 'completed']))
        <a href="{{ route('customer.booking.e-ticket', $booking) }}" 
           class="block w-full text-center bg-[#C1121F] text-white py-3 rounded-[12px] font-bold text-lg hover:bg-[#8A0F18] transition">
            🎫 Lihat E-Ticket
        </a>
        @endif

        {{-- Tombol Batalkan Booking --}}
        @php
            $canCancel = $booking->can_cancel;
            $cancellationFee = $booking->cancellation_fee ?? 0;
            $cancellationRefund = $booking->cancellation_refund ?? 0;
            
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
            <div class="bg-red-50 border border-red-200 rounded-[12px] p-4 mb-3">
                <h4 class="font-mono uppercase tracking-wider text-xs font-semibold text-red-800 mb-2">⚠️ Kebijakan Pembatalan</h4>
                <div class="text-sm text-red-700 space-y-1 font-light">
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
                <button type="submit" class="w-full border border-red-500 text-red-600 py-3 rounded-[12px] font-semibold hover:bg-red-50 transition">
                    @if($booking->status === 'paid')
                        ❌ Batalkan Booking (Biaya Rp {{ number_format($cancellationFee, 0, ',', '.') }})
                    @else
                        ❌ Batalkan Booking
                    @endif
                </button>
            </form>
        @elseif($booking->status === 'paid' && $hoursUntilDeparture <= 24)
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4 text-center">
                <p class="text-gray-600 text-sm font-medium">🔒 Pembatalan tidak tersedia</p>
                <p class="text-gray-500 text-xs mt-1 font-light">Kurang dari 24 jam sebelum keberangkatan. Hubungi agency untuk bantuan.</p>
                @if($booking->schedule && $booking->schedule->agency)
                <p class="text-gray-500 text-xs mt-1 font-light">
                    📞 {{ $booking->schedule->agency->contact_alternate ?? $booking->schedule->agency->user->phone ?? '-' }}
                </p>
                @endif
            </div>
        @endif

        <a href="{{ route('customer.bookings') }}" 
           class="block w-full text-center border border-[#E5E5E5] text-gray-700 py-3 rounded-[12px] font-semibold hover:bg-[#F5F5F5] transition">
            ← Kembali ke Booking Saya
        </a>
    </div>
    @endif
</div>

{{-- =========================================================== --}}
{{-- SCRIPTS --}}
{{-- =========================================================== --}}
{{-- MIDTRANS SNAP (Jika ada snap token) --}}
@if($snapToken)
@push('scripts')
<script src="{{ config('gomad.midtrans.snap_url') }}" data-client-key="{{ config('gomad.midtrans.client_key') }}"></script>
<script>
var payButton = document.getElementById('pay-button');
if (payButton) {
    payButton.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Disable button
        payButton.disabled = true;
        payButton.textContent = '⏳ Menghubungkan...';
        
        snap.pay('{{ $snapToken }}', {
            onSuccess: function(result) {
                // Pembayaran sukses
                window.location.href = '{{ route('customer.booking.show', $booking) }}?status=success';
            },
            onPending: function(result) {
                // Pembayaran pending
                alert('Pembayaran Anda masih dalam proses. Silakan cek status secara berkala.');
                window.location.reload();
            },
            onError: function(result) {
                // Pembayaran gagal
                alert('Pembayaran gagal. Silakan coba lagi.');
                window.location.reload();
            },
            onClose: function() {
                // User menutup popup tanpa bayar
                payButton.disabled = false;
                payButton.textContent = '💳 BAYAR SEKARANG (MIDTRANS)';
                
                // Reload halaman untuk dapat Snap Token baru
                setTimeout(function() {
                    window.location.reload();
                }, 500);
            }
        });
    });
}
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
        btn.className = 'w-full bg-[#C1121F] text-white py-4 rounded-[12px] font-bold text-lg hover:bg-[#8A0F18] cursor-pointer transition';
        
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
        btn.className = 'w-full bg-[#E5E5E5] text-gray-500 py-4 rounded-[12px] font-bold text-lg cursor-not-allowed transition';
        info.classList.add('hidden');
    }
    
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
// Leaflet Map untuk Cash Payment
document.addEventListener('DOMContentLoaded', function() {
    var mapEl = document.getElementById('warungMap');
    if (!mapEl) return;

    var map = L.map('warungMap').setView([-7.0051, 113.8586], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
        attribution: '&copy; OpenStreetMap', 
        maxZoom: 18 
    }).addTo(map);

    var pickupLat = {{ $booking->pickup_latitude ?? -7.0051 }};
    var pickupLng = {{ $booking->pickup_longitude ?? 113.8586 }};
    var radius = 50; // km

    var customerIcon = L.divIcon({
        html: '<div style="background:#C1121F;color:white;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);">📍</div>',
        className: '', iconSize: [30, 30], iconAnchor: [15, 15],
    });
    L.marker([pickupLat, pickupLng], { icon: customerIcon })
        .addTo(map)
        .bindPopup('<strong>Lokasi Penjemputan</strong>');

    function renderWarungs(warungs, showAll) {
        var warungList = document.getElementById('warungList');
        
        if (!warungs || !warungs.length) {
            warungList.innerHTML = `
                <div class="text-center py-6">
                    <div class="text-4xl mb-3">🏪</div>
                    <p class="text-gray-600 font-medium mb-2">Tidak ada Warung GoMad terdekat</p>
                    <p class="text-sm text-gray-500 mb-4">Tidak ditemukan warung dalam radius ${radius} km dari lokasi penjemputan</p>
                    <button onclick="loadAllWarungs()" class="bg-[#C1121F] text-white px-6 py-3 rounded-[12px] text-sm font-semibold hover:bg-[#8A0F18] transition">
                        🔍 Lihat Semua Warung Tersedia
                    </button>
                </div>`;
            return;
        }

        var listHtml = '';
        var bounds = L.latLngBounds();

        warungs.forEach(function(w) {
            if (w.latitude && w.longitude) {
                var lat = parseFloat(w.latitude);
                var lng = parseFloat(w.longitude);

                var warungIcon = L.divIcon({
                    html: '<div style="background:#C1121F;color:white;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);">🏪</div>',
                    className: '', iconSize: [32, 32], iconAnchor: [16, 16],
                });

                L.marker([lat, lng], { icon: warungIcon })
                    .addTo(map)
                    .bindPopup(`
                        <div style="min-width:180px;">
                            <strong>${w.agent_name}</strong><br>
                            <span style="font-size:12px;color:#666;">${w.address || ''}</span><br>
                            <span style="font-size:12px;">📞 ${w.owner_phone || '-'}</span><br>
                            ${w.distance_km ? '<span style="font-size:11px;color:#C1121F;">📍 ' + w.distance_km.toFixed(1) + ' km</span><br>' : ''}
                            <a href="${w.maps_link || 'https://www.google.com/maps?q=' + lat + ',' + lng}" target="_blank" style="display:inline-block;margin-top:6px;background:#C1121F;color:white;padding:6px 12px;border-radius:8px;text-decoration:none;font-size:12px;font-weight:600;">🗺️ Google Maps</a>
                        </div>
                    `);

                bounds.extend([lat, lng]);
            }

            var distanceText = w.distance_km ? w.distance_km.toFixed(1) + ' km' : '';
            listHtml += `
                <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 hover:border-[#C1121F] transition">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-[#C1121F]/10 rounded-full flex items-center justify-center text-lg flex-shrink-0 border border-[#E5E5E5]">🏪</div>
                        <div class="flex-1">
                            <h4 class="font-bold text-[#111111] text-sm">${w.agent_name}</h4>
                            <p class="text-xs text-gray-500 mt-1 font-light">${w.address || ''}</p>
                            <div class="flex items-center gap-3 mt-2 text-xs">
                                <span class="text-gray-500 font-light">📞 ${w.owner_phone || '-'}</span>
                                ${distanceText ? '<span class="text-[#C1121F] font-medium">📍 ' + distanceText + '</span>' : ''}
                            </div>
                            <div class="flex gap-2 mt-2">
                                <button onclick="event.stopPropagation();map.setView([' + lat + ', ' + lng + '], 16)" class="text-xs bg-[#C1121F]/10 text-[#C1121F] px-3 py-1.5 rounded-lg hover:bg-[#C1121F]/20 transition font-medium">Lihat Peta</button>
                                <a href="${w.maps_link || 'https://www.google.com/maps?q=' + lat + ',' + lng}" target="_blank" onclick="event.stopPropagation();" class="text-xs bg-[#C1121F] text-white px-3 py-1.5 rounded-lg hover:bg-[#8A0F18] transition font-medium">🗺️ Google Maps</a>
                            </div>
                        </div>
                    </div>
                </div>`;
        });

        warungList.innerHTML = listHtml;

        if (!showAll) {
            bounds.extend([pickupLat, pickupLng]);
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 14 });
        } else {
            map.fitBounds(bounds, { padding: [30, 30], maxZoom: 8 });
        }

        var infoEl = document.getElementById('warungInfo');
        if (infoEl) {
            infoEl.innerHTML = showAll 
                ? `<span class="text-xs text-gray-500">📋 Menampilkan semua <strong>${warungs.length}</strong> warung tersedia</span>`
                : `<span class="text-xs text-gray-500">📍 <strong>${warungs.length}</strong> warung dalam radius ${radius} km</span>`;
        }
    }

    function loadNearbyWarungs() {
        document.getElementById('warungList').innerHTML = '<p class="text-sm text-gray-500 text-center py-4 font-light">⏳ Mencari warung terdekat...</p>';

        fetch('/api/v1/nearby-warungs?latitude=' + pickupLat + '&longitude=' + pickupLng + '&radius=' + radius, {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            renderWarungs(data.data || [], false);
        })
        .catch(function() {
            document.getElementById('warungList').innerHTML = `
                <div class="text-center py-6">
                    <p class="text-red-500 mb-2 font-medium">⚠️ Gagal menghubungi server</p>
                    <button onclick="loadNearbyWarungs()" class="text-[#C1121F] hover:underline text-sm font-medium">Coba Lagi</button>
                </div>`;
        });
    }

    window.loadAllWarungs = function() {
        document.getElementById('warungList').innerHTML = '<p class="text-sm text-gray-500 text-center py-4 font-light">⏳ Memuat semua warung...</p>';

        fetch('/api/v1/nearby-warungs?latitude=' + pickupLat + '&longitude=' + pickupLng + '&radius=9999', {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            renderWarungs(data.data || [], true);
        })
        .catch(function() {
            document.getElementById('warungList').innerHTML = '<p class="text-red-500 text-center py-4 font-medium">Gagal memuat data warung.</p>';
        });
    };

    loadNearbyWarungs();
});
</script>
@endpush
@endif

{{-- Modal Ganti Pembayaran --}}
@if($booking->status == 'pending' || ($booking->payment && $booking->payment->payment_type == 'cod' && $booking->payment->status == 'cod_pending'))
<div id="changePaymentModal" style="display:none;" class="fixed inset-0 bg-[#111111]/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-[12px] shadow-xl p-6 max-w-sm w-full border border-[#E5E5E5]">
        <h3 class="font-bold text-lg text-[#111111] mb-4">Ganti Metode Pembayaran</h3>
        <form action="{{ route('customer.booking.change-payment', $booking) }}" method="POST" class="space-y-3">
            @csrf
            <label class="flex items-center gap-3 p-3 border border-[#E5E5E5] rounded-[12px] cursor-pointer hover:bg-[#F5F5F5] transition">
                <input type="radio" name="new_method" value="midtrans" class="text-[#C1121F] focus:ring-[#C1121F]"> 💳 Bayar Online
            </label>
            <label class="flex items-center gap-3 p-3 border border-[#E5E5E5] rounded-[12px] cursor-pointer hover:bg-[#F5F5F5] transition">
                <input type="radio" name="new_method" value="cash" class="text-[#C1121F] focus:ring-[#C1121F]"> 🏪 Warung GoMad
            </label>
            @if($booking->schedule->allow_cod)
            <label class="flex items-center gap-3 p-3 border border-[#E5E5E5] rounded-[12px] cursor-pointer hover:bg-[#F5F5F5] transition">
                <input type="radio" name="new_method" value="cod" class="text-[#C1121F] focus:ring-[#C1121F]"> 🚗 COD
            </label>
            @endif
            <div class="flex gap-3 mt-4">
                <button type="submit" class="flex-1 btn-gomad-primary">Ganti</button>
                <button type="button" onclick="document.getElementById('changePaymentModal').style.display='none'" class="flex-1 border border-[#E5E5E5] py-2 rounded-[12px] font-medium hover:bg-[#F5F5F5] transition">Batal</button>
            </div>
        </form>
    </div>
</div>
@endif


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
@endsection