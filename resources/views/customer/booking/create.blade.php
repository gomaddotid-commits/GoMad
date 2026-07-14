@extends('layouts.customer')

@section('title', 'Booking Travel')
@section('content')
@php
    $scheduleParam = request()->route('schedule');
    
    if ($scheduleParam instanceof \App\Models\Schedule) {
        $schedule = $scheduleParam;
        $schedule->load(['route.stops', 'agency', 'vehicle', 'scheduleStops.routeStop', 'routePricing']);
    } else {
        $scheduleId = is_object($scheduleParam) ? $scheduleParam->id : (int) $scheduleParam;
        $schedule = \App\Models\Schedule::with([
            'route.stops', 'agency', 'vehicle', 'scheduleStops.routeStop', 'routePricing'
        ])->findOrFail($scheduleId);
    }
    
    $scheduleService = app(\App\Services\ScheduleService::class);
    $availablePickups = $scheduleService->getAvailablePickupStops($schedule);
    
    $scheduleData = [
        'id' => $schedule->id,
        'agency_name' => $schedule->agency->agency_name ?? 'Agency',
        'agency_logo' => $schedule->agency->logo ? $schedule->agency->logo : null,
        'agency_rating' => (float) ($schedule->agency->rating ?? 0),
        'route_name' => $schedule->route->route_name ?? 'Rute',
        'vehicle' => ($schedule->vehicle->plate_number ?? '-') . ' - ' . ($schedule->vehicle->brand ?? '') . ' ' . ($schedule->vehicle->model ?? ''),
        'departure_date' => $schedule->departure_date ? $schedule->departure_date->format('d M Y') : '-',
        'departure_time' => $schedule->departure_time ?? '-',
        'travel_class' => $schedule->travel_class ?? 'economy',
        'base_price' => (float) ($schedule->price_per_seat ?? 0),
        'available_pickups' => $availablePickups,
    ];
    
    $user = auth()->user();
    $userData = ['name' => $user->name, 'phone' => $user->phone ?? ''];
@endphp

<div class="max-w-4xl mx-auto px-4 py-8" id="bookingApp">
    
    {{-- Header Info --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 mb-6 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-[#F5F5F5] flex items-center justify-center text-xl overflow-hidden flex-shrink-0 border border-[#E5E5E5]">
                @if($schedule->agency && $schedule->agency->logo)
                <img src="{{ $schedule->agency->logo }}" alt="Logo" class="w-full h-full object-cover">
                @else
                <span>🏢</span>
                @endif
            </div>
            <div class="min-w-0">
                <h2 class="font-bold text-lg text-[#111111] truncate">{{ $schedule->agency->agency_name ?? 'Agency' }}</h2>
                <p class="text-sm text-gray-500 font-light">
                    ⭐ {{ number_format((float) ($schedule->agency->rating ?? 0), 1) }} 
                    | 🚐 {{ $schedule->vehicle->plate_number ?? '-' }}
                </p>
                <p class="text-sm text-gray-500 font-light">
                    📅 {{ $schedule->departure_date ? $schedule->departure_date->format('d M Y') : '-' }} 
                    | 🕐 {{ $schedule->departure_time ?? '-' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 mb-6 shadow-sm">
        <div class="flex justify-between text-xs md:text-sm mb-2">
            <span class="step-indicator font-bold text-[#C1121F]" data-step="1">1. Pilih Naik</span>
            <span class="step-indicator text-gray-400 hidden sm:inline" data-step="2">2. Alamat Jemput</span>
            <span class="step-indicator text-gray-400 hidden sm:inline" data-step="3">3. Pilih Turun</span>
            <span class="step-indicator text-gray-400" data-step="4">4. Konfirmasi</span>
        </div>
        <div class="bg-[#E5E5E5] rounded-full h-2">
            <div id="progressBar" class="bg-[#C1121F] rounded-full h-2 transition-all duration-300" style="width: 25%"></div>
        </div>
    </div>

    {{-- STEP 1: Pilih Kota Penjemputan --}}
    <div id="step1" class="step-content">
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
            <h2 class="text-xl font-bold text-[#111111] mb-2">Pilih Kota Penjemputan</h2>
            <p class="text-gray-500 font-light mb-6">Pilih kota di mana Anda akan dijemput</p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="pickupGrid"></div>
            <div class="mt-6 text-center">
                <button type="button" onclick="goToStep2()" id="btnStep1" 
                        class="bg-[#E5E5E5] text-gray-500 px-8 py-3 rounded-[12px] font-semibold cursor-not-allowed transition" disabled>Lanjut</button>
            </div>
        </div>
    </div>

    {{-- STEP 2: Alamat Penjemputan --}}
    <div id="step2" class="step-content" style="display:none;">
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
            <h2 class="text-xl font-bold text-[#111111] mb-2">Alamat Penjemputan</h2>
            <p class="text-gray-500 font-light mb-6">Isi alamat lengkap penjemputan di <strong id="pickupCityName" class="text-[#C1121F]">-</strong></p>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Alamat Lengkap <span class="text-[#C1121F]">*</span></label>
                    <textarea id="pickupAddress" rows="3" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" 
                            placeholder="Jl. Trunojoyo No. 10, RT/RW, Kelurahan, Kecamatan, Kabupaten"></textarea>
                    <button type="button" onclick="getCurrentLocation('pickup')"
                            class="mt-2 text-sm text-[#C1121F] hover:text-[#111111] font-medium flex items-center gap-1 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Gunakan Lokasi Saat Ini
                    </button>
                    <p id="pickupLocationStatus" class="text-xs mt-1 hidden font-light"></p>
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Link Google Maps</label>
                    <input type="url" id="pickupMapsLink" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" 
                        placeholder="https://maps.google.com/?q=..." readonly>
                    <p class="text-[10px] text-gray-400 mt-1 font-light">Terisi otomatis saat menggunakan lokasi saat ini</p>
                </div>
            </div>
            <div class="mt-6 flex gap-4 justify-center">
                <button type="button" onclick="goToStep1()" class="border border-[#E5E5E5] text-gray-700 px-6 py-3 rounded-[12px] font-semibold hover:bg-[#F5F5F5] transition">Kembali</button>
                <button type="button" onclick="goToStep3()" id="btnStep2" class="bg-[#C1121F] text-white px-8 py-3 rounded-[12px] font-semibold hover:bg-[#8A0F18] transition">Lanjut</button>
            </div>
        </div>
    </div>

    {{-- STEP 3: Pilih Kota Tujuan --}}
    <div id="step3" class="step-content" style="display:none;">
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
            <h2 class="text-xl font-bold text-[#111111] mb-2">Pilih Kota Tujuan</h2>
            <p class="text-gray-500 font-light mb-6">Pilih kota tujuan Anda (setelah <strong id="pickupCityName3" class="text-[#C1121F]">-</strong>)</p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="dropoffGrid"></div>
            <div id="priceDetail" class="mt-6 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4" style="display:none;">
                <h4 class="font-mono uppercase tracking-wider text-xs font-bold text-[#C1121F] mb-2">Harga per Orang</h4>
                <div class="flex justify-between text-sm">
                    <span id="priceRoute" class="text-[#111111]">-</span>
                    <span class="font-bold text-[#C1121F] font-mono" id="pricePerPerson">-</span>
                </div>
            </div>
            <div class="mt-6 flex gap-4 justify-center">
                <button type="button" onclick="goToStep2()" class="border border-[#E5E5E5] text-gray-700 px-6 py-3 rounded-[12px] font-semibold hover:bg-[#F5F5F5] transition">Kembali</button>
                <button type="button" onclick="goToStep4()" id="btnStep3" 
                        class="bg-[#E5E5E5] text-gray-500 px-8 py-3 rounded-[12px] font-semibold cursor-not-allowed transition" disabled>Lanjut</button>
            </div>
        </div>
    </div>

    {{-- STEP 4: Data Penumpang & Konfirmasi --}}
    <div id="step4" class="step-content" style="display:none;">
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
            <h2 class="text-xl font-bold text-[#111111] mb-2">Data Penumpang</h2>
            <p class="text-gray-500 font-light mb-6">Isi data penumpang untuk perjalanan ini</p>
            
            {{-- Alamat Tujuan --}}
            <div class="mb-6">
                <h3 class="font-mono uppercase tracking-wider text-xs font-semibold text-[#111111] mb-2">Alamat Tujuan di <strong id="dropoffCityName4" class="text-[#C1121F]">-</strong></h3>
                <textarea id="destinationAddress" rows="2" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition mb-2" 
                        placeholder="Alamat lengkap tujuan"></textarea>
                <button type="button" onclick="getCurrentLocation('destination')"
                        class="mb-2 text-sm text-[#C1121F] hover:text-[#111111] font-medium flex items-center gap-1 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Gunakan Lokasi Saat Ini
                </button>
                <p id="destinationLocationStatus" class="text-xs mt-1 hidden font-light"></p>
                <input type="url" id="destinationMapsLink" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" 
                    placeholder="Link Google Maps tujuan (opsional)" readonly>
                <p class="text-[10px] text-gray-400 mt-1 font-light">Terisi otomatis saat menggunakan lokasi saat ini</p>
            </div>

            {{-- Data Penumpang --}}
            <div class="mb-6">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-mono uppercase tracking-wider text-xs font-semibold text-[#111111]">Penumpang</h3>
                    <button type="button" onclick="useAccountOwner()" id="btnUseOwner" 
                            class="text-[#C1121F] text-sm hover:underline font-medium">
                        👤 Gunakan Pemilik Akun
                    </button>
                </div>
                <div id="passengerList" class="space-y-3"></div>
                <button type="button" onclick="addPassenger()" class="mt-3 text-[#C1121F] text-sm hover:underline font-medium">
                    + Tambah Penumpang Lain
                </button>
                <p class="text-[10px] text-gray-400 mt-1 font-light">Maksimal 10 penumpang</p>
            </div>

            {{-- Ringkasan Singkat --}}
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4 mb-6">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 font-light">{{ $schedule->route->route_name }}</span>
                    <span class="font-semibold text-[#111111] font-mono">{{ $schedule->departure_date?->format('d M Y') }} {{ $schedule->departure_time }}</span>
                </div>
                <div class="flex justify-between text-sm mt-1">
                    <span class="text-gray-500 font-light">Penumpang</span>
                    <span class="font-semibold text-[#111111]" id="summaryPassengers">0 orang</span>
                </div>
            </div>

            {{-- ═══════════════════════════════════ --}}
            {{-- PERSETUJUAN SYARAT & KETENTUAN TRAVEL --}}
            {{-- ═══════════════════════════════════ --}}
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 mb-6 shadow-sm">
                {{-- Checkbox 1: Syarat & Ketentuan --}}
                <label class="flex items-start gap-3 cursor-pointer mb-3">
                    <input type="checkbox" 
                           id="agreeTerms" 
                           class="mt-0.5 w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]"
                           onchange="toggleSubmitButton()">
                    <div class="flex-1">
                        <span class="text-sm font-medium text-[#111111]">
                            Saya telah membaca dan menyetujui 
                        </span>
                        <button type="button" 
                                onclick="openTermsModal()" 
                                class="text-[#C1121F] underline font-medium text-sm hover:text-[#8A0F18] transition">
                            Syarat & Ketentuan
                        </button>
                        <span class="text-sm font-medium text-[#111111]"> perjalanan travel</span>
                    </div>
                </label>

                {{-- Checkbox 2: Kebijakan Pembatalan --}}
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" 
                           id="agreeRefund" 
                           class="mt-0.5 w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]"
                           onchange="toggleSubmitButton()">
                    <div class="flex-1">
                        <span class="text-sm font-medium text-[#111111]">
                            Saya memahami dan menyetujui 
                        </span>
                        <button type="button" 
                                onclick="openRefundModal()" 
                                class="text-[#C1121F] underline font-medium text-sm hover:text-[#8A0F18] transition">
                            Kebijakan Pembatalan & Refund
                        </button>
                    </div>
                </label>
                <div class="mt-2 ml-8 text-xs text-gray-500 font-light">
                    <p>• Pembatalan setelah bayar: biaya <strong class="text-[#C1121F]">25%</strong></p>
                    <p>• Tidak bisa dibatalkan jika &lt; 24 jam sebelum keberangkatan</p>
                </div>
            </div>

            {{-- TOMBOL SUBMIT (disabled by default) --}}
            <div class="flex gap-4 justify-center">
                <button type="button" onclick="goToStep3()" class="border border-[#E5E5E5] text-gray-700 px-6 py-3 rounded-[12px] font-semibold hover:bg-[#F5F5F5] transition">Kembali</button>
                <button type="button" onclick="submitBooking()" id="btnSubmit" disabled
                        class="bg-[#E5E5E5] text-gray-500 px-8 py-3 rounded-[12px] font-bold text-lg cursor-not-allowed transition">
                    🎫 BUAT BOOKING
                </button>
            </div>
        </div>
    </div>

    {{-- Form tersembunyi --}}
    <form id="bookingForm" action="{{ route('customer.booking.store') }}" method="POST" style="display:none;">
        @csrf
        <input type="hidden" name="schedule_id" value="{{ $schedule->id }}">
        <input type="hidden" name="origin_stop_id" id="fOriginStopId">
        <input type="hidden" name="destination_stop_id" id="fDestStopId">
        <input type="hidden" name="pickup_address" id="fPickupAddress">
        <input type="hidden" name="pickup_maps_link" id="fPickupMapsLink">
        <input type="hidden" name="destination_address" id="fDestAddress">
        <input type="hidden" name="destination_maps_link" id="fDestMapsLink">
        <div id="fPassengers"></div>
    </form>
</div>

{{-- ═══════════════════════════════════ --}}
{{-- MODAL: Syarat & Ketentuan Travel --}}
{{-- ═══════════════════════════════════ --}}
<div id="termsModal" class="fixed inset-0 bg-[#111111]/50 z-50 hidden items-center justify-center p-4" style="display:none;">
    <div class="bg-white rounded-[12px] shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-y-auto border border-[#E5E5E5]">
        <div class="sticky top-0 bg-white border-b border-[#E5E5E5] p-6 rounded-t-[12px] z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-[#C1121F]/10 rounded-[12px] flex items-center justify-center text-xl border border-[#E5E5E5]">📄</div>
                <div>
                    <h3 class="font-bold text-lg text-[#111111]">Syarat & Ketentuan Perjalanan</h3>
                    <p class="text-xs text-gray-500 font-light">GoMad Travel</p>
                </div>
            </div>
        </div>
        
        <div class="p-6 space-y-4">
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4">
                <p class="text-xs text-gray-500 font-light mb-3">
                    Dengan melakukan booking, Anda menyetujui syarat dan ketentuan berikut:
                </p>
                <ol class="list-decimal list-inside space-y-3 text-sm text-[#111111]">
                    <li class="font-light leading-relaxed">Penumpang wajib hadir di lokasi penjemputan minimal 15 menit sebelum jadwal keberangkatan.</li>
                    <li class="font-light leading-relaxed">Pembatalan yang dilakukan kurang dari 24 jam sebelum keberangkatan tidak dapat direfund.</li>
                    <li class="font-light leading-relaxed">Penumpang wajib membawa identitas diri (KTP/SIM) yang sesuai dengan data booking.</li>
                    <li class="font-light leading-relaxed">Bagasi maksimal sesuai ketentuan kelas perjalanan (Ekonomi: 15kg, Premium: 20kg).</li>
                    <li class="font-light leading-relaxed">Agency berhak membatalkan perjalanan jika terjadi force majeure dengan refund penuh.</li>
                    <li class="font-light leading-relaxed">Penumpang dilarang membawa barang terlarang atau berbahaya selama perjalanan.</li>
                    <li class="font-light leading-relaxed">Keterlambatan yang disebabkan oleh penumpang bukan tanggung jawab agency.</li>
                </ol>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" 
                           id="agreeTermsInModal" 
                           class="mt-0.5 w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]"
                           onchange="syncCheckbox('agreeTerms', 'agreeTermsInModal')">
                    <span class="text-sm text-[#111111] font-light">
                        Saya telah membaca, memahami, dan menyetujui seluruh syarat & ketentuan di atas
                    </span>
                </label>
            </div>
        </div>
        
        <div class="sticky bottom-0 bg-white border-t border-[#E5E5E5] p-4 rounded-b-[12px] flex gap-3 justify-end">
            <button type="button" 
                    onclick="closeTermsModal()" 
                    class="px-6 py-2.5 border border-[#E5E5E5] rounded-[12px] text-sm font-medium hover:bg-[#F5F5F5] transition">
                Tutup
            </button>
            <button type="button" 
                    onclick="agreeAndCloseTerms()" 
                    class="px-6 py-2.5 bg-[#C1121F] text-white rounded-[12px] text-sm font-semibold hover:bg-[#8A0F18] transition">
                Setuju & Tutup
            </button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════ --}}
{{-- MODAL: Kebijakan Pembatalan Travel --}}
{{-- ═══════════════════════════════════ --}}
<div id="refundModal" class="fixed inset-0 bg-[#111111]/50 z-50 hidden items-center justify-center p-4" style="display:none;">
    <div class="bg-white rounded-[12px] shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-y-auto border border-[#E5E5E5]">
        <div class="sticky top-0 bg-white border-b border-[#E5E5E5] p-6 rounded-t-[12px] z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-50 rounded-[12px] flex items-center justify-center text-xl border border-orange-200">🔄</div>
                <div>
                    <h3 class="font-bold text-lg text-[#111111]">Kebijakan Pembatalan & Refund</h3>
                    <p class="text-xs text-gray-500 font-light">Mohon dibaca dengan seksama</p>
                </div>
            </div>
        </div>
        
        <div class="p-6 space-y-4">
            <div class="bg-red-50 border border-red-200 rounded-[12px] p-4">
                <h4 class="font-mono uppercase tracking-wider text-xs font-bold text-red-800 mb-2">⚠️ Biaya Pembatalan</h4>
                <div class="text-sm text-red-700 space-y-2 font-light">
                    <p>• <strong>Sebelum pembayaran:</strong> Gratis, tidak ada biaya.</p>
                    <p>• <strong>Setelah pembayaran (&gt; 24 jam sebelum berangkat):</strong> Dikenakan biaya <strong>25%</strong> dari total.</p>
                    <p>• <strong>Kurang dari 24 jam sebelum berangkat:</strong> Tidak dapat dibatalkan.</p>
                </div>
            </div>
            
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4">
                <h4 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-2">📋 Kebijakan Refund</h4>
                <ol class="list-decimal list-inside space-y-3 text-sm text-[#111111]">
                    <li class="font-light leading-relaxed">Refund akan diproses dalam 1-14 hari kerja ke rekening yang terdaftar.</li>
                    <li class="font-light leading-relaxed">Biaya pembatalan 25% dipotong dari total pembayaran.</li>
                    <li class="font-light leading-relaxed">Jika agency membatalkan perjalanan, refund 100% akan diberikan.</li>
                    <li class="font-light leading-relaxed">Force majeure (bencana alam, dll): kebijakan khusus berlaku sesuai pemberitahuan.</li>
                </ol>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" 
                           id="agreeRefundInModal" 
                           class="mt-0.5 w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]"
                           onchange="syncCheckbox('agreeRefund', 'agreeRefundInModal')">
                    <span class="text-sm text-[#111111] font-light">
                        Saya telah membaca, memahami, dan menyetujui kebijakan pembatalan & refund di atas
                    </span>
                </label>
            </div>
        </div>
        
        <div class="sticky bottom-0 bg-white border-t border-[#E5E5E5] p-4 rounded-b-[12px] flex gap-3 justify-end">
            <button type="button" 
                    onclick="closeRefundModal()" 
                    class="px-6 py-2.5 border border-[#E5E5E5] rounded-[12px] text-sm font-medium hover:bg-[#F5F5F5] transition">
                Tutup
            </button>
            <button type="button" 
                    onclick="agreeAndCloseRefund()" 
                    class="px-6 py-2.5 bg-[#C1121F] text-white rounded-[12px] text-sm font-semibold hover:bg-[#8A0F18] transition">
                Setuju & Tutup
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
var scheduleData = @json($scheduleData);
var userData = @json($userData);
var availablePickups = scheduleData.available_pickups || [];
var availableDropoffs = [];
var selectedPickup = null;
var selectedDropoff = null;
var hasUsedOwner = false;

document.addEventListener('DOMContentLoaded', function() {
    renderPickupGrid();
    addPassenger();
    toggleSubmitButton(); // Init tombol disabled
});

function renderPickupGrid() {
    var grid = document.getElementById('pickupGrid');
    if (!availablePickups || !availablePickups.length) {
        grid.innerHTML = '<p class="text-gray-500 col-span-full text-center py-4 font-light">Tidak ada kota penjemputan tersedia.</p>';
        return;
    }
    var html = '';
    availablePickups.forEach(function(stop, index) {
        var isFirst = index === 0;
        var bgClass = isFirst ? 'border-[#C1121F] bg-[#C1121F]/5' : 'border-[#E5E5E5] bg-[#F5F5F5]';
        var labelClass = isFirst ? 'bg-[#C1121F]' : 'bg-gray-400';
        var label = isFirst ? 'Kota Asal' : 'Stop ' + (stop.stop_order || index + 1);
        html += '<div class="pickup-card rounded-[12px] border-2 p-4 cursor-pointer transition hover:shadow-md ' + bgClass + '" onclick="selectPickup(this, ' + stop.route_stop_id + ', \'' + stop.city_name.replace(/'/g, "\\'") + '\')">';
        html += '<span class="inline-block px-2 py-0.5 rounded-full text-xs text-white font-medium mb-2 ' + labelClass + '">' + label + '</span>';
        html += '<h3 class="font-bold text-lg text-[#111111]">' + stop.city_name + '</h3>';
        html += '<p class="text-sm mt-1 text-gray-500 font-light">Titik Jemput</p>';
        html += '<p class="text-sm font-semibold text-[#111111] mt-1">Mulai ' + (stop.min_price_formatted || 'Belum ada harga') + '</p>';
        html += '</div>';
    });
    grid.innerHTML = html;
}

function selectPickup(card, stopId, cityName) {
    document.querySelectorAll('.pickup-card').forEach(function(c) { c.classList.remove('ring-4', 'ring-[#C1121F]', 'border-[#C1121F]'); });
    card.classList.add('ring-4', 'ring-[#C1121F]', 'border-[#C1121F]');
    selectedPickup = { id: stopId, city_name: cityName };
    var btn = document.getElementById('btnStep1');
    btn.disabled = false;
    btn.className = 'bg-[#C1121F] text-white px-8 py-3 rounded-[12px] font-semibold hover:bg-[#8A0F18] cursor-pointer transition';
}

function goToStep1() { showStep(1); }
function goToStep2() {
    if (!selectedPickup) return alert('Pilih kota penjemputan!');
    document.getElementById('pickupCityName').textContent = selectedPickup.city_name;
    showStep(2);
}
function goToStep3() {
    var address = document.getElementById('pickupAddress').value.trim();
    if (!address) return alert('Isi alamat penjemputan!');
    document.getElementById('pickupCityName3').textContent = selectedPickup.city_name;
    fetch('/api/v1/schedules/' + scheduleData.id + '/dropoffs/' + selectedPickup.id)
        .then(function(res) { return res.json(); })
        .then(function(data) { availableDropoffs = data.data || []; renderDropoffGrid(); showStep(3); })
        .catch(function() { alert('Gagal memuat data tujuan.'); });
}

function renderDropoffGrid() {
    var grid = document.getElementById('dropoffGrid');
    if (!availableDropoffs || !availableDropoffs.length) {
        grid.innerHTML = '<p class="text-gray-500 col-span-full text-center py-4 font-light">Tidak ada kota tujuan tersedia.</p>';
        return;
    }
    var html = '';
    availableDropoffs.forEach(function(stop, index) {
        var isLast = index === availableDropoffs.length - 1;
        var bgClass = isLast ? 'border-[#C1121F] bg-[#C1121F]/5' : 'border-[#E5E5E5] bg-[#F5F5F5]';
        var labelClass = isLast ? 'bg-[#C1121F]' : 'bg-gray-400';
        var label = isLast ? 'Kota Tujuan' : 'Stop ' + (stop.stop_order || index + 1);
        html += '<div class="dropoff-card rounded-[12px] border-2 p-4 cursor-pointer transition hover:shadow-md ' + bgClass + '" onclick="selectDropoff(this, ' + stop.route_stop_id + ', \'' + stop.city_name.replace(/'/g, "\\'") + '\', ' + (stop.price || 0) + ')">';
        html += '<span class="inline-block px-2 py-0.5 rounded-full text-xs text-white font-medium mb-2 ' + labelClass + '">' + label + '</span>';
        html += '<h3 class="font-bold text-lg text-[#111111]">' + stop.city_name + '</h3>';
        html += '<p class="text-sm mt-1 text-gray-500 font-light">Titik Turun</p>';
        html += '<p class="text-sm font-semibold text-[#111111] mt-1">' + (stop.price_formatted || 'Belum ada harga') + '</p>';
        html += '</div>';
    });
    grid.innerHTML = html;
}

function selectDropoff(card, stopId, cityName, price) {
    document.querySelectorAll('.dropoff-card').forEach(function(c) { c.classList.remove('ring-4', 'ring-[#C1121F]', 'border-[#C1121F]'); });
    card.classList.add('ring-4', 'ring-[#C1121F]', 'border-[#C1121F]');
    selectedDropoff = { id: stopId, city_name: cityName, price: price };
    document.getElementById('priceDetail').style.display = 'block';
    document.getElementById('priceRoute').textContent = (selectedPickup ? selectedPickup.city_name : '') + ' → ' + cityName;
    document.getElementById('pricePerPerson').textContent = 'Rp ' + formatRupiah(price) + ' /orang';
    var btn = document.getElementById('btnStep3');
    btn.disabled = false;
    btn.className = 'bg-[#C1121F] text-white px-8 py-3 rounded-[12px] font-semibold hover:bg-[#8A0F18] cursor-pointer transition';
}

function goToStep4() {
    if (!selectedDropoff) return alert('Pilih kota tujuan!');
    document.getElementById('dropoffCityName4').textContent = selectedDropoff.city_name;
    updateSummary();
    showStep(4);
    toggleSubmitButton(); // Pastikan tombol disabled
}

function addPassenger() {
    var list = document.getElementById('passengerList');
    if (list.querySelectorAll('.passenger-item').length >= 10) return alert('Maksimal 10 penumpang!');
    var div = document.createElement('div');
    div.className = 'passenger-item bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4';
    div.innerHTML = 
        '<div class="flex justify-between items-start mb-2">' +
            '<span class="text-[10px] font-mono uppercase tracking-wider text-gray-500">Penumpang ' + (list.querySelectorAll('.passenger-item').length + 1) + '</span>' +
            '<button type="button" onclick="this.closest(\'.passenger-item\').remove(); updateSummary();" class="text-[#C1121F] text-xs hover:underline">Hapus</button>' +
        '</div>' +
        '<div class="grid grid-cols-3 gap-3">' +
            '<input type="text" class="passenger-name px-3 py-2 border border-[#E5E5E5] rounded-[8px] bg-white text-[#111111]" placeholder="Nama lengkap" required>' +
            '<input type="text" class="passenger-phone px-3 py-2 border border-[#E5E5E5] rounded-[8px] bg-white text-[#111111]" placeholder="No. HP">' +
            '<input type="number" class="passenger-baggage px-3 py-2 border border-[#E5E5E5] rounded-[8px] bg-white text-[#111111]" placeholder="Bagasi (kg)" value="0" min="0">' +
        '</div>';
    list.appendChild(div);
    updateSummary();
}

function useAccountOwner() {
    if (hasUsedOwner) { alert('Pemilik akun sudah digunakan.'); return; }
    var list = document.getElementById('passengerList');
    if (list.querySelectorAll('.passenger-item').length >= 10) return alert('Maksimal 10 penumpang!');
    var items = list.querySelectorAll('.passenger-item');
    var emptyItem = null;
    items.forEach(function(item) { var n = item.querySelector('.passenger-name'); if (!emptyItem && n && !n.value.trim()) emptyItem = item; });
    if (emptyItem) {
        emptyItem.querySelector('.passenger-name').value = userData.name;
        emptyItem.querySelector('.passenger-phone').value = userData.phone;
    } else {
        var div = document.createElement('div');
        div.className = 'passenger-item bg-[#C1121F]/5 border border-[#C1121F] rounded-[12px] p-4';
        div.innerHTML = 
            '<div class="flex justify-between items-start mb-2">' +
                '<span class="text-[10px] font-mono uppercase tracking-wider text-[#C1121F]">👤 Pemilik Akun</span>' +
                '<button type="button" onclick="this.closest(\'.passenger-item\').remove(); hasUsedOwner = false; updateSummary();" class="text-[#C1121F] text-xs hover:underline">Hapus</button>' +
            '</div>' +
            '<div class="grid grid-cols-3 gap-3">' +
                '<input type="text" class="passenger-name px-3 py-2 border border-[#C1121F] rounded-[8px] bg-[#C1121F]/5 text-[#111111]" value="' + userData.name + '" required>' +
                '<input type="text" class="passenger-phone px-3 py-2 border border-[#C1121F] rounded-[8px] bg-[#C1121F]/5 text-[#111111]" value="' + (userData.phone || '') + '">' +
                '<input type="number" class="passenger-baggage px-3 py-2 border border-[#C1121F] rounded-[8px] bg-[#C1121F]/5 text-[#111111]" placeholder="Bagasi (kg)" value="0" min="0">' +
            '</div>';
        list.appendChild(div);
    }
    hasUsedOwner = true;
    document.getElementById('btnUseOwner').textContent = '✅ Pemilik Akun Ditambahkan';
    document.getElementById('btnUseOwner').classList.add('text-[#C1121F]');
    updateSummary();
}

function updateSummary() {
    document.getElementById('summaryPassengers').textContent = document.querySelectorAll('.passenger-item').length + ' orang';
}

function submitBooking() {
    // Validasi checkbox
    if (!document.getElementById('agreeTerms').checked) {
        alert('Anda harus menyetujui Syarat & Ketentuan terlebih dahulu.');
        return;
    }
    
    if (!document.getElementById('agreeRefund').checked) {
        alert('Anda harus menyetujui Kebijakan Pembatalan & Refund terlebih dahulu.');
        return;
    }
    
    var destAddress = document.getElementById('destinationAddress').value.trim();
    if (!destAddress) return alert('Isi alamat tujuan!');
    var passengerItems = document.querySelectorAll('.passenger-item');
    if (!passengerItems.length) return alert('Tambahkan minimal 1 penumpang!');
    var passengers = []; var valid = true;
    passengerItems.forEach(function(item) {
        var name = item.querySelector('.passenger-name').value.trim();
        if (!name) valid = false;
        passengers.push({ name: name, phone: item.querySelector('.passenger-phone').value.trim(), baggage_weight: parseFloat(item.querySelector('.passenger-baggage').value) || 0 });
    });
    if (!valid) return alert('Isi nama semua penumpang!');
    
    document.getElementById('fOriginStopId').value = selectedPickup.id;
    document.getElementById('fDestStopId').value = selectedDropoff.id;
    document.getElementById('fPickupAddress').value = document.getElementById('pickupAddress').value.trim();
    document.getElementById('fPickupMapsLink').value = document.getElementById('pickupMapsLink').value.trim();
    document.getElementById('fDestAddress').value = destAddress;
    document.getElementById('fDestMapsLink').value = document.getElementById('destinationMapsLink').value.trim();
    
    var passengerDiv = document.getElementById('fPassengers');
    passengerDiv.innerHTML = '';
    passengers.forEach(function(p, idx) {
        passengerDiv.innerHTML += '<input type="hidden" name="passengers[' + idx + '][name]" value="' + escapeHtml(p.name) + '">';
        passengerDiv.innerHTML += '<input type="hidden" name="passengers[' + idx + '][phone]" value="' + escapeHtml(p.phone) + '">';
        passengerDiv.innerHTML += '<input type="hidden" name="passengers[' + idx + '][baggage_weight]" value="' + p.baggage_weight + '">';
    });
    
    var btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.textContent = '⏳ Memproses...';
    
    document.getElementById('bookingForm').submit();
}

function showStep(step) {
    document.querySelectorAll('.step-content').forEach(function(el) { el.style.display = 'none'; });
    document.getElementById('step' + step).style.display = 'block';
    document.getElementById('progressBar').style.width = (step * 25) + '%';
    document.querySelectorAll('.step-indicator').forEach(function(el) {
        var elStep = parseInt(el.getAttribute('data-step'));
        el.classList.remove('text-[#C1121F]', 'text-gray-400', 'text-green-600', 'font-bold');
        if (elStep < step) el.classList.add('text-green-600');
        else if (elStep === step) el.classList.add('text-[#C1121F]', 'font-bold');
        else el.classList.add('text-gray-400');
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function formatRupiah(num) { return new Intl.NumberFormat('id-ID').format(num || 0); }
function escapeHtml(text) { if (!text) return ''; return text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

// ═══════════════════════════════════
// MODAL FUNCTIONS
// ═══════════════════════════════════

function openTermsModal() {
    document.getElementById('termsModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeTermsModal() {
    document.getElementById('termsModal').style.display = 'none';
    document.body.style.overflow = '';
}

function openRefundModal() {
    document.getElementById('refundModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeRefundModal() {
    document.getElementById('refundModal').style.display = 'none';
    document.body.style.overflow = '';
}

function agreeAndCloseTerms() {
    document.getElementById('agreeTermsInModal').checked = true;
    document.getElementById('agreeTerms').checked = true;
    toggleSubmitButton();
    closeTermsModal();
}

function agreeAndCloseRefund() {
    document.getElementById('agreeRefundInModal').checked = true;
    document.getElementById('agreeRefund').checked = true;
    toggleSubmitButton();
    closeRefundModal();
}

function syncCheckbox(mainId, modalId) {
    document.getElementById(mainId).checked = document.getElementById(modalId).checked;
    toggleSubmitButton();
}

function toggleSubmitButton() {
    var agreeTerms = document.getElementById('agreeTerms');
    var agreeRefund = document.getElementById('agreeRefund');
    var btnSubmit = document.getElementById('btnSubmit');
    
    if (!agreeTerms || !agreeRefund || !btnSubmit) return;
    
    if (agreeTerms.checked && agreeRefund.checked) {
        btnSubmit.disabled = false;
        btnSubmit.className = 'bg-[#C1121F] text-white px-8 py-3 rounded-[12px] font-bold text-lg hover:bg-[#8A0F18] cursor-pointer transition';
    } else {
        btnSubmit.disabled = true;
        btnSubmit.className = 'bg-[#E5E5E5] text-gray-500 px-8 py-3 rounded-[12px] font-bold text-lg cursor-not-allowed transition';
    }
}

// Tutup modal dengan klik overlay
document.addEventListener('click', function(e) {
    if (e.target.id === 'termsModal') closeTermsModal();
    if (e.target.id === 'refundModal') closeRefundModal();
});

// Tutup modal dengan ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTermsModal();
        closeRefundModal();
    }
});

// ═══════════════════════════════════
// GEOLOCATION
// ═══════════════════════════════════
function getCurrentLocation(type) {
    if (!navigator.geolocation) {
        alert('Browser Anda tidak mendukung geolocation.');
        return;
    }

    var statusEl = document.getElementById(type + 'LocationStatus');
    if (statusEl) {
        statusEl.textContent = '⏳ Mengambil lokasi...';
        statusEl.classList.remove('hidden', 'text-green-600', 'text-red-600', 'text-yellow-600');
        statusEl.classList.add('text-blue-600');
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;
            reverseGeocode(lat, lng, type);
        },
        function(error) {
            var msg = '';
            switch(error.code) {
                case error.PERMISSION_DENIED: msg = 'Akses lokasi ditolak. Izinkan di pengaturan browser.'; break;
                case error.POSITION_UNAVAILABLE: msg = 'Lokasi tidak tersedia.'; break;
                case error.TIMEOUT: msg = 'Timeout mengambil lokasi.'; break;
                default: msg = 'Gagal mengambil lokasi.';
            }

            if (statusEl) {
                statusEl.textContent = '❌ ' + msg;
                statusEl.classList.remove('hidden', 'text-blue-600', 'text-green-600', 'text-yellow-600');
                statusEl.classList.add('text-red-600');
            }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
    );
}

function reverseGeocode(lat, lng, type) {
    var statusEl = document.getElementById(type + 'LocationStatus');

    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&accept-language=id')
        .then(function(res) { return res.json(); })
        .then(function(data) {
            var address = data.display_name || '';

            if (type === 'pickup') {
                var addressField = document.getElementById('pickupAddress');
                if (addressField) addressField.value = address;
                var mapsField = document.getElementById('pickupMapsLink');
                if (mapsField) mapsField.value = 'https://www.google.com/maps?q=' + lat + ',' + lng;
                updateOrCreateHiddenInput('pickup_latitude', lat);
                updateOrCreateHiddenInput('pickup_longitude', lng);
            } else {
                var addressField = document.getElementById('destinationAddress');
                if (addressField) addressField.value = address;
                var mapsField = document.getElementById('destinationMapsLink');
                if (mapsField) mapsField.value = 'https://www.google.com/maps?q=' + lat + ',' + lng;
                updateOrCreateHiddenInput('destination_latitude', lat);
                updateOrCreateHiddenInput('destination_longitude', lng);
            }

            if (statusEl) {
                statusEl.textContent = '✅ Lokasi berhasil: ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
                statusEl.classList.remove('hidden', 'text-blue-600', 'text-red-600', 'text-yellow-600');
                statusEl.classList.add('text-green-600');
            }
        })
        .catch(function() {
            var mapsLink = 'https://www.google.com/maps?q=' + lat + ',' + lng;
            if (type === 'pickup') {
                var addressField = document.getElementById('pickupAddress');
                if (addressField) addressField.value = 'Lat: ' + lat.toFixed(6) + ', Lng: ' + lng.toFixed(6) + ' (isi alamat lengkap)';
                var mapsField = document.getElementById('pickupMapsLink');
                if (mapsField) mapsField.value = mapsLink;
                updateOrCreateHiddenInput('pickup_latitude', lat);
                updateOrCreateHiddenInput('pickup_longitude', lng);
            } else {
                var addressField = document.getElementById('destinationAddress');
                if (addressField) addressField.value = 'Lat: ' + lat.toFixed(6) + ', Lng: ' + lng.toFixed(6) + ' (isi alamat lengkap)';
                var mapsField = document.getElementById('destinationMapsLink');
                if (mapsField) mapsField.value = mapsLink;
                updateOrCreateHiddenInput('destination_latitude', lat);
                updateOrCreateHiddenInput('destination_longitude', lng);
            }

            if (statusEl) {
                statusEl.textContent = '⚠️ Koordinat didapat, gagal mendapatkan alamat. Silakan isi manual.';
                statusEl.classList.remove('hidden', 'text-blue-600', 'text-green-600', 'text-red-600');
                statusEl.classList.add('text-yellow-600');
            }
        });
}

function updateOrCreateHiddenInput(name, value) {
    var input = document.getElementById('f' + name);
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.id = 'f' + name;
        input.name = name;
        document.getElementById('bookingForm').appendChild(input);
    }
    input.value = value;
}
</script>
@endpush
@endsection