@extends('layouts.agency')

@section('title', 'Buat Jadwal')
@section('content')
@php
    $routes = \App\Models\Route::where('is_active', true)
        ->where('is_system_generated', false)
        ->with(['stops.city', 'originCity', 'destinationCity'])
        ->get();
    $vehicles = auth()->user()->agency->vehicles()->where('is_active', true)->get();
    $drivers = auth()->user()->agency->drivers()->where('is_active', true)->get();
    $minDays = app()->environment('local') ? 1 : 30;
    $minDate = now()->addDays($minDays)->toDateString();
    
    $walletService = app(\App\Services\WalletService::class);
    $agency = auth()->user()->agency;
    $depositBalance = (float) ($agency->wallet->deposit_balance ?? 0);
    $codHold = (float) ($agency->wallet->cod_hold_balance ?? 0);
    $availableDeposit = $depositBalance - $codHold;

    $routesData = $routes->map(function($route) {
        $stops = $route->stops->map(function($stop) {
            return [
                'id' => $stop->id,
                'city_code' => $stop->city_code,
                'city_name' => $stop->city_name,
                'stop_order' => $stop->stop_order,
                'is_first' => $stop->isFirst(),
                'is_last' => $stop->isLast(),
                'latitude' => (float) $stop->latitude,
                'longitude' => (float) $stop->longitude,
            ];
        })->values()->toArray();
        
        return [
            'id' => $route->id,
            'route_name' => $route->route_name,
            'origin_city' => $route->origin_city_name,
            'destination_city' => $route->destination_city_name,
            'origin_city_code' => $route->origin_city_code,
            'destination_city_code' => $route->destination_city_code,
            'distance_km' => (float) ($route->distance_km ?? 0),
            'estimated_duration' => $route->estimated_duration,
            'max_price' => (float) ($route->max_price ?? 0),
            'cod_available' => (bool) $route->cod_available,
            'cod_min_deposit' => (float) ($route->cod_min_deposit ?? 500000),
            'payment_methods' => $route->payment_methods_array,
            'stops' => $stops,
        ];
    })->values()->toArray();

    $vehiclesData = $vehicles->map(function($v) {
        $isRental = $v->rentalSetting && $v->rentalSetting->is_available_for_rental;
        return [
            'id' => $v->id,
            'plate_number' => $v->plate_number,
            'brand' => $v->brand,
            'model' => $v->model,
            'capacity' => $v->capacity,
            'is_rental' => $isRental,
        ];
    })->values()->toArray();
@endphp

<div class="max-w-5xl mx-auto" id="scheduleFormApp">
    <h1 class="text-2xl font-bold text-[#111111] mb-2">Buat Jadwal Baru</h1>

    {{-- PROGRESS BAR --}}
    <div class="mb-6">
        <div class="flex justify-between text-xs md:text-sm mb-2" id="progressLabels">
            <span class="step-label font-bold text-[#C1121F]" data-step="1">1. Mode & Info</span>
            <span class="step-label text-gray-400" data-step="2">2. Jadwal Pergi</span>
            <span class="step-label text-gray-400 hidden" data-step="2b" id="labelStep2b">2b. Jadwal Pulang</span>
            <span class="step-label text-gray-400" data-step="3">3. Ringkasan</span>
        </div>
        <div class="bg-[#E5E5E5] rounded-full h-2">
            <div id="progressBar" class="bg-[#C1121F] rounded-full h-2 transition-all duration-300" style="width: 25%"></div>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[12px] mb-6 text-sm" id="errorBox">
        @foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
    </div>
    @endif

    <form action="{{ route('agency.schedules.store') }}" method="POST" id="scheduleForm">
        @csrf

        {{-- ═══════════════════════════════════════ --}}
        {{-- STEP 1: MODE & INFO UTAMA --}}
        {{-- ═══════════════════════════════════════ --}}
        <div class="step-content" id="step-1">
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
                <h2 class="font-bold text-lg text-[#111111] mb-4">Mode & Informasi Utama</h2>
                <p class="text-sm text-gray-500 font-light mb-6">Info ini berlaku untuk jadwal pergi (dan pulang jika PP diaktifkan).</p>

                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4 mb-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_pp" value="1" id="isPP" 
                               class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]"
                               onchange="updatePPUI()" {{ old('is_pp') ? 'checked' : '' }}>
                        <div>
                            <span class="font-semibold text-[#111111]">🔄 Aktifkan Jadwal Pulang-Pergi (PP)</span>
                            <p class="text-xs text-gray-500 font-light mt-0.5">Sistem akan otomatis membuat jadwal pulang dengan rute kebalikan</p>
                        </div>
                    </label>
                    <p class="text-xs text-purple-600 mt-2 font-light hidden" id="ppInfoStep1">
                        Rute PP: <strong id="ppRoutePreview">-</strong>
                    </p>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Rute <span class="text-[#C1121F]">*</span></label>
                        <select name="route_id" id="routeSelect" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required onchange="onRouteChange()">
                            <option value="">Pilih Rute</option>
                            @foreach($routes as $route)
                            <option value="{{ $route->id }}" {{ old('route_id') == $route->id ? 'selected' : '' }}>{{ $route->route_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kendaraan <span class="text-[#C1121F]">*</span></label>
                        <select name="vehicle_id" id="vehicleSelect" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required onchange="onVehicleChange()">
                            <option value="">Pilih Kendaraan</option>
                            @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" {{ old('vehicle_id') == $v->id ? 'selected' : '' }} data-rental="{{ $v->rentalSetting && $v->rentalSetting->is_available_for_rental ? '1' : '0' }}">
                                {{ $v->plate_number }} ({{ $v->capacity }} seat) {{ $v->rentalSetting && $v->rentalSetting->is_available_for_rental ? '🚗' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Driver (Opsional)</label>
                        <select name="driver_id" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                            <option value="">Pilih Driver</option>
                            @foreach($drivers as $d)
                            <option value="{{ $d->id }}" {{ old('driver_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kelas <span class="text-[#C1121F]">*</span></label>
                        <select name="travel_class" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                            <option value="economy" {{ old('travel_class') == 'economy' ? 'selected' : '' }}>Ekonomi</option>
                            <option value="premium" {{ old('travel_class') == 'premium' ? 'selected' : '' }}>Premium</option>
                            <option value="charter" {{ old('travel_class') == 'charter' ? 'selected' : '' }}>Charter</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Harga Dasar per Seat (Rp) <span class="text-[#C1121F]">*</span></label>
                        <input type="number" name="price_per_seat" id="basePrice" value="{{ old('price_per_seat', 150000) }}" 
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" min="1000" required>
                    </div>
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Max Overload</label>
                            <input type="number" name="max_overload" value="{{ old('max_overload', 2) }}" min="0" max="2" 
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                        </div>
                        <div class="flex-1">
                            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Bagasi (kg)</label>
                            <input type="number" name="baggage_limit_kg" value="{{ old('baggage_limit_kg', 15) }}" min="0" max="50" 
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end mt-4">
                <button type="button" onclick="goToStep2()" class="btn-gomad-primary px-8 py-3 rounded-[12px] font-semibold">Lanjutkan →</button>
            </div>
        </div>

        {{-- ═══════════════════════════════════════ --}}
        {{-- STEP 2: KONFIGURASI JADWAL PERGI --}}
        {{-- ═══════════════════════════════════════ --}}
        <div class="step-content" id="step-2" style="display:none;">
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
                <h2 class="font-bold text-lg text-[#111111] mb-4">🚐 Konfigurasi Jadwal Pergi</h2>

                <div class="grid md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                            Tanggal (min {{ \Carbon\Carbon::parse($minDate)->format('d M Y') }}) <span class="text-[#C1121F]">*</span>
                        </label>
                        <input type="date" name="departure_date" id="departureDate" min="{{ $minDate }}" value="{{ old('departure_date') }}" 
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required onchange="updateEstimatedArrival()">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Jam <span class="text-[#C1121F]">*</span></label>
                        <input type="time" name="departure_time" id="departureTime" value="{{ old('departure_time', '08:00') }}" 
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required onchange="updateEstimatedArrival()">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Estimasi Tiba</label>
                        <input type="datetime-local" name="estimated_arrival" id="estimatedArrival"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                               value="{{ old('estimated_arrival') }}" onchange="updateMinPPInfo()">
                        <p class="text-[10px] text-gray-400 mt-1 font-light" id="estimatedArrivalInfo">Auto-calculate</p>
                    </div>
                </div>

                {{-- Konfigurasi Stop & Harga PERGI --}}
                <div id="stopConfigSectionGo" style="display:none;">
                    <div class="border-t border-[#E5E5E5] pt-4 mb-4">
                        <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-2">🛑 Stop & Harga Pergi</h3>
                    </div>
                    <div id="coverageCheckGo" class="mb-4" style="display:none;"></div>
                    <div class="overflow-x-auto mb-4">
                        <table class="w-full text-sm">
                            <thead class="bg-[#F5F5F5] border-b border-[#E5E5E5]">
                                <tr>
                                    <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Stop</th>
                                    <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Kota</th>
                                    <th class="px-4 py-3 text-center w-24 font-mono uppercase tracking-wider text-xs text-gray-500">Pickup</th>
                                    <th class="px-4 py-3 text-center w-24 font-mono uppercase tracking-wider text-xs text-gray-500">Dropoff</th>
                                </tr>
                            </thead>
                            <tbody id="stopsTableBodyGo">
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 font-light">Pilih rute terlebih dahulu</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="pricingSummaryGo" style="display:none;">
                        <h4 class="font-mono uppercase tracking-wider text-xs font-bold mb-2">Harga Pergi:</h4>
                        <div id="pricingListGo" class="grid grid-cols-1 md:grid-cols-2 gap-2"></div>
                        <p id="pricingWarningGo" class="text-[#C1121F] text-sm mt-2 hidden font-medium">Masih ada kombinasi yang belum diisi!</p>
                    </div>
                </div>

                {{-- Pembayaran --}}
                <div id="paymentSectionGo" class="border-t border-[#E5E5E5] pt-4 mt-4" style="display:none;">
                    <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">💳 Pembayaran</h3>
                    <div class="grid grid-cols-3 gap-3" id="paymentMethodsContainerGo"></div>
                </div>
            </div>

            <div class="flex justify-between mt-4">
                <button type="button" onclick="goToStep1()" class="border border-[#E5E5E5] text-gray-700 px-6 py-3 rounded-[12px] font-semibold hover:bg-[#F5F5F5] transition">← Kembali</button>
                <button type="button" onclick="goToNextFromStep2()" id="btnNextStep2" class="btn-gomad-primary px-8 py-3 rounded-[12px] font-semibold">Lanjutkan →</button>
            </div>
        </div>

        {{-- ═══════════════════════════════════════ --}}
        {{-- STEP 2B: KONFIGURASI JADWAL PULANG (PP) --}}
        {{-- ═══════════════════════════════════════ --}}
        <div class="step-content" id="step-2b" style="display:none;">
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
                <h2 class="font-bold text-lg text-[#111111] mb-4">🔄 Konfigurasi Jadwal Pulang (PP)</h2>

                <div class="bg-blue-50 border border-blue-200 rounded-[12px] p-4 mb-4 text-sm text-blue-800">
                    <p class="font-mono uppercase tracking-wider text-xs font-medium mb-1">ℹ️ Info PP</p>
                    <p class="font-light">Rute PP: <strong id="ppRouteName">-</strong></p>
                    <p class="font-light mt-1">Estimasi Tiba Pergi: <strong id="ppEstArrival">-</strong></p>
                    <p class="font-light">Tanggal PP minimal: <strong id="ppMinDateTime">-</strong></p>
                </div>

                <div class="grid md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Istirahat di Tujuan (jam)</label>
                        <input type="number" name="pp_rest_hours" id="ppRestHours" value="{{ old('pp_rest_hours', 2) }}" min="0" max="48"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" onchange="updateMinPPInfo()">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Tanggal PP <span class="text-[#C1121F]">*</span></label>
                        <input type="date" name="pp_date" id="ppDate" value="{{ old('pp_date') }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required onchange="updateRentalInfo()">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Jam PP <span class="text-[#C1121F]">*</span></label>
                        <input type="time" name="pp_time" id="ppTime" value="{{ old('pp_time', old('departure_time', '08:00')) }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required onchange="updateRentalInfo()">
                    </div>
                </div>

                {{-- Harga PP --}}
                <div class="mb-4">
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Harga PP per Seat (Rp) <span class="text-[#C1121F]">*</span></label>
                    <input type="number" name="pp_price" id="ppPrice" value="{{ old('pp_price', old('price_per_seat', 150000)) }}"
                           class="w-48 px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" min="1000" onchange="updatePPSummaryPrice()">
                </div>

                {{-- Konfigurasi Stop & Harga PULANG --}}
                <div id="stopConfigSectionPP" class="border-t border-[#E5E5E5] pt-4 mt-4">
                    <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-2">🛑 Stop & Harga Pulang</h3>
                    <p class="text-sm text-gray-500 font-light mb-3">Stop otomatis dari rute kebalikan. Atur pickup/dropoff dan harga.</p>
                    <div class="overflow-x-auto mb-4">
                        <table class="w-full text-sm">
                            <thead class="bg-[#F5F5F5] border-b border-[#E5E5E5]">
                                <tr>
                                    <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Stop</th>
                                    <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Kota</th>
                                    <th class="px-4 py-3 text-center w-24 font-mono uppercase tracking-wider text-xs text-gray-500">Pickup</th>
                                    <th class="px-4 py-3 text-center w-24 font-mono uppercase tracking-wider text-xs text-gray-500">Dropoff</th>
                                </tr>
                            </thead>
                            <tbody id="stopsTableBodyPP">
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 font-light">Pilih rute di Step 1 terlebih dahulu</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="pricingSummaryPP" style="display:none;">
                        <h4 class="font-mono uppercase tracking-wider text-xs font-bold mb-2">Harga Pulang:</h4>
                        <div id="pricingListPP" class="grid grid-cols-1 md:grid-cols-2 gap-2"></div>
                        <p id="pricingWarningPP" class="text-[#C1121F] text-sm mt-2 hidden font-medium">Masih ada kombinasi yang belum diisi!</p>
                    </div>
                </div>

                {{-- Ketersediaan Rental --}}
                <div id="rentalSectionPP" class="border-t border-[#E5E5E5] pt-4 mt-4" style="display:none;">
                    <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">🚗 Ketersediaan Rental</h3>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-3 mb-3 text-sm text-yellow-800">
                        <p class="font-light">Atur kapan kendaraan bisa dipakai rental lagi.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="text-sm text-[#111111]">Siap Rental H+</label>
                        <input type="number" name="rest_days_before_rental" id="restDaysBeforeRental" value="{{ old('rest_days_before_rental', 1) }}" min="1" max="30"
                               class="w-20 px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" onchange="updateRentalInfo()">
                        <span class="text-sm text-gray-500">hari setelah tiba</span>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-2 font-light" id="rentalInfo">Kendaraan siap rental mulai: -</p>
                </div>
            </div>

            <div class="flex justify-between mt-4">
                <button type="button" onclick="goToStep2()" class="border border-[#E5E5E5] text-gray-700 px-6 py-3 rounded-[12px] font-semibold hover:bg-[#F5F5F5] transition">← Kembali</button>
                <button type="button" onclick="goToStep3()" class="btn-gomad-primary px-8 py-3 rounded-[12px] font-semibold">Lanjutkan ke Ringkasan →</button>
            </div>
        </div>

        {{-- ═══════════════════════════════════════ --}}
        {{-- STEP 3: RINGKASAN --}}
        {{-- ═══════════════════════════════════════ --}}
        <div class="step-content" id="step-3" style="display:none;">
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
                <h2 class="font-bold text-lg text-[#111111] mb-4">📋 Ringkasan Jadwal</h2>
                <div id="summaryContent" class="space-y-4"></div>
            </div>

            <div class="flex justify-between mt-4">
                <button type="button" onclick="goBackFromStep3()" class="border border-[#E5E5E5] text-gray-700 px-6 py-3 rounded-[12px] font-semibold hover:bg-[#F5F5F5] transition">← Kembali</button>
                <button type="button" onclick="submitForm()" class="btn-gomad-primary px-8 py-3 rounded-[12px] font-semibold text-lg">
                    💾 {{ old('is_pp') ? 'Buat 2 Jadwal' : 'Buat Jadwal' }}
                </button>
            </div>
        </div>

        {{-- Hidden inputs --}}
        <input type="hidden" name="stop_config" id="stopConfigInputGo">
        <input type="hidden" name="pricing" id="pricingInputGo">
        <input type="hidden" name="pp_stop_config" id="stopConfigInputPP">
        <input type="hidden" name="pp_pricing" id="pricingInputPP">
    </form>
</div>

{{-- MODAL Harga --}}
<div id="pricingModal" style="display:none;" class="fixed inset-0 bg-[#111111]/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-[12px] shadow-xl p-6 max-w-lg w-full max-h-[80vh] overflow-y-auto border border-[#E5E5E5]">
        <h3 class="font-bold text-lg text-[#111111] mb-2">Isi Harga</h3>
        <p class="text-sm text-gray-500 font-light mb-4" id="modalInfo"></p>
        <div id="modalPairs"></div>
        <div class="flex gap-3 mt-4">
            <button type="button" onclick="saveModalPricing()" class="flex-1 btn-gomad-primary py-2.5 rounded-[12px] font-semibold">Simpan Harga</button>
            <button type="button" onclick="closeModal()" class="flex-1 border border-[#E5E5E5] py-2.5 rounded-[12px] font-medium hover:bg-[#F5F5F5] transition">Batal</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ═══════════════════════════════════
// GLOBAL STATE
// ═══════════════════════════════════
var routesData = @json($routesData);
var vehiclesData = @json($vehiclesData);
var agencyCoverage = @json($agency->coverage_cities ?? [$agency->city_code]);
var availableDeposit = {{ $availableDeposit }};
var currentStep = 1;
var isPP = false;
var isRentalVehicle = false;
var selectedRouteId = null;
var selectedVehicleId = null;

// Data Pergi
var stopsGo = [];
var pricingListGo = [];

// Data Pulang
var stopsPP = [];
var pricingListPP = [];

// Modal
var tempModalPairs = [];
var currentModalTarget = 'go';
var pricingModal = document.getElementById('pricingModal');
var modalInfo = document.getElementById('modalInfo');
var modalPairsDiv = document.getElementById('modalPairs');

// ═══════════════════════════════════
// STEP NAVIGATION
// ═══════════════════════════════════
function showStep(step) {
    document.querySelectorAll('.step-content').forEach(el => el.style.display = 'none');
    var el = document.getElementById('step-' + step);
    if (el) el.style.display = 'block';
    currentStep = step;
    updateProgressBar();
    updateStepLabels();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateProgressBar() {
    var totalSteps = isPP ? 4 : 3;
    var stepMap = {1: 1, 2: 2, '2b': 3, 3: isPP ? 4 : 3};
    var currentIdx = stepMap[currentStep] || 1;
    var pct = Math.round((currentIdx / totalSteps) * 100);
    document.getElementById('progressBar').style.width = pct + '%';
}

function updateStepLabels() {
    document.querySelectorAll('.step-label').forEach(el => {
        var s = el.getAttribute('data-step');
        var stepMap = {1: 1, 2: 2, '2b': 3, 3: isPP ? 4 : 3};
        var currentIdx = stepMap[currentStep] || 1;
        var labelIdx = stepMap[s] || 0;
        
        if (labelIdx < currentIdx) {
            el.classList.add('text-green-600');
            el.classList.remove('font-bold', 'text-[#C1121F]', 'text-gray-400');
        } else if (labelIdx === currentIdx) {
            el.classList.add('font-bold', 'text-[#C1121F]');
            el.classList.remove('text-gray-400', 'text-green-600');
        } else {
            el.classList.add('text-gray-400');
            el.classList.remove('font-bold', 'text-[#C1121F]', 'text-green-600');
        }
    });
}

function goToStep1() { showStep(1); }

function goToStep2() {
    if (!validateStep1()) return;
    isPP = document.getElementById('isPP').checked;
    document.getElementById('labelStep2b').classList.toggle('hidden', !isPP);
    document.getElementById('rentalSectionPP').style.display = (isPP && isRentalVehicle) ? 'block' : 'none';
    if (isPP) {
        loadStopsPP(selectedRouteId);
        updateMinPPInfo();
    }
    showStep(2);
}

function goToNextFromStep2() {
    if (!validateStep2()) return;
    if (isPP) {
        updateMinPPInfo();
        showStep('2b');
    } else {
        showStep(3);
        updateSummary();
    }
}

function goToStep3() {
    if (!validateStep2B()) return;
    showStep(3);
    updateSummary();
}

function goBackFromStep3() {
    showStep(isPP ? '2b' : 2);
}

function updatePPUI() {
    isPP = document.getElementById('isPP').checked;
    document.getElementById('labelStep2b').classList.toggle('hidden', !isPP);
    document.getElementById('ppInfoStep1').classList.toggle('hidden', !isPP);
    updatePPRoutePreview();
    updateProgressBar();
}

function updatePPRoutePreview() {
    var routeId = parseInt(document.getElementById('routeSelect').value);
    if (!routeId) return;
    var route = routesData.find(r => r.id === routeId);
    if (route) {
        document.getElementById('ppRoutePreview').textContent = route.destination_city + ' → ' + route.origin_city;
    }
}

function onRouteChange() {
    selectedRouteId = parseInt(document.getElementById('routeSelect').value);
    updatePPRoutePreview();
    updateEstimatedArrival();
    loadStopsGo(selectedRouteId);
    if (isPP) loadStopsPP(selectedRouteId);
}

function onVehicleChange() {
    var sel = document.getElementById('vehicleSelect');
    var opt = sel.options[sel.selectedIndex];
    isRentalVehicle = opt.getAttribute('data-rental') === '1';
    document.getElementById('rentalSectionPP').style.display = (isPP && isRentalVehicle) ? 'block' : 'none';
}

// ═══════════════════════════════════
// ESTIMATED ARRIVAL & PP INFO
// ═══════════════════════════════════
function updateEstimatedArrival() {
    var routeId = parseInt(document.getElementById('routeSelect').value);
    var depDate = document.getElementById('departureDate').value;
    var depTime = document.getElementById('departureTime').value;
    var arrEl = document.getElementById('estimatedArrival');
    var infoEl = document.getElementById('estimatedArrivalInfo');
    
    if (!routeId || !depDate || !depTime) return;
    var route = routesData.find(r => r.id === routeId);
    if (!route || !route.estimated_duration) return;
    
    var dep = new Date(depDate + 'T' + depTime);
    var arr = new Date(dep.getTime() + route.estimated_duration * 60000);
    
    arrEl.value = arr.getFullYear() + '-' + String(arr.getMonth()+1).padStart(2,'0') + '-' + String(arr.getDate()).padStart(2,'0') + 'T' + String(arr.getHours()).padStart(2,'0') + ':' + String(arr.getMinutes()).padStart(2,'0');
    infoEl.textContent = '±' + Math.round(route.estimated_duration/60) + ' jam ' + (route.estimated_duration%60) + ' menit';
    updateMinPPInfo();
}

function updateMinPPInfo() {
    var arrVal = document.getElementById('estimatedArrival').value;
    var restH = parseInt(document.getElementById('ppRestHours')?.value || 2);
    var ppMinEl = document.getElementById('ppMinDateTime');
    var ppDateEl = document.getElementById('ppDate');
    var ppEstArrEl = document.getElementById('ppEstArrival');
    
    if (arrVal && ppEstArrEl) {
        var arr = new Date(arrVal);
        ppEstArrEl.textContent = arr.toLocaleString('id-ID', { day:'numeric', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit' });
        
        var minPP = new Date(arr.getTime() + restH * 3600000);
        if (ppMinEl) ppMinEl.textContent = minPP.toLocaleString('id-ID', { day:'numeric', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit' });
        
        if (ppDateEl) {
            ppDateEl.min = minPP.getFullYear() + '-' + String(minPP.getMonth()+1).padStart(2,'0') + '-' + String(minPP.getDate()).padStart(2,'0');
        }
    }
}

function updateRentalInfo() {
    var restDays = parseInt(document.getElementById('restDaysBeforeRental')?.value || 1);
    var ppDate = document.getElementById('ppDate')?.value;
    var ppTime = document.getElementById('ppTime')?.value || '08:00';
    var arrVal = document.getElementById('estimatedArrival')?.value;
    var rentalInfo = document.getElementById('rentalInfo');
    if (!rentalInfo) return;
    
    var lastDate;
    if (ppDate) {
        lastDate = new Date(ppDate + 'T' + ppTime);
        if (selectedRouteId) {
            var route = routesData.find(r => r.id === selectedRouteId);
            if (route && route.estimated_duration) lastDate = new Date(lastDate.getTime() + route.estimated_duration * 60000);
        }
    } else if (arrVal) {
        lastDate = new Date(arrVal);
    }
    if (lastDate) {
        lastDate.setDate(lastDate.getDate() + restDays);
        rentalInfo.textContent = 'Kendaraan siap rental mulai: ' + lastDate.toLocaleDateString('id-ID', { day:'numeric', month:'long', year:'numeric' });
    }
}

// ═══════════════════════════════════
// LOAD STOPS - PERGI
// ═══════════════════════════════════
function loadStopsGo(routeId) {
    var route = routesData.find(r => r.id === routeId);
    if (!route) return;
    pricingListGo = [];
    
    var uncovered = route.stops.filter(s => !agencyCoverage.includes(s.city_code));
    var covDiv = document.getElementById('coverageCheckGo');
    if (uncovered.length > 0) {
        covDiv.style.display = 'block';
        covDiv.innerHTML = '<div class="bg-red-50 border border-red-200 rounded-[12px] p-4 text-sm text-red-700">⚠️ Agency tidak melayani: ' + uncovered.map(c => c.city_name).join(', ') + '</div>';
    } else {
        covDiv.style.display = 'none';
    }
    
    stopsGo = route.stops.map(s => ({
        id: s.id, city_name: s.city_name, stop_order: s.stop_order,
        is_pickup_available: s.is_first, is_dropoff_available: s.is_last,
        is_pickup_fixed: s.is_first, is_dropoff_fixed: s.is_last,
        is_first: s.is_first, is_last: s.is_last
    }));
    
    var basePrice = parseInt(document.getElementById('basePrice').value) || 150000;
    pricingListGo.push({
        origin_stop_id: stopsGo[0].id, origin_city: stopsGo[0].city_name,
        destination_stop_id: stopsGo[stopsGo.length-1].id, destination_city: stopsGo[stopsGo.length-1].city_name,
        price: basePrice
    });
    
    renderStopsTable('stopsTableBodyGo', stopsGo, 'go');
    document.getElementById('stopConfigSectionGo').style.display = 'block';
    
    var methods = route.payment_methods || ['midtrans','cash','cod'];
    var pmContainer = document.getElementById('paymentMethodsContainerGo');
    var html = '';
    if (methods.includes('midtrans')) html += '<div class="bg-[#F5F5F5] rounded-[12px] p-3 text-center border"><div class="text-xl mb-1">💳</div><p class="font-semibold text-xs">Online</p></div>';
    if (methods.includes('cash')) html += '<div class="bg-[#F5F5F5] rounded-[12px] p-3 text-center border"><div class="text-xl mb-1">🏪</div><p class="font-semibold text-xs">Warung</p></div>';
    if (methods.includes('cod') && route.cod_available) {
        html += '<div class="bg-[#F5F5F5] rounded-[12px] p-3 text-center border"><div class="text-xl mb-1">🚗</div><p class="font-semibold text-xs">COD</p><label class="mt-2 flex items-center justify-center gap-2"><input type="checkbox" name="allow_cod" value="1" class="w-4 h-4 text-[#C1121F]"><span class="text-xs">Aktifkan</span></label></div>';
    }
    pmContainer.innerHTML = html;
    document.getElementById('paymentSectionGo').style.display = 'block';
    
    updatePricingSummary('pricingListGo', 'pricingSummaryGo', pricingListGo, 'go');
}

// ═══════════════════════════════════
// LOAD STOPS - PULANG (via API)
// ═══════════════════════════════════
async function loadStopsPP(routeId) {
    if (!routeId) return;
    
    var route = routesData.find(r => r.id === routeId);
    if (!route) return;
    
    pricingListPP = [];
    
    document.getElementById('stopsTableBodyPP').innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 font-light">⏳ Memuat data rute PP...</td></tr>';
    
    try {
        var res = await fetch('/api/v1/routes/' + routeId + '/return-route');
        if (!res.ok) throw new Error('HTTP ' + res.status);
        
        var data = await res.json();
        
        if (!data.success || !data.data) throw new Error('Data tidak valid');
        
        var ppRoute = data.data;
        
        document.getElementById('ppRouteName').textContent = ppRoute.route_name || (route.destination_city + ' → ' + route.origin_city);
        
        if (ppRoute.stops && ppRoute.stops.length > 0) {
            stopsPP = ppRoute.stops.map(function(s, i) {
                return {
                    id: s.id,
                    city_name: s.city_name,
                    stop_order: s.stop_order || (i + 1),
                    is_pickup_available: s.is_first || false,
                    is_dropoff_available: s.is_last || false,
                    is_pickup_fixed: s.is_first || false,
                    is_dropoff_fixed: s.is_last || false,
                    is_first: s.is_first || false,
                    is_last: s.is_last || false,
                };
            });
        } else {
            throw new Error('Data stop PP kosong');
        }
        
        var ppPrice = parseInt(document.getElementById('ppPrice')?.value) || parseInt(document.getElementById('basePrice')?.value) || 150000;
        var firstStop = stopsPP[0];
        var lastStop = stopsPP[stopsPP.length - 1];
        
        if (firstStop && lastStop) {
            pricingListPP.push({
                origin_stop_id: firstStop.id,
                origin_city: firstStop.city_name,
                destination_stop_id: lastStop.id,
                destination_city: lastStop.city_name,
                price: ppPrice,
            });
        }
        
        renderStopsTable('stopsTableBodyPP', stopsPP, 'pp');
        document.getElementById('stopConfigSectionPP').style.display = 'block';
        updatePricingSummary('pricingListPP', 'pricingSummaryPP', pricingListPP, 'pp');
        
    } catch (err) {
        console.error('Failed to load PP route:', err);
        
        document.getElementById('stopsTableBodyPP').innerHTML = 
            '<tr><td colspan="4" class="px-4 py-6 text-center">' +
            '<div class="bg-red-50 border border-red-200 rounded-[12px] p-4">' +
            '<p class="text-red-700 font-medium text-sm mb-2">❌ Gagal memuat data rute PP</p>' +
            '<p class="text-red-600 text-xs font-light mb-3">' + (err.message || 'Terjadi kesalahan') + '</p>' +
            '<div class="flex gap-2 justify-center">' +
            '<button type="button" onclick="loadStopsPP(' + routeId + ')" class="bg-[#BA1826] text-white px-4 py-2 rounded-[10px] text-sm font-medium hover:bg-[#8A0F18] transition">🔄 Coba Lagi</button>' +
            '<button type="button" onclick="disablePP()" class="border border-[#E5E5E5] text-gray-600 px-4 py-2 rounded-[10px] text-sm font-medium hover:bg-[#F5F5F5] transition">Lewati (Nonaktifkan PP)</button>' +
            '</div></div></td></tr>';
        document.getElementById('stopConfigSectionPP').style.display = 'block';
        document.getElementById('pricingSummaryPP').style.display = 'none';
    }
}

function disablePP() {
    document.getElementById('isPP').checked = false;
    isPP = false;
    document.getElementById('labelStep2b').classList.add('hidden');
    document.getElementById('rentalSectionPP').style.display = 'none';
    updateProgressBar();
    updateStepLabels();
    showStep(2);
    alert('Fitur PP dinonaktifkan. Anda tetap bisa membuat jadwal pergi saja.');
}

function updatePPSummaryPrice() {
    var ppPrice = parseInt(document.getElementById('ppPrice')?.value) || parseInt(document.getElementById('basePrice')?.value) || 150000;
    pricingListPP.forEach(function(p) { p.price = ppPrice; });
    updatePricingSummary('pricingListPP', 'pricingSummaryPP', pricingListPP, 'pp');
}

// ═══════════════════════════════════
// RENDER STOPS TABLE
// ═══════════════════════════════════
function renderStopsTable(tableId, stopList, target) {
    var html = '';
    stopList.forEach(function(s) {
        html += '<tr class="border-t">';
        html += '<td class="px-4 py-3"><span class="text-xs font-mono text-gray-400">Stop ' + s.stop_order + '</span></td>';
        html += '<td class="px-4 py-3"><span class="font-medium">' + s.city_name + '</span></td>';
        html += '<td class="px-4 py-3 text-center"><input type="checkbox" ' + (s.is_pickup_available ? 'checked' : '') + ' ' + (s.is_pickup_fixed ? 'disabled' : '') + ' onchange="toggleStop(' + s.id + ', \'pickup\', this.checked, \'' + target + '\')" class="w-5 h-5 text-[#C1121F] rounded"></td>';
        html += '<td class="px-4 py-3 text-center"><input type="checkbox" ' + (s.is_dropoff_available ? 'checked' : '') + ' ' + (s.is_dropoff_fixed ? 'disabled' : '') + ' onchange="toggleStop(' + s.id + ', \'dropoff\', this.checked, \'' + target + '\')" class="w-5 h-5 text-[#C1121F] rounded"></td>';
        html += '</tr>';
    });
    document.getElementById(tableId).innerHTML = html;
}

function toggleStop(stopId, type, enabled, target) {
    var stopList = target === 'go' ? stopsGo : stopsPP;
    var pricingList = target === 'go' ? pricingListGo : pricingListPP;
    var stop = stopList.find(s => s.id === stopId);
    if (!stop) return;
    
    if (type === 'pickup') stop.is_pickup_available = enabled;
    else stop.is_dropoff_available = enabled;
    
    if (!enabled) {
        if (type === 'pickup') {
            if (target === 'go') pricingListGo = pricingListGo.filter(p => p.origin_stop_id !== stopId);
            else pricingListPP = pricingListPP.filter(p => p.origin_stop_id !== stopId);
        } else {
            if (target === 'go') pricingListGo = pricingListGo.filter(p => p.destination_stop_id !== stopId);
            else pricingListPP = pricingListPP.filter(p => p.destination_stop_id !== stopId);
        }
    } else {
        var basePrice;
        if (target === 'go') {
            basePrice = parseInt(document.getElementById('basePrice').value) || 150000;
        } else {
            basePrice = parseInt(document.getElementById('ppPrice')?.value) || parseInt(document.getElementById('basePrice')?.value) || 150000;
        }
        
        var newPairs = findNewPairs(stop, type, stopList, pricingList, basePrice);
        
        if (newPairs.length > 0) {
            tempModalPairs = newPairs;
            currentModalTarget = target;
            showModal(newPairs);
        }
    }
    
    if (target === 'go') {
        pricingListGo = pricingList;
        updatePricingSummary('pricingListGo', 'pricingSummaryGo', pricingListGo, 'go');
    } else {
        pricingListPP = pricingList;
        updatePricingSummary('pricingListPP', 'pricingSummaryPP', pricingListPP, 'pp');
    }
    
    renderStopsTable(target === 'go' ? 'stopsTableBodyGo' : 'stopsTableBodyPP', stopList, target);
}

function findNewPairs(changedStop, type, stopList, pricingList, basePrice) {
    var pairs = [];
    if (type === 'pickup') {
        stopList.filter(function(s) {
            return s.is_dropoff_available && s.stop_order > changedStop.stop_order;
        }).forEach(function(ds) {
            var exists = pricingList.find(function(p) {
                return p.origin_stop_id === changedStop.id && p.destination_stop_id === ds.id;
            });
            if (!exists) {
                pairs.push({ 
                    origin_stop_id: changedStop.id, origin_city: changedStop.city_name, 
                    destination_stop_id: ds.id, destination_city: ds.city_name, price: basePrice 
                });
            }
        });
    } else {
        stopList.filter(function(s) {
            return s.is_pickup_available && s.stop_order < changedStop.stop_order;
        }).forEach(function(ps) {
            var exists = pricingList.find(function(p) {
                return p.origin_stop_id === ps.id && p.destination_stop_id === changedStop.id;
            });
            if (!exists) {
                pairs.push({ 
                    origin_stop_id: ps.id, origin_city: ps.city_name, 
                    destination_stop_id: changedStop.id, destination_city: changedStop.city_name, price: basePrice 
                });
            }
        });
    }
    return pairs;
}

// ═══════════════════════════════════
// MODAL
// ═══════════════════════════════════
function showModal(pairs) {
    if (!pairs || pairs.length === 0) {
        console.log('No pairs to show in modal');
        return;
    }
    
    modalInfo.textContent = 'Isi harga untuk ' + pairs.length + ' kombinasi stop:';
    var html = '';
    pairs.forEach(function(p, i) {
        html += '<div class="mb-3 bg-[#F5F5F5] rounded-xl p-3 border border-[#E5E5E5]">';
        html += '<p class="text-sm font-medium mb-2 text-[#111111]">' + p.origin_city + ' → ' + p.destination_city + '</p>';
        html += '<div class="flex items-center gap-2">';
        html += '<span class="text-sm text-gray-500">Rp</span>';
        html += '<input type="number" id="modalPrice' + i + '" value="' + (p.price || '') + '" class="flex-1 px-3 py-2 border border-[#E5E5E5] rounded-lg text-sm bg-white focus:border-[#C1121F] outline-none" min="1000" placeholder="Harga">';
        html += '</div>';
        html += '</div>';
    });
    modalPairsDiv.innerHTML = html;
    pricingModal.style.display = 'flex';
    
    setTimeout(function() {
        var firstInput = document.getElementById('modalPrice0');
        if (firstInput) firstInput.focus();
    }, 100);
}

function closeModal() { 
    pricingModal.style.display = 'none'; 
    tempModalPairs = []; 
}

function saveModalPricing() {
    var validPairs = [];
    var hasError = false;
    
    tempModalPairs.forEach(function(p, i) {
        var input = document.getElementById('modalPrice' + i);
        var price = input ? parseInt(input.value) : 0;
        
        if (!price || price < 1000) {
            if (input) {
                input.style.borderColor = '#EF4444';
                input.style.backgroundColor = '#FEF2F2';
            }
            hasError = true;
        } else {
            validPairs.push({ 
                origin_stop_id: p.origin_stop_id, origin_city: p.origin_city, 
                destination_stop_id: p.destination_stop_id, destination_city: p.destination_city, 
                price: price 
            });
        }
    });
    
    if (hasError) {
        alert('Semua harga harus diisi (minimal Rp 1.000)!');
        return;
    }
    
    if (validPairs.length > 0) {
        if (currentModalTarget === 'go') {
            pricingListGo = pricingListGo.concat(validPairs);
            updatePricingSummary('pricingListGo', 'pricingSummaryGo', pricingListGo, 'go');
        } else {
            pricingListPP = pricingListPP.concat(validPairs);
            updatePricingSummary('pricingListPP', 'pricingSummaryPP', pricingListPP, 'pp');
        }
        console.log('Pricing saved for ' + currentModalTarget + ': ' + validPairs.length + ' pairs');
    }
    
    closeModal();
}

function updatePricingSummary(listDivId, summaryDivId, pricingList, target) {
    var summaryDiv = document.getElementById(summaryDivId);
    var listDiv = document.getElementById(listDivId);
    if (!summaryDiv || !listDiv) return;
    
    if (!pricingList || pricingList.length === 0) { 
        summaryDiv.style.display = 'none'; 
        return; 
    }
    summaryDiv.style.display = 'block';
    var html = '';
    pricingList.forEach(function(p) {
        html += '<div class="bg-green-50 border border-green-200 rounded-lg px-3 py-2 text-sm flex justify-between items-center">';
        html += '<span>' + p.origin_city + ' → ' + p.destination_city + '</span>';
        html += '<div class="flex items-center gap-2">';
        html += '<span class="font-bold text-green-700">Rp ' + formatRupiah(p.price) + '</span>';
        html += '<button type="button" onclick="removePricing(' + p.origin_stop_id + ',' + p.destination_stop_id + ',\'' + target + '\')" class="text-red-500 text-xs hover:underline ml-2">✕</button>';
        html += '</div>';
        html += '</div>';
    });
    listDiv.innerHTML = html;
    
    // ✅ TAMBAHKAN: Cek missing pairs
    var missing = checkMissingPairs(target === 'go' ? stopsGo : stopsPP, pricingList);
    var warningEl = document.getElementById(target === 'go' ? 'pricingWarningGo' : 'pricingWarningPP');
    if (warningEl) {
        if (missing.length > 0) {
            warningEl.textContent = 'Masih ada ' + missing.length + ' kombinasi yang belum diisi!';
            warningEl.classList.remove('hidden');
        } else {
            warningEl.classList.add('hidden');
        }
    }
}

function removePricing(originId, destId, target) {
    if (target === 'go') {
        pricingListGo = pricingListGo.filter(p => !(p.origin_stop_id === originId && p.destination_stop_id === destId));
        updatePricingSummary('pricingListGo', 'pricingSummaryGo', pricingListGo, 'go');
    } else {
        pricingListPP = pricingListPP.filter(p => !(p.origin_stop_id === originId && p.destination_stop_id === destId));
        updatePricingSummary('pricingListPP', 'pricingSummaryPP', pricingListPP, 'pp');
    }
}

// ═══════════════════════════════════
// VALIDATION
// ═══════════════════════════════════
function validateStep1() {
    if (!document.getElementById('routeSelect').value) { alert('Pilih rute!'); return false; }
    if (!document.getElementById('vehicleSelect').value) { alert('Pilih kendaraan!'); return false; }
    if (!document.getElementById('basePrice').value || parseInt(document.getElementById('basePrice').value) < 1000) { alert('Harga minimal Rp 1.000!'); return false; }
    return true;
}

function validateStep2() {
    if (!document.getElementById('departureDate').value) { alert('Isi tanggal!'); return false; }
    if (!document.getElementById('departureTime').value) { alert('Isi jam!'); return false; }
    var missing = checkMissingPairs(stopsGo, pricingListGo);
    if (missing.length > 0) { alert('Harga belum diisi: ' + missing.join(', ')); return false; }
    return true;
}

function validateStep2B() {
    if (!document.getElementById('ppDate').value) { alert('Isi tanggal PP!'); return false; }
    if (!document.getElementById('ppTime').value) { alert('Isi jam PP!'); return false; }
    if (!document.getElementById('ppPrice').value || parseInt(document.getElementById('ppPrice').value) < 1000) { alert('Harga PP minimal Rp 1.000!'); return false; }
    
    if (!pricingListPP || pricingListPP.length === 0) {
        alert('Harga PP wajib diisi! Atur pickup/dropoff dan isi harga untuk setiap kombinasi stop PP.');
        return false;
    }
    
    var missing = checkMissingPairs(stopsPP, pricingListPP);
    if (missing.length > 0) { 
        alert('Harga PP belum diisi: ' + missing.join(', ')); 
        return false; 
    }
    
    if (!stopsPP || stopsPP.length === 0) {
        alert('Konfigurasi stop PP gagal dimuat. Coba refresh halaman.');
        return false;
    }
    
    return true;
}

function checkMissingPairs(stopList, pricingList) {
    var missing = [];
    if (!stopList || !pricingList) return missing;
    
    var pickups = stopList.filter(s => s.is_pickup_available);
    var dropoffs = stopList.filter(s => s.is_dropoff_available);
    pickups.forEach(function(ps) {
        dropoffs.forEach(function(ds) {
            if (ds.stop_order > ps.stop_order) {
                if (!pricingList.find(p => p.origin_stop_id === ps.id && p.destination_stop_id === ds.id)) {
                    missing.push(ps.city_name + ' → ' + ds.city_name);
                }
            }
        });
    });
    return missing;
}

// ═══════════════════════════════════
// SUMMARY & SUBMIT
// ═══════════════════════════════════
function updateSummary() {
    var html = '';
    var routeName = document.getElementById('routeSelect').options[document.getElementById('routeSelect').selectedIndex].text;
    var vehiclePlate = document.getElementById('vehicleSelect').options[document.getElementById('vehicleSelect').selectedIndex].text;
    var driverName = document.querySelector('[name="driver_id"]').options[document.querySelector('[name="driver_id"]').selectedIndex]?.text || 'Tidak ada';
    var kelas = document.querySelector('[name="travel_class"]').options[document.querySelector('[name="travel_class"]').selectedIndex].text;
    var harga = formatRupiah(document.getElementById('basePrice').value || 150000);
    var depDate = document.getElementById('departureDate').value || '-';
    var depTime = document.getElementById('departureTime').value || '-';
    var estArr = document.getElementById('estimatedArrival').value ? new Date(document.getElementById('estimatedArrival').value).toLocaleString('id-ID') : '-';
    var codActive = document.querySelector('[name="allow_cod"]')?.checked ? 'Ya' : 'Tidak';
    
    html += '<div class="bg-[#F5F5F5] rounded-[12px] p-4 border"><h3 class="font-bold text-[#111111] mb-2">🚐 Jadwal Pergi</h3>';
    html += '<div class="grid grid-cols-2 gap-2 text-sm"><div><span class="text-gray-400">Rute:</span> ' + routeName + '</div><div><span class="text-gray-400">Kendaraan:</span> ' + vehiclePlate + '</div>';
    html += '<div><span class="text-gray-400">Driver:</span> ' + driverName + '</div><div><span class="text-gray-400">Kelas:</span> ' + kelas + '</div>';
    html += '<div><span class="text-gray-400">Tanggal:</span> ' + depDate + ' ' + depTime + '</div><div><span class="text-gray-400">Harga:</span> Rp ' + harga + '/seat</div>';
    html += '<div><span class="text-gray-400">Estimasi Tiba:</span> ' + estArr + '</div><div><span class="text-gray-400">COD:</span> ' + codActive + '</div></div></div>';
    
    if (isPP) {
        var ppPrice = formatRupiah(document.getElementById('ppPrice')?.value || document.getElementById('basePrice').value || 150000);
        var ppDate = document.getElementById('ppDate')?.value || '-';
        var ppTime = document.getElementById('ppTime')?.value || '-';
        var ppRoute = document.getElementById('ppRouteName')?.textContent || '-';
        var rentalReady = document.getElementById('rentalInfo')?.textContent || '-';
        
        html += '<div class="bg-purple-50 rounded-[12px] p-4 border border-purple-200 mt-3"><h3 class="font-bold text-[#111111] mb-2">🔄 Jadwal Pulang (PP)</h3>';
        html += '<div class="grid grid-cols-2 gap-2 text-sm"><div><span class="text-gray-400">Rute:</span> ' + ppRoute + '</div><div><span class="text-gray-400">Kendaraan:</span> ' + vehiclePlate + '</div>';
        html += '<div><span class="text-gray-400">Driver:</span> ' + driverName + '</div><div><span class="text-gray-400">Kelas:</span> ' + kelas + '</div>';
        html += '<div><span class="text-gray-400">Tanggal:</span> ' + ppDate + ' ' + ppTime + '</div><div><span class="text-gray-400">Harga:</span> Rp ' + ppPrice + '/seat</div>';
        html += '<div class="col-span-2"><span class="text-gray-400">' + rentalReady + '</span></div></div></div>';
    }
    
    if (isRentalVehicle) {
        var lastDate;
        if (isPP) {
            var ppD = document.getElementById('ppDate')?.value;
            var ppT = document.getElementById('ppTime')?.value || '08:00';
            if (ppD) lastDate = new Date(ppD + 'T' + ppT);
        } else {
            var arrV = document.getElementById('estimatedArrival')?.value;
            if (arrV) lastDate = new Date(arrV);
            else { var dD = document.getElementById('departureDate')?.value; var dT = document.getElementById('departureTime')?.value || '08:00'; if (dD) lastDate = new Date(dD + 'T' + dT); }
        }
        if (lastDate) {
            var restDays = parseInt(document.getElementById('restDaysBeforeRental')?.value || 1);
            lastDate.setDate(lastDate.getDate() + restDays);
            html += '<div class="bg-blue-50 rounded-[12px] p-3 border border-blue-200 mt-3 text-sm"><span class="text-blue-700">🚗 Kendaraan siap rental mulai: ' + lastDate.toLocaleDateString('id-ID', { day:'numeric', month:'long', year:'numeric' }) + '</span></div>';
        }
    }
    
    document.getElementById('summaryContent').innerHTML = html;
}

function submitForm() {
    document.getElementById('stopConfigInputGo').value = JSON.stringify(stopsGo.map(s => ({ route_stop_id: s.id, is_pickup_available: s.is_pickup_available, is_dropoff_available: s.is_dropoff_available })));
    document.getElementById('pricingInputGo').value = JSON.stringify(pricingListGo.map(p => ({ origin_stop_id: p.origin_stop_id, destination_stop_id: p.destination_stop_id, price: p.price })));
    
    if (isPP) {
        document.getElementById('stopConfigInputPP').value = JSON.stringify(stopsPP.map(s => ({ route_stop_id: s.id, is_pickup_available: s.is_pickup_available, is_dropoff_available: s.is_dropoff_available })));
        document.getElementById('pricingInputPP').value = JSON.stringify(pricingListPP.map(p => ({ origin_stop_id: p.origin_stop_id, destination_stop_id: p.destination_stop_id, price: p.price })));
    }
    
    document.getElementById('scheduleForm').submit();
}

function formatRupiah(num) { return new Intl.NumberFormat('id-ID').format(num || 0); }

// Close modal on overlay click
pricingModal.addEventListener('click', function(e) { if (e.target === pricingModal) closeModal(); });

// Init
@if(old('route_id'))
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('routeSelect').value = '{{ old('route_id') }}';
    onRouteChange();
    @if(old('is_pp'))
    document.getElementById('isPP').checked = true;
    updatePPUI();
    @endif
    @if(old('departure_date'))
    showStep(2);
    @endif
});
@endif
</script>
@endpush
@endsection