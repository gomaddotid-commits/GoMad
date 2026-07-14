@extends('layouts.customer')

@section('title', 'Booking Rental')
@section('content')
@php
    $vehicle = $vehicleSetting->vehicle;
    $agency = $vehicle->agency;
    $canSelfDrive = $vehicleSetting->allow_self_drive && $documentStatus['is_complete_for_self_drive'];
    $canWithDriver = $vehicleSetting->allow_with_driver;
    
    $defaultType = '';
    if (!$canSelfDrive && $canWithDriver) $defaultType = 'with_driver';
    if ($canSelfDrive && !$canWithDriver) $defaultType = 'self_drive';
    
    $minDate = now()->format('Y-m-d\TH:i');
    
    $promoService = app(\App\Services\PromoService::class);
    $availablePromos = $promoService->getAvailablePromosForRental(auth()->user());
    
    // Alamat pengambilan
    $pickupAddr = $vehicleSetting->pickup_address;
    $pickupMaps = $vehicleSetting->pickup_maps_url;
@endphp

<div class="max-w-3xl mx-auto px-4 py-8">
    <a href="{{ route('customer.rental.browse') }}" class="text-[#C1121F] text-sm mb-4 inline-block hover:underline">
        ← Kembali ke Pencarian
    </a>

    {{-- Info Kendaraan --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <div class="flex flex-col md:flex-row gap-6">
            <div class="w-full md:w-64 h-48 bg-[#F5F5F5] rounded-[12px] overflow-hidden flex-shrink-0 border border-[#E5E5E5]">
                @if($vehicle->vehicle_image)
                <img src="{{ $vehicle->vehicle_image }}" class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center text-6xl">🚗</div>
                @endif
            </div>
            
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 bg-[#C1121F]/10 text-[#C1121F] text-[10px] font-mono uppercase tracking-wider rounded-full border border-[#C1121F]">Rental</span>
                    @if($vehicleSetting->allow_self_drive)
                    <span class="px-2 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-mono uppercase tracking-wider rounded-full border border-blue-200">Lepas Kunci</span>
                    @endif
                    @if($vehicleSetting->allow_with_driver)
                    <span class="px-2 py-0.5 bg-green-50 text-green-700 text-[10px] font-mono uppercase tracking-wider rounded-full border border-green-200">+Supir</span>
                    @endif
                </div>
                
                <h1 class="text-2xl font-bold text-[#111111]">{{ $vehicle->brand }} {{ $vehicle->model }} ({{ $vehicle->year }})</h1>
                <p class="text-sm text-gray-500 font-mono">{{ $vehicle->plate_number }}</p>
                
                <div class="flex items-center gap-3 mt-2 text-sm">
                    <div class="flex items-center gap-1">
                        <div class="w-8 h-8 rounded-full bg-[#F5F5F5] flex items-center justify-center overflow-hidden border border-[#E5E5E5]">
                            @if($agency->logo)<img src="{{ $agency->logo }}" class="w-full h-full object-cover">@else<span class="text-sm">🏢</span>@endif
                        </div>
                        <span class="text-gray-600 font-light">{{ $agency->agency_name }}</span>
                    </div>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-500 font-mono">⭐ {{ number_format($agency->rating, 1) }}</span>
                </div>

                @if($vehicleSetting->specifications)
                <div class="flex flex-wrap gap-2 mt-3">
                    @foreach($vehicleSetting->specifications as $key => $value)
                        @if($value && !is_array($value))
                        <span class="px-2 py-1 bg-[#F5F5F5] text-[10px] font-mono uppercase tracking-wider rounded-full text-gray-600 border border-[#E5E5E5]">
                            {{ str_replace('_', ' ', $key) }}: {{ is_bool($value) ? ($value ? 'Ya' : 'Tidak') : $value }}
                        </span>
                        @endif
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Kalender Ketersediaan --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-[#111111]">📅 Ketersediaan Kendaraan</h2>
            <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Klik tanggal untuk memilih</span>
        </div>
        
        <div id="availabilityCalendar" class="mb-4">
            <div class="flex items-center justify-center py-8">
                <span class="text-gray-400 font-light">⏳ Memuat kalender ketersediaan...</span>
            </div>
        </div>
        
        <div class="flex flex-wrap items-center gap-4 text-xs">
            <div class="flex items-center gap-1"><div class="w-4 h-4 bg-white border border-[#E5E5E5] rounded"></div><span class="text-gray-500 font-light">Tersedia</span></div>
            <div class="flex items-center gap-1"><div class="w-4 h-4 bg-red-100 border border-red-300 rounded"></div><span class="text-gray-500 font-light">Sudah Dibooking</span></div>
            <div class="flex items-center gap-1"><div class="w-4 h-4 bg-yellow-100 border border-yellow-300 rounded"></div><span class="text-gray-500 font-light">Hari Ini</span></div>
            <div class="flex items-center gap-1"><div class="w-4 h-4 bg-blue-100 border border-blue-300 rounded"></div><span class="text-gray-500 font-light">Tanggal Dipilih</span></div>
            <div class="flex items-center gap-1"><div class="w-4 h-4 bg-gray-100 text-gray-300 rounded flex items-center justify-center text-[10px]">-</div><span class="text-gray-500 font-light">Tidak Tersedia</span></div>
        </div>
        
        <div id="selectedDatesInfo" class="mt-4 bg-blue-50 border border-blue-200 rounded-[12px] p-3 hidden">
            <div class="flex items-center gap-2 text-sm">
                <span>📅</span><span class="font-medium text-[#111111]">Tanggal Dipilih:</span>
                <span id="selectedDatesText" class="text-blue-700 font-mono"></span>
                <button onclick="clearSelection()" class="ml-auto text-xs text-red-500 hover:underline font-medium">Hapus</button>
            </div>
        </div>
    </div>

    {{-- Form Booking --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm" id="rentalFormSection">
        <h2 class="text-lg font-bold text-[#111111] mb-4">Form Pemesanan</h2>

        @if(!$canSelfDrive && !$canWithDriver)
        <div class="bg-red-50 border border-red-200 rounded-[12px] p-4 mb-6 text-sm text-red-700 font-light">
            Maaf, kendaraan ini sedang tidak tersedia untuk disewa.
        </div>
        @else
        
        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[12px] mb-6 text-sm">
            @foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[12px] mb-6 text-sm whitespace-pre-line">{{ session('error') }}</div>
        @endif

        <form action="{{ route('customer.rental.store') }}" method="POST" id="bookingForm">
            @csrf
            <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">

            {{-- Tipe Rental --}}
            <div class="mb-6">
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-3">Tipe Sewa <span class="text-[#C1121F]">*</span></label>
                
                @if($canSelfDrive && $canWithDriver)
                <div class="grid grid-cols-2 gap-4">
                    <label class="rental-type-card flex flex-col items-center p-4 border-2 border-[#E5E5E5] rounded-[12px] cursor-pointer hover:border-[#C1121F] transition" id="selfDriveCard" onclick="selectType('self_drive')">
                        <input type="radio" name="type" value="self_drive" id="typeSelfDrive" class="hidden" {{ old('type') == 'self_drive' ? 'checked' : '' }}>
                        <span class="text-3xl mb-2">🚗</span>
                        <span class="font-semibold text-[#111111] text-sm">Lepas Kunci</span>
                        <span class="text-[10px] text-gray-500 mt-1 font-light">Anda menyetir sendiri</span>
                        <span class="text-[10px] text-green-600 mt-1 font-mono">✅ Dokumen Lengkap</span>
                    </label>
                    <label class="rental-type-card flex flex-col items-center p-4 border-2 border-[#E5E5E5] rounded-[12px] cursor-pointer hover:border-[#C1121F] transition" id="withDriverCard" onclick="selectType('with_driver')">
                        <input type="radio" name="type" value="with_driver" id="typeWithDriver" class="hidden" {{ old('type') == 'with_driver' ? 'checked' : '' }}>
                        <span class="text-3xl mb-2">👨‍✈️</span>
                        <span class="font-semibold text-[#111111] text-sm">Dengan Supir</span>
                        <span class="text-[10px] text-gray-500 mt-1 font-light">Termasuk supir profesional</span>
                        @if($vehicleSetting->driver_fee_per_day > 0)<span class="text-[10px] text-orange-600 mt-1 font-mono">+Rp {{ number_format($vehicleSetting->driver_fee_per_day, 0, ',', '.') }}/hari</span>@endif
                    </label>
                </div>
                @elseif($canSelfDrive)
                <input type="hidden" name="type" value="self_drive">
                <div class="p-4 border-2 border-[#C1121F] rounded-[12px] bg-[#C1121F]/5">
                    <span class="text-3xl mb-2 block">🚗</span><span class="font-semibold text-[#111111] text-sm">Lepas Kunci (Self Drive)</span>
                    <span class="text-[10px] text-green-600 mt-1 block font-mono">✅ Dokumen Lengkap</span>
                </div>
                @elseif($canWithDriver)
                <input type="hidden" name="type" value="with_driver">
                <div class="p-4 border-2 border-[#C1121F] rounded-[12px] bg-[#C1121F]/5">
                    <span class="text-3xl mb-2 block">👨‍✈️</span><span class="font-semibold text-[#111111] text-sm">Dengan Supir</span>
                    <span class="text-[10px] text-gray-500 mt-1 block font-light">Termasuk supir profesional</span>
                </div>
                @endif
                
                @if($vehicleSetting->allow_self_drive && !$documentStatus['is_complete_for_self_drive'] && $canWithDriver)
                <div class="mt-3 bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-700">
                    <p class="font-medium mb-1">⚠️ Lepas Kunci tidak tersedia</p>
                    <p class="font-light">Dokumen KTP & SIM belum lengkap. <a href="{{ route('customer.documents') }}" class="text-[#C1121F] underline font-medium">Lengkapi sekarang →</a></p>
                </div>
                @endif
                
                <p id="typeError" class="text-[#C1121F] text-xs mt-2 hidden font-medium">Silakan pilih tipe sewa</p>
            </div>

            {{-- Durasi --}}
            <div class="mb-6">
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-3">Durasi Sewa <span class="text-[#C1121F]">*</span></label>
                <div class="flex gap-3 mb-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="duration_unit" value="day" class="text-[#C1121F] focus:ring-[#C1121F]" {{ old('duration_unit', 'day') == 'day' ? 'checked' : '' }} onchange="updateDurationUnit('day')">
                        <span class="text-sm text-[#111111] font-medium">Per Hari</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="duration_unit" value="hour" class="text-[#C1121F] focus:ring-[#C1121F]" {{ old('duration_unit') == 'hour' ? 'checked' : '' }} onchange="updateDurationUnit('hour')">
                        <span class="text-sm text-[#111111] font-medium">Per Jam</span>
                    </label>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1 font-light">Mulai Sewa <span class="text-[#C1121F]">*</span></label>
                        <input type="datetime-local" name="start_datetime" id="startDatetime" min="{{ $minDate }}" value="{{ old('start_datetime') }}"
                               class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] transition" required onchange="calculatePrice()">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1 font-light">Selesai Sewa <span class="text-[#C1121F]">*</span></label>
                        <input type="datetime-local" name="end_datetime" id="endDatetime" min="{{ $minDate }}" value="{{ old('end_datetime') }}"
                               class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] transition" required onchange="calculatePrice()">
                    </div>
                </div>
                <p id="durationError" class="text-[#C1121F] text-xs mt-2 hidden font-medium">Tanggal selesai harus setelah tanggal mulai</p>
            </div>

            {{-- ═══════════════════════════════════ --}}
            {{-- ALAMAT (Tergantung Tipe) --}}
            {{-- ═══════════════════════════════════ --}}
            
            {{-- LEPAS KUNCI --}}
            <div id="selfDriveLocation" style="display:{{ $defaultType == 'self_drive' ? 'block' : 'none' }};">
                <div class="mb-6 bg-blue-50 border border-blue-200 rounded-[12px] p-4">
                    <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">📍 Lokasi Pengambilan Mobil</h3>
                    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4">
                        <p class="font-semibold text-[#111111]">{{ $agency->agency_name }}</p>
                        <p class="text-sm text-gray-500 mt-1 font-light">{{ $pickupAddr }}</p>
                        <a href="{{ $pickupMaps }}" target="_blank" class="mt-3 inline-flex items-center gap-2 text-sm text-[#C1121F] hover:text-[#8A0F18] font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            🗺️ Buka Google Maps
                        </a>
                    </div>
                    <div class="mt-3 bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-700">
                        <p class="font-medium">⚠️ Informasi Penting</p>
                        <p class="font-light mt-1">Anda wajib datang ke lokasi di atas untuk mengambil mobil. Silakan isi catatan untuk perkiraan jam kedatangan.</p>
                    </div>
                </div>
            </div>

            {{-- DENGAN SUPIR --}}
            <div id="withDriverLocation" style="display:{{ $defaultType == 'with_driver' ? 'block' : 'none' }};">
                <div class="mb-6">
                    <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">📍 Alamat Penjemputan</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1 font-light">Alamat Lengkap <span class="text-[#C1121F]">*</span></label>
                            <textarea name="pickup_address" id="pickupAddress" rows="2" 
                                      class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] transition"
                                      placeholder="Jl. Sudirman No. 10, Kelurahan, Kecamatan...">{{ old('pickup_address') }}</textarea>
                            <button type="button" onclick="getCurrentLocation('pickup')"
                                    class="mt-2 text-sm text-[#C1121F] hover:text-[#111111] font-medium flex items-center gap-1 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Gunakan Lokasi Saat Ini
                            </button>
                            <p id="pickupLocationStatus" class="text-xs mt-1 hidden font-light"></p>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1 font-light">Link Google Maps</label>
                            <input type="url" name="pickup_maps_link" id="pickupMapsLink" 
                                   class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] transition"
                                   placeholder="https://maps.google.com/?q=..." readonly>
                            <p class="text-[10px] text-gray-400 mt-1 font-light">Terisi otomatis saat menggunakan lokasi saat ini</p>
                        </div>
                        <div class="border-t border-[#E5E5E5] pt-4">
                            <label class="block text-xs text-gray-500 mb-1 font-light">Alamat Tujuan <span class="text-xs text-gray-400">(Opsional)</span></label>
                            <textarea name="destination_address" rows="2" 
                                      class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] transition"
                                      placeholder="Alamat tujuan Anda...">{{ old('destination_address') }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mb-1">Lokasi Agency</p>
                        <p class="text-xs text-gray-500 font-light">{{ $agency->agency_name }} - {{ $agency->address }}</p>
                    </div>
                </div>
            </div>

            {{-- Promo --}}
            @if($availablePromos->isNotEmpty())
            <div class="mb-6">
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-3">🎫 Promo (Opsional)</label>
                <select name="promo_id" id="promoSelect" class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] text-sm" onchange="updatePromoDisplay()">
                    <option value="">Tanpa Promo</option>
                    @foreach($availablePromos as $promo)
                    @php
                        $discountType = $promo->rental_discount_type ?? 'percent';
                        if ($discountType === 'fixed') { $discountText = 'Potongan Rp ' . number_format($promo->rental_discount_amount, 0, ',', '.'); }
                        else { $maxDiscount = $promo->rental_max_discount ?? $promo->max_discount; $discountText = 'Diskon ' . $promo->discount_percent . '% (Maks Rp ' . number_format($maxDiscount, 0, ',', '.') . ')'; }
                    @endphp
                    <option value="{{ $promo->id }}" data-type="{{ $discountType }}" data-percent="{{ $promo->discount_percent }}" data-amount="{{ $promo->rental_discount_amount ?? 0 }}" data-max="{{ $promo->rental_max_discount ?? $promo->max_discount }}" data-name="{{ $promo->name }}">
                        {{ $promo->name }} - {{ $discountText }}
                    </option>
                    @endforeach
                </select>
                <p id="promoInfo" class="text-xs text-green-600 mt-2 hidden font-medium"></p>
            </div>
            @endif

            {{-- Catatan --}}
            <div class="mb-6">
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Catatan (Opsional)</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] transition"
                    placeholder="{{ $defaultType == 'self_drive' ? 'Contoh: Saya akan datang sekitar jam 09:00...' : 'Contoh: Butuh car seat untuk anak...' }}">{{ old('notes') }}</textarea>
            </div>

            {{-- Ringkasan Harga --}}
            <div id="priceSummary" class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4 mb-6 hidden">
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">Ringkasan Harga</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500 font-light">Harga sewa</span><span class="font-medium text-[#111111]" id="summaryPricePerUnit">-</span></div>
                    <div class="flex justify-between"><span class="text-gray-500 font-light">Durasi</span><span class="font-medium text-[#111111]" id="summaryDuration">-</span></div>
                    <div class="flex justify-between" id="driverFeeRow" style="display:none;"><span class="text-gray-500 font-light">Biaya Supir</span><span class="font-medium text-[#111111]" id="summaryDriverFee">-</span></div>
                    <div class="flex justify-between"><span class="text-gray-500 font-light">Subtotal</span><span class="font-bold text-[#111111] font-mono" id="summarySubtotal">-</span></div>
                    <div class="flex justify-between"><span class="text-gray-500 font-light">Biaya Platform (3%)</span><span class="font-medium text-[#111111]" id="summaryPlatformFee">-</span></div>
                    <div class="flex justify-between text-[#C1121F] hidden" id="discountRow"><span class="font-medium">Diskon Promo</span><span class="font-bold" id="summaryDiscount">-Rp 0</span></div>
                    <hr class="border-[#E5E5E5]">
                    <div class="flex justify-between text-base font-bold"><span>Total</span><span class="text-[#C1121F] font-mono" id="summaryTotal">-</span></div>
                </div>
            </div>

            {{-- Persetujuan --}}
            <x-rental-agreement :vehicleSetting="$vehicleSetting" mode="create" :showCheckboxes="true" />

            {{-- Tombol Submit --}}
            <button type="submit" id="btnSubmit" disabled
                    class="w-full bg-[#E5E5E5] text-gray-500 py-4 rounded-[12px] font-bold text-lg cursor-not-allowed transition">
                🚗 BOOKING SEKARANG
            </button>
        </form>
        @endif
    </div>
</div>

@push('scripts')
<script>
var prices = {
    price_per_hour: {{ $vehicleSetting->price_per_hour ?? 0 }},
    price_per_day: {{ $vehicleSetting->price_per_day ?? 0 }},
    driver_fee_per_hour: {{ $vehicleSetting->driver_fee_per_hour ?? 0 }},
    driver_fee_per_day: {{ $vehicleSetting->driver_fee_per_day ?? 0 }},
};
var selectedType = '{{ $defaultType }}';
var durationUnit = 'day';
var currentSubtotal = 0;
var currentPlatformFee = 0;

// Kalender
var bookedDates = {};
var currentMonth = new Date().getMonth();
var currentYear = new Date().getFullYear();
var selectedStartDate = null;
var selectedEndDate = null;
var vehicleId = {{ $vehicle->id }};

function loadAvailability() {
    fetch('/api/v1/rental/vehicle/' + vehicleId + '/availability')
        .then(res => res.json())
        .then(data => { if (data.success) { bookedDates = data.data.booked_dates || {}; } renderCalendar(currentMonth, currentYear); })
        .catch(() => { document.getElementById('availabilityCalendar').innerHTML = '<p class="text-center text-gray-500 py-4 font-light">Gagal memuat data.</p>'; });
}

function renderCalendar(month, year) {
    var container = document.getElementById('availabilityCalendar');
    var months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var daysInMonth = new Date(year, month + 1, 0).getDate();
    var today = new Date(); today.setHours(0,0,0,0);
    var html = '<div class="text-center mb-4 flex items-center justify-center gap-4">';
    html += '<button onclick="prevMonth()" class="w-8 h-8 flex items-center justify-center border border-[#E5E5E5] rounded-lg hover:bg-[#F5F5F5] text-sm">&larr;</button>';
    html += '<span class="font-bold text-[#111111] text-lg min-w-[150px]">' + months[month] + ' ' + year + '</span>';
    html += '<button onclick="nextMonth()" class="w-8 h-8 flex items-center justify-center border border-[#E5E5E5] rounded-lg hover:bg-[#F5F5F5] text-sm">&rarr;</button></div>';
    html += '<div class="grid grid-cols-7 gap-1 text-center">';
    ['Min','Sen','Sel','Rab','Kam','Jum','Sab'].forEach(d => html += '<div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 py-1">'+d+'</div>');
    var startDay = new Date(year, month, 1).getDay();
    for (var i = 0; i < startDay; i++) html += '<div class="py-2"></div>';
    for (var day = 1; day <= daysInMonth; day++) {
        var dateStr = year + '-' + String(month+1).padStart(2,'0') + '-' + String(day).padStart(2,'0');
        var dateObj = new Date(year, month, day);
        var isBooked = bookedDates[dateStr] !== undefined;
        var isToday = dateObj.getTime() === today.getTime();
        var isPast = dateObj < today;
        var isSelected = false;
        if (selectedStartDate && selectedEndDate) { var s = new Date(selectedStartDate+'T00:00:00'), e = new Date(selectedEndDate+'T00:00:00'); if (dateObj >= s && dateObj <= e) isSelected = true; }
        else if (selectedStartDate && !selectedEndDate && dateStr === selectedStartDate) isSelected = true;
        var bgClass = 'bg-white hover:bg-blue-50 cursor-pointer border border-transparent';
        var bookedInfo = '';
        if (isBooked) { bgClass = 'bg-red-50 cursor-not-allowed border border-red-200'; var rentals = bookedDates[dateStr]; var tooltip = rentals.map(r => r.rental_code+' ('+r.type+')').join('\n'); bookedInfo = 'title="'+tooltip+'"'; }
        if (isToday) bgClass = 'bg-yellow-50 border border-yellow-300';
        if (isSelected) bgClass = 'bg-blue-100 border border-blue-400 font-bold';
        if (isPast && !isToday) bgClass = 'bg-gray-100 text-gray-300 cursor-not-allowed border border-gray-200';
        var clickHandler = (!isBooked && !isPast) ? 'onclick="selectCalendarDate(\''+dateStr+'\')"' : '';
        html += '<div class="py-2 rounded-lg text-sm '+bgClass+'" '+clickHandler+' '+bookedInfo+'>'+day+(isBooked?'<div class="text-[8px] text-red-500 leading-none">📌</div>':'')+'</div>';
    }
    html += '</div>';
    container.innerHTML = html;
}

function prevMonth() { if (currentMonth === 0) { currentMonth = 11; currentYear--; } else { currentMonth--; } renderCalendar(currentMonth, currentYear); }
function nextMonth() { var today = new Date(); var maxMonth = today.getMonth()+6, maxYear = today.getFullYear(); if (maxMonth > 11) { maxMonth -= 12; maxYear++; } if (currentYear < maxYear || (currentYear === maxYear && currentMonth < maxMonth)) { if (currentMonth === 11) { currentMonth = 0; currentYear++; } else { currentMonth++; } } renderCalendar(currentMonth, currentYear); }

function selectCalendarDate(dateStr) {
    if (!selectedStartDate || (selectedStartDate && selectedEndDate)) { selectedStartDate = dateStr; selectedEndDate = null; document.getElementById('startDatetime').value = dateStr+'T08:00'; document.getElementById('endDatetime').value = ''; document.getElementById('durationError').classList.add('hidden'); document.getElementById('priceSummary').classList.add('hidden'); }
    else { if (dateStr < selectedStartDate) { selectedEndDate = selectedStartDate; selectedStartDate = dateStr; } else { selectedEndDate = dateStr; } document.getElementById('endDatetime').value = selectedEndDate+'T17:00'; calculatePrice(); }
    updateSelectedDatesInfo(); renderCalendar(currentMonth, currentYear);
    document.getElementById('rentalFormSection').scrollIntoView({ behavior:'smooth', block:'start' });
}

function clearSelection() { selectedStartDate = null; selectedEndDate = null; document.getElementById('startDatetime').value = ''; document.getElementById('endDatetime').value = ''; document.getElementById('selectedDatesInfo').classList.add('hidden'); document.getElementById('priceSummary').classList.add('hidden'); renderCalendar(currentMonth, currentYear); }

function updateSelectedDatesInfo() {
    var info = document.getElementById('selectedDatesInfo'), text = document.getElementById('selectedDatesText');
    if (selectedStartDate && selectedEndDate) { info.classList.remove('hidden'); text.textContent = selectedStartDate + ' → ' + selectedEndDate; }
    else if (selectedStartDate) { info.classList.remove('hidden'); text.textContent = selectedStartDate + ' (pilih tanggal selesai)'; }
    else { info.classList.add('hidden'); }
}

function selectType(type) {
    selectedType = type;
    document.getElementsByName('type').forEach(r => { if (r.value === type) r.checked = true; });
    document.getElementById('typeError').classList.add('hidden');
    document.querySelectorAll('.rental-type-card').forEach(c => { c.classList.remove('border-[#C1121F]','bg-[#C1121F]/5'); c.classList.add('border-[#E5E5E5]'); });
    var card = document.getElementById(type === 'self_drive' ? 'selfDriveCard' : 'withDriverCard');
    if (card) { card.classList.add('border-[#C1121F]','bg-[#C1121F]/5'); card.classList.remove('border-[#E5E5E5]'); }
    document.getElementById('selfDriveLocation').style.display = type === 'self_drive' ? 'block' : 'none';
    document.getElementById('withDriverLocation').style.display = type === 'with_driver' ? 'block' : 'none';
    calculatePrice();
}

function updateDurationUnit(unit) { durationUnit = unit; calculatePrice(); }

function calculatePrice() {
    var startVal = document.getElementById('startDatetime').value, endVal = document.getElementById('endDatetime').value;
    var summary = document.getElementById('priceSummary'), durationError = document.getElementById('durationError');
    if (!startVal || !endVal || !selectedType) { summary.classList.add('hidden'); return; }
    var start = new Date(startVal), end = new Date(endVal);
    if (end <= start) { durationError.classList.remove('hidden'); summary.classList.add('hidden'); return; }
    durationError.classList.add('hidden');
    var durationHours = Math.ceil((end - start) / (1000*60*60)), durationDays = Math.ceil(durationHours/24);
    var duration = durationUnit === 'hour' ? durationHours : durationDays;
    var durationLabel = durationUnit === 'hour' ? duration+' jam' : duration+' hari';
    var pricePerUnit = durationUnit === 'hour' ? prices.price_per_hour : prices.price_per_day;
    var driverFeePerUnit = selectedType === 'with_driver' ? (durationUnit === 'hour' ? prices.driver_fee_per_hour : prices.driver_fee_per_day) : 0;
    currentSubtotal = (pricePerUnit + driverFeePerUnit) * duration;
    currentPlatformFee = Math.round(currentSubtotal * 0.03);
    document.getElementById('summaryPricePerUnit').textContent = 'Rp '+formatRupiah(pricePerUnit)+'/'+(durationUnit==='hour'?'jam':'hari');
    document.getElementById('summaryDuration').textContent = durationLabel;
    if (selectedType === 'with_driver' && driverFeePerUnit > 0) { document.getElementById('summaryDriverFee').textContent = 'Rp '+formatRupiah(driverFeePerUnit)+'/'+(durationUnit==='hour'?'jam':'hari'); document.getElementById('driverFeeRow').style.display = 'flex'; }
    else { document.getElementById('driverFeeRow').style.display = 'none'; }
    document.getElementById('summarySubtotal').textContent = 'Rp '+formatRupiah(currentSubtotal);
    document.getElementById('summaryPlatformFee').textContent = 'Rp '+formatRupiah(currentPlatformFee);
    summary.classList.remove('hidden');
    updatePromoDisplay();
}

function updatePromoDisplay() {
    var promoSelect = document.getElementById('promoSelect'), promoInfo = document.getElementById('promoInfo'), discountRow = document.getElementById('discountRow'), summaryDiscount = document.getElementById('summaryDiscount'), summaryTotal = document.getElementById('summaryTotal');
    var discountAmount = 0;
    if (promoSelect && promoSelect.value && currentSubtotal > 0) {
        var option = promoSelect.options[promoSelect.selectedIndex], discountType = option.getAttribute('data-type') || 'percent';
        if (discountType === 'fixed') { discountAmount = parseFloat(option.getAttribute('data-amount')) || 0; }
        else { var percent = parseFloat(option.getAttribute('data-percent')) || 0, maxDiscount = parseFloat(option.getAttribute('data-max')) || 0; discountAmount = Math.min(currentSubtotal*(percent/100), maxDiscount); }
        if (discountAmount > 0 && promoInfo) { promoInfo.textContent = '✅ Promo "'+option.getAttribute('data-name')+'" aktif! Hemat Rp '+formatRupiah(discountAmount); promoInfo.classList.remove('hidden'); discountRow.classList.remove('hidden'); summaryDiscount.textContent = '-Rp '+formatRupiah(discountAmount); }
    }
    if (discountAmount <= 0) { if (promoInfo) promoInfo.classList.add('hidden'); discountRow.classList.add('hidden'); }
    var total = Math.max(0, currentSubtotal + currentPlatformFee - discountAmount);
    if (summaryTotal) summaryTotal.textContent = 'Rp '+formatRupiah(total);
}

function formatRupiah(num) { return new Intl.NumberFormat('id-ID').format(num || 0); }

function getCurrentLocation(type) {
    if (!navigator.geolocation) { alert('Browser tidak mendukung geolocation.'); return; }
    var statusEl = document.getElementById(type+'LocationStatus');
    if (statusEl) { statusEl.textContent = '⏳ Mengambil lokasi...'; statusEl.classList.remove('hidden','text-green-600','text-red-600'); statusEl.classList.add('text-blue-600'); }
    navigator.geolocation.getCurrentPosition(function(pos) { reverseGeocode(pos.coords.latitude, pos.coords.longitude, type); }, function(err) {
        var msg = err.code === err.PERMISSION_DENIED ? 'Akses lokasi ditolak.' : (err.code === err.POSITION_UNAVAILABLE ? 'Lokasi tidak tersedia.' : 'Gagal mengambil lokasi.');
        if (statusEl) { statusEl.textContent = '❌ '+msg; statusEl.classList.remove('hidden','text-blue-600'); statusEl.classList.add('text-red-600'); }
    }, { enableHighAccuracy:true, timeout:10000, maximumAge:60000 });
}

function reverseGeocode(lat, lng, type) {
    var statusEl = document.getElementById(type+'LocationStatus');
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat='+lat+'&lon='+lng+'&accept-language=id')
        .then(res => res.json()).then(data => {
            var address = data.display_name || '';
            if (type === 'pickup') { document.getElementById('pickupAddress').value = address; document.getElementById('pickupMapsLink').value = 'https://www.google.com/maps?q='+lat+','+lng; }
            if (statusEl) { statusEl.textContent = '✅ Lokasi berhasil'; statusEl.classList.remove('hidden','text-blue-600','text-red-600'); statusEl.classList.add('text-green-600'); }
        }).catch(() => {
            var mapsLink = 'https://www.google.com/maps?q='+lat+','+lng;
            if (type === 'pickup') { document.getElementById('pickupAddress').value = 'Lat: '+lat.toFixed(6)+', Lng: '+lng.toFixed(6)+' (isi alamat lengkap)'; document.getElementById('pickupMapsLink').value = mapsLink; }
            if (statusEl) { statusEl.textContent = '⚠️ Koordinat didapat, isi alamat manual.'; statusEl.classList.remove('hidden','text-blue-600','text-red-600'); statusEl.classList.add('text-yellow-600'); }
        });
}

document.getElementById('bookingForm').addEventListener('submit', function(e) {
    var errors = [];
    var typeSelected = document.querySelector('input[name="type"]:checked');
    if (!typeSelected && !document.querySelector('input[name="type"][type="hidden"]')) errors.push('Silakan pilih tipe sewa.');
    if (!document.getElementById('startDatetime').value || !document.getElementById('endDatetime').value) errors.push('Isi tanggal mulai dan selesai.');
    if (new Date(document.getElementById('endDatetime').value) <= new Date(document.getElementById('startDatetime').value)) errors.push('Tanggal selesai harus setelah mulai.');
    if (selectedType === 'with_driver' && !document.getElementById('pickupAddress').value.trim()) errors.push('Alamat penjemputan wajib diisi.');
    if (!document.getElementById('agreeTerms').checked) errors.push('Setujui Syarat & Ketentuan.');
    if (!document.getElementById('agreeRefund').checked) errors.push('Setujui Kebijakan Pembatalan.');
    if (errors.length > 0) { e.preventDefault(); alert(errors.join('\n')); return false; }
    var btn = document.getElementById('btnSubmit'); btn.disabled = true; btn.textContent = '⏳ Memproses...'; btn.className = 'w-full bg-gray-400 text-white py-4 rounded-[12px] font-bold text-lg cursor-not-allowed transition';
    return true;
});

document.getElementById('startDatetime').addEventListener('change', function() { if (this.value) { selectedStartDate = this.value.split('T')[0]; updateSelectedDatesInfo(); renderCalendar(currentMonth, currentYear); } });
document.getElementById('endDatetime').addEventListener('change', function() { if (this.value) { selectedEndDate = this.value.split('T')[0]; updateSelectedDatesInfo(); renderCalendar(currentMonth, currentYear); calculatePrice(); } });

document.addEventListener('DOMContentLoaded', function() {
    loadAvailability();
    if (selectedType) selectType(selectedType);
    var oldType = '{{ old('type') }}'; if (oldType) selectType(oldType);
    var oldStart = '{{ old('start_datetime') }}', oldEnd = '{{ old('end_datetime') }}';
    if (oldStart) { selectedStartDate = oldStart.split('T')[0]; document.getElementById('startDatetime').value = oldStart; }
    if (oldEnd) { selectedEndDate = oldEnd.split('T')[0]; document.getElementById('endDatetime').value = oldEnd; }
    if (oldStart || oldEnd) { updateSelectedDatesInfo(); setTimeout(() => renderCalendar(currentMonth, currentYear), 500); }
    if (document.getElementById('promoSelect').value) updatePromoDisplay();
    toggleSubmitButton();
});
</script>
@endpush
@endsection