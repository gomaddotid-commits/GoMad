@extends('layouts.customer')

@section('title', 'Home')
@section('content')

@php
    $user = auth()->user();
    
    $recentBookings = \App\Models\Booking::with(['schedule.route', 'originStop', 'destinationStop', 'payment'])
        ->where('customer_id', $user->id)
        ->latest()
        ->limit(5)
        ->get();
    
    $recentRentals = \App\Models\Rental::with(['vehicle', 'agency'])
        ->where('customer_id', $user->id)
        ->latest()
        ->limit(5)
        ->get();
    
    $activePromos = \App\Models\Promo::active()
        ->where(function($q) {
            $q->where('module', 'travel')->orWhere('module', 'all');
        })
        ->latest()
        ->limit(4)
        ->get();
    
    $rentalService = app(\App\Services\RentalService::class);
    $docStatus = $rentalService->getCustomerDocumentStatus($user);
    $canSelfDrive = $docStatus['is_complete_for_self_drive'];
    
    $referralCode = \App\Models\ReferralCode::where('user_id', $user->id)->first();
    if (!$referralCode) {
        $referralCode = app(\App\Services\PromoService::class)->generateReferralCode($user);
    }
@endphp

<div class="container-magazine py-6 md:py-8" x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 800)">
    
    {{-- Welcome Section --}}
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-[#111827]">Halo, {{ $user->name }}! 👋</h1>
                <p class="text-gray-500 font-light mt-1">Mau kemana atau butuh kendaraan hari ini?</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('customer.profile') }}" 
                   class="flex items-center gap-2 px-4 py-2 bg-white border border-[#E5E7EB] rounded-[12px] text-sm font-medium hover:bg-[#F9FAFB] transition shadow-sm">
                    <div class="w-8 h-8 rounded-full bg-[#BA1826] flex items-center justify-center text-white text-xs font-bold">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <span class="hidden sm:inline text-[#111827]">Profil Saya</span>
                </a>
            </div>
        </div>
    </div>

    {{-- CTA CARDS --}}
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <a href="{{ route('customer.search') }}" 
           class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 shadow-gomad hover:border-[#BA1826] hover:shadow-gomad-lg transition-all group">
            <div class="flex items-start gap-5">
                <div class="w-16 h-16 bg-[#BA1826]/5 rounded-[12px] flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0 border border-[#E5E7EB]">
                    <span class="text-3xl">🚐</span>
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-bold text-[#111827] mb-1">Cari Travel Door to Door</h2>
                    <p class="text-sm text-gray-500 font-light mb-3">Booking tiket travel antar kota. Dijemput di rumah, diantar ke tujuan.</p>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <span class="px-2 py-0.5 bg-[#F9FAFB] text-[10px] font-mono uppercase tracking-wider rounded-full text-gray-600 border border-[#E5E7EB]">Ekonomi</span>
                        <span class="px-2 py-0.5 bg-[#F9FAFB] text-[10px] font-mono uppercase tracking-wider rounded-full text-gray-600 border border-[#E5E7EB]">Premium</span>
                        <span class="px-2 py-0.5 bg-[#F9FAFB] text-[10px] font-mono uppercase tracking-wider rounded-full text-gray-600 border border-[#E5E7EB]">Charter</span>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[#BA1826] font-semibold text-sm group-hover:gap-2 transition-all">
                        Cari Jadwal Travel
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </div>
            </div>
        </a>

        <a href="{{ route('customer.rental.browse') }}" 
           class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 shadow-gomad hover:border-[#BA1826] hover:shadow-gomad-lg transition-all group">
            <div class="flex items-start gap-5">
                <div class="w-16 h-16 bg-[#BA1826]/5 rounded-[12px] flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0 border border-[#E5E7EB]">
                    <span class="text-3xl">🚗</span>
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-bold text-[#111827] mb-1">Sewa Rental Mobil</h2>
                    <p class="text-sm text-gray-500 font-light mb-3">Sewa mobil lepas kunci atau dengan supir. Bebas eksplorasi!</p>
                    <div class="flex flex-wrap gap-2 mb-3">
                        <span class="px-2 py-0.5 {{ $canSelfDrive ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-[#F9FAFB] text-gray-400 border-[#E5E7EB]' }} text-[10px] font-mono uppercase tracking-wider rounded-full border">
                            Lepas Kunci {{ $canSelfDrive ? '✅' : '🔒' }}
                        </span>
                        <span class="px-2 py-0.5 bg-green-50 text-green-700 text-[10px] font-mono uppercase tracking-wider rounded-full border border-green-200">+Supir</span>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[#BA1826] font-semibold text-sm group-hover:gap-2 transition-all">
                        Cari Mobil Rental
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </span>
                </div>
            </div>
            @if(!$canSelfDrive)
            <div class="mt-3 bg-yellow-50 border border-yellow-200 rounded-lg p-2 text-xs text-yellow-700">
                ⚠️ Lengkapi <a href="{{ route('customer.documents') }}" class="text-[#BA1826] underline font-medium">dokumen KTP, SIM & Selfie</a> untuk bisa Lepas Kunci
            </div>
            @endif
        </a>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        {{-- Kolom Kiri: Riwayat + Promo --}}
        <div class="lg:col-span-2 space-y-8">
            
            {{-- ✅ Riwayat Travel --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-[#111827] flex items-center gap-2">
                        <span>🚐</span> Riwayat Perjalanan Travel
                    </h2>
                    <a href="{{ route('customer.bookings') }}" class="text-sm text-[#BA1826] hover:underline font-medium">Lihat Semua →</a>
                </div>

                {{-- ✅ SKELETON LOADING --}}
                <div x-show="loading" x-cloak>
                    <x-skeleton-card type="booking" :count="5" />
                </div>
                <div x-show="!loading" x-cloak>

                @if($recentBookings->isEmpty())
                <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-8 text-center shadow-sm">
                    <div class="w-12 h-12 bg-[#F9FAFB] rounded-[10px] flex items-center justify-center mx-auto mb-3 border border-[#E5E7EB]">
                        <span class="text-xl">🎫</span>
                    </div>
                    <p class="text-gray-500 font-light mb-3">Belum ada riwayat perjalanan.</p>
                    <a href="{{ route('customer.search') }}" class="text-[#BA1826] text-sm font-medium hover:underline">Cari Jadwal Travel →</a>
                </div>
                @else
                <div class="space-y-3">
                    @foreach($recentBookings as $booking)
                    <a href="{{ route('customer.booking.show', $booking) }}" 
                       class="block bg-white border border-[#E5E7EB] rounded-[12px] p-4 shadow-sm hover:border-[#BA1826] transition-colors">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-[#F9FAFB] flex items-center justify-center flex-shrink-0 border border-[#E5E7EB]">
                                    <span class="text-lg">
                                        @if(in_array($booking->status, ['paid', 'on_going', 'completed'])) ✅
                                        @elseif($booking->status == 'cancelled') ❌
                                        @else ⏳
                                        @endif
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-bold font-mono text-[#111827] text-sm">{{ $booking->booking_code }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                            @if(in_array($booking->status, ['paid', 'on_going', 'completed'])) bg-green-50 text-green-700 border-green-200
                                            @elseif($booking->status == 'cancelled') bg-red-50 text-red-700 border-red-200
                                            @else bg-yellow-50 text-yellow-700 border-yellow-200 @endif">
                                            {{ $booking->status_label }}
                                        </span>
                                    </div>
                                    @if($booking->originStop && $booking->destinationStop)
                                    <p class="text-sm text-[#111827] font-medium truncate">{{ $booking->originStop->city_name }} → {{ $booking->destinationStop->city_name }}</p>
                                    @endif
                                    @if($booking->schedule)
                                    <p class="text-xs text-gray-500 font-light mt-0.5">
                                        📅 {{ $booking->schedule->departure_date->format('d M Y') }} | 🕐 {{ $booking->schedule->departure_time }}
                                    </p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="font-bold text-[#BA1826] font-mono">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</p>
                                <p class="text-xs text-gray-400 font-light">{{ $booking->total_passengers }} pax</p>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @endif
                </div>
            </div>

            {{-- ✅ Riwayat Rental --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-[#111827] flex items-center gap-2">
                        <span>🚗</span> Riwayat Sewa Rental
                    </h2>
                    <a href="{{ route('customer.rentals') }}" class="text-sm text-[#BA1826] hover:underline font-medium">Lihat Semua →</a>
                </div>

                {{-- ✅ SKELETON LOADING --}}
                <div x-show="loading" x-cloak>
                    <x-skeleton-card type="rental" :count="5" />
                </div>
                <div x-show="!loading" x-cloak>

                @if($recentRentals->isEmpty())
                <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-8 text-center shadow-sm">
                    <div class="w-12 h-12 bg-[#F9FAFB] rounded-[10px] flex items-center justify-center mx-auto mb-3 border border-[#E5E7EB]">
                        <span class="text-xl">🚗</span>
                    </div>
                    <p class="text-gray-500 font-light mb-3">Belum ada riwayat sewa rental.</p>
                    <a href="{{ route('customer.rental.browse') }}" class="text-[#BA1826] text-sm font-medium hover:underline">Cari Mobil Rental →</a>
                </div>
                @else
                <div class="space-y-3">
                    @foreach($recentRentals as $rental)
                    <a href="{{ route('customer.rental.show', $rental) }}" 
                       class="block bg-white border border-[#E5E7EB] rounded-[12px] p-4 shadow-sm hover:border-[#BA1826] transition-colors">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="w-12 h-12 rounded-lg bg-[#F9FAFB] overflow-hidden flex-shrink-0 border border-[#E5E7EB]">
                                    @if($rental->vehicle->vehicle_image)
                                    <img src="{{ $rental->vehicle->vehicle_image }}" alt="{{ $rental->vehicle->plate_number }}" class="w-full h-full object-cover">
                                    @else
                                    <div class="w-full h-full flex items-center justify-center text-xl">🚗</div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-bold font-mono text-[#111827] text-sm">{{ $rental->rental_code }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                            @if($rental->status == 'active') bg-indigo-50 text-indigo-700 border-indigo-200
                                            @elseif($rental->status == 'paid') bg-blue-50 text-blue-700 border-blue-200
                                            @elseif($rental->status == 'completed') bg-green-50 text-green-700 border-green-200
                                            @elseif($rental->status == 'cancelled') bg-red-50 text-red-700 border-red-200
                                            @else bg-yellow-50 text-yellow-700 border-yellow-200 @endif">
                                            {{ $rental->status_label }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-[#111827] font-medium">{{ $rental->vehicle->brand }} {{ $rental->vehicle->model }}</p>
                                    <p class="text-xs text-gray-500 font-light">
                                        📅 {{ $rental->start_datetime->format('d M Y H:i') }} | 
                                        🕐 {{ $rental->duration }} {{ $rental->duration_unit == 'hour' ? 'Jam' : 'Hari' }}
                                        <span class="ml-2 text-[10px] font-mono {{ $rental->type == 'self_drive' ? 'text-blue-600' : 'text-green-600' }}">
                                            {{ $rental->type == 'self_drive' ? '🚗 Lepas Kunci' : '👨‍✈️ Dengan Supir' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="font-bold text-[#BA1826] font-mono">Rp {{ number_format($rental->total_price, 0, ',', '.') }}</p>
                                <p class="text-xs text-gray-400 font-light">{{ $rental->agency->agency_name ?? '-' }}</p>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @endif
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Promo & Akses Cepat --}}
        <div class="space-y-6">
            
            {{-- ✅ Promo Aktif --}}
            <div>
                <h2 class="text-lg font-bold text-[#111827] mb-4 flex items-center gap-2">
                    <span>🎫</span> Promo Aktif
                </h2>
                
                {{-- ✅ SKELETON LOADING --}}
                <div x-show="loading" x-cloak>
                    <x-skeleton-card type="promo" :count="4" />
                </div>
                <div x-show="!loading" x-cloak>

                @if($activePromos->isEmpty())
                <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 text-center shadow-sm">
                    <span class="text-3xl block mb-2">🎫</span>
                    <p class="text-sm text-gray-500 font-light">Belum ada promo tersedia.</p>
                </div>
                @else
                <div class="space-y-3">
                    @foreach($activePromos as $promo)
                    <a href="{{ route('customer.search') }}" 
                       class="block bg-white border border-[#E5E7EB] rounded-[12px] overflow-hidden shadow-sm hover:border-[#BA1826] transition-colors group">
                        @if($promo->image)
                        <div class="h-32 bg-[#F9FAFB] overflow-hidden">
                            <img src="{{ $promo->image_url }}" alt="{{ $promo->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                        @endif
                        <div class="p-4">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                    @if($promo->module == 'travel') bg-blue-50 text-blue-700 border-blue-200
                                    @elseif($promo->module == 'rental') bg-orange-50 text-orange-700 border-orange-200
                                    @else bg-purple-50 text-purple-700 border-purple-200 @endif">
                                    {{ $promo->module_label }}
                                </span>
                            </div>
                            <h3 class="font-bold text-[#111827] text-sm">{{ $promo->name }}</h3>
                            <p class="text-xs text-gray-500 font-light mt-1 line-clamp-2">{{ $promo->description }}</p>
                            <div class="flex items-center justify-between mt-3 pt-3 border-t border-[#E5E7EB]">
                                <div>
                                    <span class="text-[#BA1826] font-bold text-lg">{{ $promo->discount_percent }}%</span>
                                    <span class="text-gray-400 text-xs font-light ml-1">s/d Rp {{ number_format($promo->max_discount, 0, ',', '.') }}</span>
                                </div>
                                <span class="text-[10px] font-mono text-gray-400">{{ $promo->end_date->format('d M Y') }}</span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @endif
                </div>
            </div>

            {{-- ✅ Akses Cepat --}}
            <div>
                <h2 class="text-lg font-bold text-[#111827] mb-4 flex items-center gap-2">
                    <span>⚡</span> Akses Cepat
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <a href="{{ route('customer.search') }}" 
                       class="bg-white border border-[#E5E7EB] rounded-[12px] p-4 text-center hover:border-[#BA1826] transition-colors shadow-sm group">
                        <span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">🔍</span>
                        <span class="text-xs font-semibold text-[#111827] block">Cari Travel</span>
                        <span class="text-[10px] text-gray-400 font-light">Jadwal & Booking</span>
                    </a>
                    <a href="{{ route('customer.rental.browse') }}" 
                       class="bg-white border border-[#E5E7EB] rounded-[12px] p-4 text-center hover:border-[#BA1826] transition-colors shadow-sm group">
                        <span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">🚗</span>
                        <span class="text-xs font-semibold text-[#111827] block">Cari Rental</span>
                        <span class="text-[10px] text-gray-400 font-light">Mobil & Supir</span>
                    </a>
                    <a href="{{ route('customer.bookings') }}" 
                       class="bg-white border border-[#E5E7EB] rounded-[12px] p-4 text-center hover:border-[#BA1826] transition-colors shadow-sm group">
                        <span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">🎫</span>
                        <span class="text-xs font-semibold text-[#111827] block">Booking Saya</span>
                        <span class="text-[10px] text-gray-400 font-light">{{ $recentBookings->count() }} travel</span>
                    </a>
                    <a href="{{ route('customer.rentals') }}" 
                       class="bg-white border border-[#E5E7EB] rounded-[12px] p-4 text-center hover:border-[#BA1826] transition-colors shadow-sm group">
                        <span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">📋</span>
                        <span class="text-xs font-semibold text-[#111827] block">Rental Saya</span>
                        <span class="text-[10px] text-gray-400 font-light">{{ $recentRentals->count() }} rental</span>
                    </a>
                    <a href="{{ route('customer.documents') }}" 
                       class="bg-white border border-[#E5E7EB] rounded-[12px] p-4 text-center hover:border-[#BA1826] transition-colors shadow-sm group">
                        <span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">📄</span>
                        <span class="text-xs font-semibold text-[#111827] block">Dokumen</span>
                        <span class="text-[10px] text-gray-400 font-light">{{ $canSelfDrive ? '✅ Lengkap' : '⚠️ Perlu' }}</span>
                    </a>
                    <a href="{{ route('customer.profile') }}" 
                       class="bg-white border border-[#E5E7EB] rounded-[12px] p-4 text-center hover:border-[#BA1826] transition-colors shadow-sm group">
                        <span class="text-2xl block mb-2 group-hover:scale-110 transition-transform">👤</span>
                        <span class="text-xs font-semibold text-[#111827] block">Profil</span>
                        <span class="text-[10px] text-gray-400 font-light">{{ $user->phone ? '✅' : '⚠️' }} Akun</span>
                    </a>
                </div>
            </div>

            {{-- ✅ Referral Card --}}
            <div class="bg-gradient-to-br from-[#BA1826] to-[#8A0F18] rounded-[12px] p-5 text-white shadow-gomad">
                <h3 class="font-bold text-lg mb-2">🎁 Ajak Teman, Dapat Diskon!</h3>
                <p class="text-sm text-white/80 font-light mb-4">Bagikan kode referral dan dapatkan diskon hingga 50%!</p>
                <div class="bg-white/10 backdrop-blur rounded-[10px] p-3 mb-3 text-center border border-white/10">
                    <p class="text-[10px] font-mono uppercase tracking-wider text-white/70 mb-1">Kode Referral Anda</p>
                    <p class="text-2xl font-mono font-bold tracking-widest">{{ $referralCode->code }}</p>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button onclick="copyReferralCode()" 
                            class="bg-white/20 text-white py-2 rounded-[10px] text-xs font-semibold hover:bg-white/30 transition text-center">
                        📋 Copy Kode
                    </button>
                    @php $waText = "Daftar GoMad pakai kode referral saya: *" . $referralCode->code . "*\n\nDaftar di: " . route('register', ['ref' => $referralCode->code]); @endphp
                    <a href="https://wa.me/?text={{ rawurlencode($waText) }}" target="_blank"
                       class="bg-green-500 text-white py-2 rounded-[10px] text-xs font-semibold hover:bg-green-600 transition text-center">
                        💬 Share WA
                    </a>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-3">
                    <div class="bg-white/10 rounded-[8px] p-2 text-center border border-white/10">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-white/70">Mengajak</p>
                        <p class="text-lg font-bold">{{ $referralCode->total_referred }}</p>
                    </div>
                    <div class="bg-white/10 rounded-[8px] p-2 text-center border border-white/10">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-white/70">Berhasil</p>
                        <p class="text-lg font-bold">{{ $referralCode->successful_referrals }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyReferralCode() {
    navigator.clipboard.writeText('{{ $referralCode->code }}');
    alert('Kode referral berhasil dicopy!');
}
</script>
@endpush
@endsection