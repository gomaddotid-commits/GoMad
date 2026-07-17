@extends('layouts.agency')

@section('title', 'Buat Jadwal')
@section('content')
@php
    $routes = \App\Models\Route::where('is_active', true)
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

    // Prepare routes data for JavaScript
    $routesData = $routes->map(function($route) {
        $stops = $route->stops->map(function($stop, $index) use ($route) {
            $totalStops = $route->stops->count();
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
            'max_price' => (float) ($route->max_price ?? 0),
            'cod_available' => (bool) $route->cod_available,
            'cod_min_deposit' => (float) ($route->cod_min_deposit ?? 500000),
            'payment_methods' => $route->payment_methods_array,
            'stops' => $stops,
        ];
    })->values()->toArray();
@endphp

<div class="max-w-5xl mx-auto" id="scheduleFormApp">
    <h1 class="text-2xl font-bold text-[#111111] mb-6">Buat Jadwal Baru</h1>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[12px] mb-6 text-sm">
        @foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
    </div>
    @endif

    <form action="{{ route('agency.schedules.store') }}" method="POST" id="scheduleForm">
        @csrf
        
        {{-- STEP 1: Info Dasar --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
            <h2 class="font-bold text-lg text-[#111111] mb-4">Informasi Dasar</h2>
            
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Rute <span class="text-[#C1121F]">*</span></label>
                    <select name="route_id" id="routeSelect" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                        <option value="">Pilih Rute</option>
                        @foreach($routes as $route)
                        <option value="{{ $route->id }}" {{ old('route_id') == $route->id ? 'selected' : '' }}>
                            {{ $route->route_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kendaraan <span class="text-[#C1121F]">*</span></label>
                    <select name="vehicle_id" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                        <option value="">Pilih Kendaraan</option>
                        @foreach($vehicles as $v)
                        <option value="{{ $v->id }}" {{ old('vehicle_id') == $v->id ? 'selected' : '' }}>{{ $v->plate_number }} ({{ $v->capacity }} seat)</option>
                        @endforeach
                    </select>
                    @if($vehicles->isEmpty())
                    <p class="text-xs text-[#C1121F] mt-1 font-light">Belum ada kendaraan. <a href="{{ route('agency.vehicles.create') }}" class="underline">Tambah</a></p>
                    @endif
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Driver</label>
                    <select name="driver_id" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                        <option value="">Pilih Driver (opsional)</option>
                        @foreach($drivers as $d)
                        <option value="{{ $d->id }}" {{ old('driver_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                        Tanggal (min {{ \Carbon\Carbon::parse($minDate)->format('d M Y') }}) <span class="text-[#C1121F]">*</span>
                    </label>
                    <input type="date" name="departure_date" min="{{ $minDate }}" value="{{ old('departure_date') }}" 
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Jam <span class="text-[#C1121F]">*</span></label>
                    <input type="time" name="departure_time" value="{{ old('departure_time', '08:00') }}" 
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Kelas <span class="text-[#C1121F]">*</span></label>
                    <select name="travel_class" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                        <option value="economy" {{ old('travel_class') == 'economy' ? 'selected' : '' }}>Ekonomi</option>
                        <option value="premium" {{ old('travel_class') == 'premium' ? 'selected' : '' }}>Premium</option>
                        <option value="charter" {{ old('travel_class') == 'charter' ? 'selected' : '' }}>Charter</option>
                    </select>
                </div>
            </div>
            
            <div class="grid md:grid-cols-3 gap-4 mt-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Harga Dasar per Seat (Rp) <span class="text-[#C1121F]">*</span></label>
                    <input type="number" name="price_per_seat" id="basePrice" value="{{ old('price_per_seat', 150000) }}" 
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" min="1000" required>
                    <p class="text-[10px] text-gray-400 mt-1 font-light" id="maxPriceInfo"></p>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Max Overload (Ekonomi)</label>
                    <input type="number" name="max_overload" value="{{ old('max_overload', 2) }}" min="0" max="2" 
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Batas Bagasi (kg/orang)</label>
                    <input type="number" name="baggage_limit_kg" value="{{ old('baggage_limit_kg', 15) }}" min="0" max="50" 
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                </div>
            </div>
        </div>

        {{-- STEP 2: Konfigurasi Stop --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm" id="stopConfigSection" style="display:none;">
            <h2 class="font-bold text-lg text-[#111111] mb-4">Konfigurasi Stop & Harga</h2>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-4 mb-4 text-sm text-yellow-800">
                <p class="font-mono uppercase tracking-wider text-xs font-medium">Cara Konfigurasi:</p>
                <ol class="list-decimal list-inside mt-1 space-y-1 font-light">
                    <li>Centang <strong>Pickup</strong> atau <strong>Dropoff</strong> di setiap stop tengah</li>
                    <li>Sistem akan menampilkan popup untuk mengisi harga kombinasi baru</li>
                    <li>Stop pertama (Pickup wajib) dan stop terakhir (Dropoff wajib) sudah otomatis</li>
                </ol>
            </div>

            {{-- Coverage Check --}}
            <div id="coverageCheck" class="mb-4" style="display:none;"></div>

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
                    <tbody id="stopsTableBody">
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 font-light">Pilih rute terlebih dahulu</td></tr>
                    </tbody>
                </table>
            </div>

            <div id="pricingSummary" style="display:none;">
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold mb-2">Harga yang sudah dikonfigurasi:</h3>
                <div id="pricingList" class="grid grid-cols-1 md:grid-cols-2 gap-2"></div>
                <p id="pricingWarning" class="text-[#C1121F] text-sm mt-2 hidden font-medium">Masih ada kombinasi yang belum diisi harganya!</p>
            </div>
        </div>

        {{-- STEP 3: Pengaturan Pembayaran --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm" id="paymentSection" style="display:none;">
            <h2 class="font-bold text-lg text-[#111111] mb-4">Pengaturan Pembayaran</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="paymentMethodsContainer">
                <div id="onlinePaymentCard" class="bg-[#F5F5F5] rounded-[12px] p-4 text-center border border-[#E5E5E5]" style="display:none;">
                    <div class="text-2xl mb-2">💳</div>
                    <p class="font-semibold text-[#111111] text-sm">Online (Midtrans)</p>
                    <p class="text-xs text-gray-500 mt-1 font-light">Selalu tersedia</p>
                </div>
                
                <div id="cashPaymentCard" class="bg-[#F5F5F5] rounded-[12px] p-4 text-center border border-[#E5E5E5]" style="display:none;">
                    <div class="text-2xl mb-2">🏪</div>
                    <p class="font-semibold text-[#111111] text-sm">Warung GoMad</p>
                    <p class="text-xs text-gray-500 mt-1 font-light">Selalu tersedia</p>
                </div>
                
                <div id="codPaymentCard" class="rounded-[12px] p-4 text-center border bg-[#F5F5F5] border-[#E5E5E5]" style="display:none;">
                    <div class="text-2xl mb-2">🚗</div>
                    <p class="font-semibold text-sm text-gray-700">COD (Bayar ke Sopir)</p>
                    
                    <div class="mt-3">
                        <label class="flex items-center justify-center gap-2 cursor-pointer">
                            <input type="checkbox" name="allow_cod" value="1" id="allowCod" 
                                class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]">
                            <span class="text-sm font-medium text-[#111111]">Aktifkan COD</span>
                        </label>
                    </div>
                    
                    <div id="codInfo" class="mt-3 bg-orange-50 border border-orange-200 rounded-lg p-3 text-xs text-orange-800 text-left" style="display:none;">
                        <p class="font-mono uppercase tracking-wider text-xs font-medium mb-1">ℹ️ Informasi COD:</p>
                        <ul class="list-disc list-inside space-y-1 font-light">
                            <li>Customer bayar tunai ke sopir saat penjemputan</li>
                            <li>Butuh saldo deposit: <strong id="codMinDepositLabel">Rp 0</strong></li>
                            <li>Saldo deposit tersedia: <strong>Rp {{ number_format($availableDeposit, 0, ',', '.') }}</strong></li>
                        </ul>
                    </div>
                    
                    <div id="codWarning" class="bg-red-50 border border-red-200 rounded-lg p-3 mt-3 text-sm text-red-700 hidden">
                        ⚠️ Saldo deposit tidak mencukupi. 
                        <a href="{{ route('agency.wallet.topup') }}" target="_blank" class="text-red-600 underline font-medium">Top Up sekarang</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hidden inputs --}}
        <input type="hidden" name="stop_config" id="stopConfigInput" value="[]">
        <input type="hidden" name="pricing" id="pricingInput" value="[]">

        <div class="flex gap-4">
            <button type="button" onclick="submitForm()" class="btn-gomad-primary px-8 py-3 rounded-[12px] font-semibold">Buat Jadwal</button>
            <a href="{{ route('agency.schedules.index') }}" class="border border-[#E5E5E5] text-gray-700 px-6 py-3 rounded-[12px] font-semibold hover:bg-[#F5F5F5] transition">Batal</a>
        </div>
    </form>
</div>

{{-- MODAL Input Harga --}}
<div id="pricingModal" style="display:none;" class="fixed inset-0 bg-[#111111]/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-[12px] shadow-xl p-6 max-w-lg w-full max-h-[80vh] overflow-y-auto border border-[#E5E5E5]">
        <h3 class="font-bold text-lg text-[#111111] mb-2">Isi Harga</h3>
        <p class="text-sm text-gray-500 font-light mb-4" id="modalInfo"></p>
        <div id="modalPairs"></div>
        <div class="flex gap-3 mt-4">
            <button type="button" onclick="saveModalPricing()" class="flex-1 btn-gomad-primary">Simpan Harga</button>
            <button type="button" onclick="closeModal()" class="flex-1 border border-[#E5E5E5] py-2 rounded-[12px]">Batal</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
var routesData = @json($routesData);
var agencyCoverage = @json($agency->coverage_cities ?? [$agency->city_code]);
var availableDeposit = {{ $availableDeposit }};
var selectedRouteId = null;
var stops = [];
var pricingList = [];
var tempModalPairs = [];

var routeSelect = document.getElementById('routeSelect');
var stopConfigSection = document.getElementById('stopConfigSection');
var stopsTableBody = document.getElementById('stopsTableBody');
var pricingSummary = document.getElementById('pricingSummary');
var pricingListDiv = document.getElementById('pricingList');
var pricingWarning = document.getElementById('pricingWarning');
var stopConfigInput = document.getElementById('stopConfigInput');
var pricingInput = document.getElementById('pricingInput');
var pricingModal = document.getElementById('pricingModal');
var modalInfo = document.getElementById('modalInfo');
var modalPairsDiv = document.getElementById('modalPairs');
var basePriceInput = document.getElementById('basePrice');
var maxPriceInfo = document.getElementById('maxPriceInfo');
var coverageCheckDiv = document.getElementById('coverageCheck');

routeSelect.addEventListener('change', function() {
    selectedRouteId = parseInt(this.value);
    if (!selectedRouteId) {
        stopConfigSection.style.display = 'none';
        document.getElementById('paymentSection').style.display = 'none';
        coverageCheckDiv.style.display = 'none';
        return;
    }
    loadStops(selectedRouteId);
});

function loadStops(routeId) {
    var route = routesData.find(function(r) { return r.id === routeId; });
    if (!route) return;
    
    pricingList = [];
    
    // Check coverage
    checkAgencyCoverage(route);
    
    // Tampilkan max price info
    if (route.max_price > 0) {
        maxPriceInfo.textContent = 'Maksimal harga: Rp ' + formatRupiah(route.max_price);
        basePriceInput.max = route.max_price;
    } else {
        maxPriceInfo.textContent = '';
        basePriceInput.removeAttribute('max');
    }
    
    // ========================================
    // SETUP PAYMENT METHODS
    // ========================================
    var paymentMethods = route.payment_methods || ['midtrans', 'cash', 'cod'];
    var paymentSection = document.getElementById('paymentSection');
    var onlineCard = document.getElementById('onlinePaymentCard');
    var cashCard = document.getElementById('cashPaymentCard');
    var codCard = document.getElementById('codPaymentCard');
    var codInfo = document.getElementById('codInfo');
    var codWarning = document.getElementById('codWarning');
    var allowCod = document.getElementById('allowCod');
    var codMinDepositLabel = document.getElementById('codMinDepositLabel');
    
    onlineCard.style.display = paymentMethods.includes('midtrans') ? 'block' : 'none';
    cashCard.style.display = paymentMethods.includes('cash') ? 'block' : 'none';
    
    if (paymentMethods.includes('cod') && route.cod_available) {
        codCard.style.display = 'block';
        var requiredDeposit = route.cod_min_deposit || 500000;
        codMinDepositLabel.textContent = 'Rp ' + formatRupiah(requiredDeposit);
        
        allowCod.onchange = function() {
            codInfo.style.display = this.checked ? 'block' : 'none';
        };
        
        if (availableDeposit < requiredDeposit) {
            codWarning.classList.remove('hidden');
            codWarning.innerHTML = '⚠️ Saldo deposit: <strong>Rp ' + formatRupiah(availableDeposit) + '</strong>. Butuh Rp ' + formatRupiah(requiredDeposit) + '. <a href="{{ route("agency.wallet.topup") }}" target="_blank" class="text-red-600 underline font-medium">Top Up</a>';
            allowCod.disabled = true;
        } else {
            codWarning.classList.add('hidden');
            allowCod.disabled = false;
        }
    } else {
        codCard.style.display = 'none';
    }
    
    paymentSection.style.display = 'block';
    
    // ========================================
    // SETUP STOPS
    // ========================================
    stops = route.stops.map(function(stop, index) {
        return {
            id: stop.id,
            city_code: stop.city_code,
            city_name: stop.city_name,
            stop_order: stop.stop_order,
            is_pickup_available: stop.is_first ? true : false,
            is_dropoff_available: stop.is_last ? true : false,
            is_pickup_fixed: stop.is_first,
            is_dropoff_fixed: stop.is_last,
            is_first: stop.is_first,
            is_last: stop.is_last,
            in_coverage: agencyCoverage.includes(stop.city_code),
        };
    });
    
    var firstStop = stops[0];
    var lastStop = stops[stops.length - 1];
    var basePrice = parseInt(basePriceInput.value) || 150000;
    
    pricingList.push({
        origin_stop_id: firstStop.id,
        origin_city: firstStop.city_name,
        destination_stop_id: lastStop.id,
        destination_city: lastStop.city_name,
        price: basePrice,
    });
    
    renderStopsTable();
    stopConfigSection.style.display = 'block';
    updatePricingSummary();
}

function checkAgencyCoverage(route) {
    var uncoveredCities = [];
    
    route.stops.forEach(function(stop) {
        if (!agencyCoverage.includes(stop.city_code)) {
            uncoveredCities.push(stop.city_name);
        }
    });
    
    if (uncoveredCities.length > 0) {
        coverageCheckDiv.style.display = 'block';
        coverageCheckDiv.innerHTML = 
            '<div class="bg-red-50 border border-red-200 rounded-[12px] p-4 text-sm text-red-700">' +
            '<p class="font-medium">⚠️ Agency Anda tidak melayani kota berikut:</p>' +
            '<ul class="list-disc list-inside mt-2 font-light">' +
            uncoveredCities.map(function(c) { return '<li>' + c + '</li>'; }).join('') +
            '</ul>' +
            '<p class="mt-2 font-light">Update <a href="{{ route("agency.profile.edit") }}" class="underline font-medium">Zona Layanan</a> terlebih dahulu.</p>' +
            '</div>';
    } else {
        coverageCheckDiv.style.display = 'none';
        coverageCheckDiv.innerHTML = '';
    }
}

function renderStopsTable() {
    var html = '';
    stops.forEach(function(stop) {
        html += '<tr class="border-t">';
        html += '<td class="px-4 py-3"><span class="text-xs font-mono text-gray-400">Stop ' + stop.stop_order + '</span></td>';
        html += '<td class="px-4 py-3">';
        html += '<span class="font-medium">' + stop.city_name + '</span>';
        if (!stop.in_coverage) {
            html += ' <span class="text-[10px] text-red-500 font-mono">⚠️ Di luar coverage</span>';
        }
        html += '</td>';
        html += '<td class="px-4 py-3 text-center"><input type="checkbox" ' + (stop.is_pickup_available ? 'checked' : '') + ' ' + (stop.is_pickup_fixed ? 'disabled' : '') + ' onchange="toggleStop(' + stop.id + ', \'pickup\', this.checked)" class="w-5 h-5 text-[#C1121F] rounded"></td>';
        html += '<td class="px-4 py-3 text-center"><input type="checkbox" ' + (stop.is_dropoff_available ? 'checked' : '') + ' ' + (stop.is_dropoff_fixed ? 'disabled' : '') + ' onchange="toggleStop(' + stop.id + ', \'dropoff\', this.checked)" class="w-5 h-5 text-[#C1121F] rounded"></td>';
        html += '</tr>';
    });
    stopsTableBody.innerHTML = html;
}

function toggleStop(stopId, type, enabled) {
    var stop = stops.find(function(s) { return s.id === stopId; });
    if (!stop) return;
    
    if (type === 'pickup') stop.is_pickup_available = enabled;
    else stop.is_dropoff_available = enabled;
    
    if (!enabled) {
        if (type === 'pickup') pricingList = pricingList.filter(function(p) { return p.origin_stop_id !== stopId; });
        else pricingList = pricingList.filter(function(p) { return p.destination_stop_id !== stopId; });
        updatePricingSummary();
    } else {
        var newPairs = findNewPairs(stop, type);
        if (newPairs.length > 0) {
            tempModalPairs = newPairs;
            showModal(newPairs);
        }
    }
}

function findNewPairs(changedStop, type) {
    var pairs = [];
    var basePrice = parseInt(basePriceInput.value) || 150000;
    
    if (type === 'pickup') {
        var dropoffStops = stops.filter(function(s) { return s.is_dropoff_available && s.stop_order > changedStop.stop_order; });
        dropoffStops.forEach(function(ds) {
            var exists = pricingList.find(function(p) { return p.origin_stop_id === changedStop.id && p.destination_stop_id === ds.id; });
            if (!exists) {
                pairs.push({ origin_stop_id: changedStop.id, origin_city: changedStop.city_name, destination_stop_id: ds.id, destination_city: ds.city_name, price: basePrice });
            }
        });
    } else if (type === 'dropoff') {
        var pickupStops = stops.filter(function(s) { return s.is_pickup_available && s.stop_order < changedStop.stop_order; });
        pickupStops.forEach(function(ps) {
            var exists = pricingList.find(function(p) { return p.origin_stop_id === ps.id && p.destination_stop_id === changedStop.id; });
            if (!exists) {
                pairs.push({ origin_stop_id: ps.id, origin_city: ps.city_name, destination_stop_id: changedStop.id, destination_city: changedStop.city_name, price: basePrice });
            }
        });
    }
    return pairs;
}

function showModal(pairs) {
    modalInfo.textContent = 'Isi harga untuk ' + pairs.length + ' kombinasi baru:';
    var html = '';
    pairs.forEach(function(pair, idx) {
        html += '<div class="mb-3 bg-[#F5F5F5] rounded-xl p-3">';
        html += '<p class="text-sm font-medium mb-1">' + pair.origin_city + ' → ' + pair.destination_city + '</p>';
        html += '<input type="number" id="modalPrice' + idx + '" value="' + (pair.price || '') + '" placeholder="Harga (Rp)" class="w-full px-3 py-2 border rounded-lg text-sm bg-white" min="1000">';
        html += '</div>';
    });
    modalPairsDiv.innerHTML = html;
    pricingModal.style.display = 'flex';
}

function closeModal() { pricingModal.style.display = 'none'; tempModalPairs = []; }

function saveModalPricing() {
    var validPairs = [];
    tempModalPairs.forEach(function(pair, idx) {
        var priceInput = document.getElementById('modalPrice' + idx);
        if (priceInput && priceInput.value && parseInt(priceInput.value) > 0) {
            validPairs.push({ origin_stop_id: pair.origin_stop_id, origin_city: pair.origin_city, destination_stop_id: pair.destination_stop_id, destination_city: pair.destination_city, price: parseInt(priceInput.value) });
        }
    });
    if (validPairs.length > 0) pricingList = pricingList.concat(validPairs);
    closeModal();
    updatePricingSummary();
}

function updatePricingSummary() {
    if (pricingList.length === 0) { pricingSummary.style.display = 'none'; return; }
    pricingSummary.style.display = 'block';
    var html = '';
    pricingList.forEach(function(p) {
        html += '<div class="bg-green-50 border border-green-200 rounded-lg px-3 py-2 text-sm flex justify-between items-center">';
        html += '<span>' + p.origin_city + ' → ' + p.destination_city + '</span>';
        html += '<span class="font-bold text-green-700">Rp ' + formatRupiah(p.price) + '</span>';
        html += '<button type="button" onclick="removePricing(' + p.origin_stop_id + ', ' + p.destination_stop_id + ')" class="text-red-500 text-xs hover:underline ml-2">✕</button>';
        html += '</div>';
    });
    pricingListDiv.innerHTML = html;
}

function removePricing(originId, destId) {
    pricingList = pricingList.filter(function(p) { return !(p.origin_stop_id === originId && p.destination_stop_id === destId); });
    updatePricingSummary();
}

function submitForm() {
    var pickupStops = stops.filter(function(s) { return s.is_pickup_available; });
    var dropoffStops = stops.filter(function(s) { return s.is_dropoff_available; });
    var missingPairs = [];
    
    pickupStops.forEach(function(ps) {
        dropoffStops.forEach(function(ds) {
            if (ds.stop_order > ps.stop_order) {
                var hasPrice = pricingList.find(function(p) { return p.origin_stop_id === ps.id && p.destination_stop_id === ds.id; });
                if (!hasPrice) missingPairs.push(ps.city_name + ' → ' + ds.city_name);
            }
        });
    });
    
    if (missingPairs.length > 0) {
        pricingWarning.textContent = 'Harga belum diisi untuk: ' + missingPairs.join(', ');
        pricingWarning.classList.remove('hidden');
        return;
    }
    
    pricingWarning.classList.add('hidden');
    
    var stopConfig = stops.map(function(s) { return { route_stop_id: s.id, is_pickup_available: s.is_pickup_available, is_dropoff_available: s.is_dropoff_available }; });
    var pricingData = pricingList.map(function(p) { return { origin_stop_id: p.origin_stop_id, destination_stop_id: p.destination_stop_id, price: p.price }; });
    
    stopConfigInput.value = JSON.stringify(stopConfig);
    pricingInput.value = JSON.stringify(pricingData);
    
    document.getElementById('scheduleForm').submit();
}

function formatRupiah(num) { return new Intl.NumberFormat('id-ID').format(num || 0); }

pricingModal.addEventListener('click', function(e) { if (e.target === pricingModal) closeModal(); });

@if(old('route_id'))
document.addEventListener('DOMContentLoaded', function() {
    routeSelect.value = '{{ old('route_id') }}';
    routeSelect.dispatchEvent(new Event('change'));
});
@endif
</script>
@endpush
@endsection