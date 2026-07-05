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
        'agency_logo' => $schedule->agency->logo ?  $schedule->agency->logo : null,
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
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-primary-50 flex items-center justify-center text-xl overflow-hidden flex-shrink-0">
                @if($schedule->agency && $schedule->agency->logo)
                <img src="{{  $schedule->agency->logo }}" alt="Logo" class="w-full h-full object-cover">
                @else
                <span>🏢</span>
                @endif
            </div>
            <div class="min-w-0">
                <h2 class="font-bold text-lg truncate">{{ $schedule->agency->agency_name ?? 'Agency' }}</h2>
                <p class="text-sm text-gray-500">
                    ⭐ {{ number_format((float) ($schedule->agency->rating ?? 0), 1) }} 
                    | 🚐 {{ $schedule->vehicle->plate_number ?? '-' }}
                </p>
                <p class="text-sm text-gray-500">
                    📅 {{ $schedule->departure_date ? $schedule->departure_date->format('d M Y') : '-' }} 
                    | 🕐 {{ $schedule->departure_time ?? '-' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
        <div class="flex justify-between text-xs md:text-sm mb-2">
            <span class="step-indicator font-bold text-primary-600" data-step="1">1. Pilih Naik</span>
            <span class="step-indicator text-gray-400 hidden sm:inline" data-step="2">2. Alamat Jemput</span>
            <span class="step-indicator text-gray-400 hidden sm:inline" data-step="3">3. Pilih Turun</span>
            <span class="step-indicator text-gray-400" data-step="4">4. Konfirmasi</span>
        </div>
        <div class="bg-gray-200 rounded-full h-2">
            <div id="progressBar" class="bg-primary-600 rounded-full h-2 transition-all duration-300" style="width: 25%"></div>
        </div>
    </div>

    {{-- STEP 1: Pilih Kota Penjemputan --}}
    <div id="step1" class="step-content">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-xl font-bold text-secondary mb-2">Pilih Kota Penjemputan</h2>
            <p class="text-gray-500 mb-6">Pilih kota di mana Anda akan dijemput</p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="pickupGrid"></div>
            <div class="mt-6 text-center">
                <button type="button" onclick="goToStep2()" id="btnStep1" 
                        class="bg-gray-300 text-gray-500 px-8 py-3 rounded-xl font-semibold cursor-not-allowed" disabled>Lanjut</button>
            </div>
        </div>
    </div>

    {{-- STEP 2: Alamat Penjemputan --}}
    {{-- STEP 2: Alamat Penjemputan --}}
    <div id="step2" class="step-content" style="display:none;">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-xl font-bold text-secondary mb-2">Alamat Penjemputan</h2>
            <p class="text-gray-500 mb-6">Isi alamat lengkap penjemputan di <strong id="pickupCityName">-</strong></p>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">Alamat Lengkap <span class="text-red-500">*</span></label>
                    <textarea id="pickupAddress" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" 
                            placeholder="Jl. Trunojoyo No. 10, RT/RW, Kelurahan, Kecamatan, Kabupaten"></textarea>
                    <button type="button" onclick="getCurrentLocation('pickup')"
                            class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Gunakan Lokasi Saat Ini
                    </button>
                    <p id="pickupLocationStatus" class="text-xs mt-1 hidden"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">Link Google Maps</label>
                    <input type="url" id="pickupMapsLink" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" 
                        placeholder="https://maps.google.com/?q=..." readonly>
                    <p class="text-xs text-gray-400 mt-1">Terisi otomatis saat menggunakan lokasi saat ini</p>
                </div>
            </div>
            <div class="mt-6 flex gap-4 justify-center">
                <button type="button" onclick="goToStep1()" class="border border-gray-300 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-50 transition">Kembali</button>
                <button type="button" onclick="goToStep3()" id="btnStep2" class="bg-primary-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-primary-700 transition">Lanjut</button>
            </div>
        </div>
    </div>

    {{-- STEP 3: Pilih Kota Tujuan --}}
    <div id="step3" class="step-content" style="display:none;">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-xl font-bold text-secondary mb-2">Pilih Kota Tujuan</h2>
            <p class="text-gray-500 mb-6">Pilih kota tujuan Anda (setelah <strong id="pickupCityName3">-</strong>)</p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4" id="dropoffGrid"></div>
            <div id="priceDetail" class="mt-6 bg-primary-50 rounded-2xl p-4" style="display:none;">
                <h4 class="font-bold text-primary-600 mb-2">Harga per Orang</h4>
                <div class="flex justify-between text-sm mb-1">
                    <span id="priceRoute">-</span>
                    <span class="font-bold text-primary-600" id="pricePerPerson">-</span>
                </div>
            </div>
            <div class="mt-6 flex gap-4 justify-center">
                <button type="button" onclick="goToStep2()" class="border border-gray-300 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-50 transition">Kembali</button>
                <button type="button" onclick="goToStep4()" id="btnStep3" 
                        class="bg-gray-300 text-gray-500 px-8 py-3 rounded-xl font-semibold cursor-not-allowed" disabled>Lanjut</button>
            </div>
        </div>
    </div>

    {{-- STEP 4: Data Penumpang & Konfirmasi --}}
    <div id="step4" class="step-content" style="display:none;">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
            <h2 class="text-xl font-bold text-secondary mb-2">Data Penumpang</h2>
            <p class="text-gray-500 mb-6">Isi data penumpang untuk perjalanan ini</p>
            
            {{-- Alamat Tujuan --}}
           {{-- Alamat Tujuan --}}
            <div class="mb-6">
                <h3 class="font-semibold text-secondary mb-2">Alamat Tujuan di <strong id="dropoffCityName4">-</strong></h3>
                <textarea id="destinationAddress" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50 mb-2" 
                        placeholder="Alamat lengkap tujuan"></textarea>
                <button type="button" onclick="getCurrentLocation('destination')"
                        class="mb-2 text-sm text-red-600 hover:text-red-800 font-medium flex items-center gap-1 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Gunakan Lokasi Saat Ini
                </button>
                <p id="destinationLocationStatus" class="text-xs mt-1 hidden"></p>
                <input type="url" id="destinationMapsLink" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" 
                    placeholder="Link Google Maps tujuan (opsional)" readonly>
                <p class="text-xs text-gray-400 mt-1">Terisi otomatis saat menggunakan lokasi saat ini</p>
            </div>

            {{-- Data Penumpang --}}
            <div class="mb-6">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="font-semibold text-secondary">Penumpang</h3>
                    <button type="button" onclick="useAccountOwner()" id="btnUseOwner" 
                            class="text-primary-600 text-sm hover:underline font-medium">
                        👤 Gunakan Pemilik Akun
                    </button>
                </div>
                <div id="passengerList" class="space-y-3"></div>
                <button type="button" onclick="addPassenger()" class="mt-3 text-primary-600 text-sm hover:underline font-medium">
                    + Tambah Penumpang Lain
                </button>
                <p class="text-xs text-gray-500 mt-1">Maksimal 10 penumpang</p>
            </div>

            {{-- Ringkasan Singkat --}}
            <div class="bg-gray-50 rounded-2xl p-4 mb-6">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">{{ $schedule->route->route_name }}</span>
                    <span class="font-semibold">{{ $schedule->departure_date?->format('d M Y') }} {{ $schedule->departure_time }}</span>
                </div>
                <div class="flex justify-between text-sm mt-1">
                    <span class="text-gray-600">Penumpang</span>
                    <span class="font-semibold" id="summaryPassengers">0 orang</span>
                </div>
            </div>

            <div class="flex gap-4 justify-center">
                <button type="button" onclick="goToStep3()" class="border border-gray-300 text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-50 transition">Kembali</button>
                <button type="button" onclick="submitBooking()" class="bg-green-600 text-white px-8 py-3 rounded-xl font-bold text-lg hover:bg-green-700 transition">
                    Buat Booking
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

@endsection

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
});

function renderPickupGrid() {
    var grid = document.getElementById('pickupGrid');
    if (!availablePickups || !availablePickups.length) {
        grid.innerHTML = '<p class="text-gray-500 col-span-full text-center py-4">Tidak ada kota penjemputan tersedia.</p>';
        return;
    }
    var html = '';
    availablePickups.forEach(function(stop, index) {
        var isFirst = index === 0;
        var bgClass = isFirst ? 'border-green-400 bg-green-50' : 'border-blue-400 bg-blue-50';
        var labelClass = isFirst ? 'bg-green-500' : 'bg-blue-500';
        var label = isFirst ? 'Kota Asal' : 'Stop ' + (stop.stop_order || index + 1);
        html += '<div class="pickup-card rounded-xl border-2 p-4 cursor-pointer transition hover:shadow-lg ' + bgClass + '" onclick="selectPickup(this, ' + stop.route_stop_id + ', \'' + stop.city_name.replace(/'/g, "\\'") + '\')">';
        html += '<span class="inline-block px-2 py-0.5 rounded-full text-xs text-white font-medium mb-2 ' + labelClass + '">' + label + '</span>';
        html += '<h3 class="font-bold text-lg">' + stop.city_name + '</h3>';
        html += '<p class="text-sm mt-1">Titik Jemput</p>';
        html += '<p class="text-sm font-semibold text-gray-700 mt-1">Mulai ' + (stop.min_price_formatted || 'Belum ada harga') + '</p>';
        html += '</div>';
    });
    grid.innerHTML = html;
}

function selectPickup(card, stopId, cityName) {
    document.querySelectorAll('.pickup-card').forEach(function(c) { c.classList.remove('ring-4', 'ring-primary-600', 'border-primary-600'); });
    card.classList.add('ring-4', 'ring-primary-600', 'border-primary-600');
    selectedPickup = { id: stopId, city_name: cityName };
    var btn = document.getElementById('btnStep1');
    btn.disabled = false;
    btn.className = 'bg-primary-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-primary-700 cursor-pointer transition';
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
        grid.innerHTML = '<p class="text-gray-500 col-span-full text-center py-4">Tidak ada kota tujuan tersedia.</p>';
        return;
    }
    var html = '';
    availableDropoffs.forEach(function(stop, index) {
        var isLast = index === availableDropoffs.length - 1;
        var bgClass = isLast ? 'border-red-400 bg-red-50' : 'border-purple-400 bg-purple-50';
        var labelClass = isLast ? 'bg-red-500' : 'bg-purple-500';
        var label = isLast ? 'Kota Tujuan' : 'Stop ' + (stop.stop_order || index + 1);
        html += '<div class="dropoff-card rounded-xl border-2 p-4 cursor-pointer transition hover:shadow-lg ' + bgClass + '" onclick="selectDropoff(this, ' + stop.route_stop_id + ', \'' + stop.city_name.replace(/'/g, "\\'") + '\', ' + (stop.price || 0) + ')">';
        html += '<span class="inline-block px-2 py-0.5 rounded-full text-xs text-white font-medium mb-2 ' + labelClass + '">' + label + '</span>';
        html += '<h3 class="font-bold text-lg">' + stop.city_name + '</h3>';
        html += '<p class="text-sm mt-1">Titik Turun</p>';
        html += '<p class="text-sm font-semibold text-gray-700 mt-1">' + (stop.price_formatted || 'Belum ada harga') + '</p>';
        html += '</div>';
    });
    grid.innerHTML = html;
}

function selectDropoff(card, stopId, cityName, price) {
    document.querySelectorAll('.dropoff-card').forEach(function(c) { c.classList.remove('ring-4', 'ring-primary-600', 'border-primary-600'); });
    card.classList.add('ring-4', 'ring-primary-600', 'border-primary-600');
    selectedDropoff = { id: stopId, city_name: cityName, price: price };
    document.getElementById('priceDetail').style.display = 'block';
    document.getElementById('priceRoute').textContent = (selectedPickup ? selectedPickup.city_name : '') + ' → ' + cityName;
    document.getElementById('pricePerPerson').textContent = 'Rp ' + formatRupiah(price) + ' /orang';
    var btn = document.getElementById('btnStep3');
    btn.disabled = false;
    btn.className = 'bg-primary-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-primary-700 cursor-pointer transition';
}

function goToStep4() {
    if (!selectedDropoff) return alert('Pilih kota tujuan!');
    document.getElementById('dropoffCityName4').textContent = selectedDropoff.city_name;
    updateSummary();
    showStep(4);
}

function addPassenger() {
    var list = document.getElementById('passengerList');
    if (list.querySelectorAll('.passenger-item').length >= 10) return alert('Maksimal 10 penumpang!');
    var div = document.createElement('div');
    div.className = 'passenger-item bg-gray-50 rounded-xl p-4';
    div.innerHTML = 
        '<div class="flex justify-between items-start mb-2">' +
            '<span class="text-xs font-medium text-gray-500">Penumpang ' + (list.querySelectorAll('.passenger-item').length + 1) + '</span>' +
            '<button type="button" onclick="this.closest(\'.passenger-item\').remove(); updateSummary();" class="text-red-500 text-xs hover:underline">Hapus</button>' +
        '</div>' +
        '<div class="grid grid-cols-3 gap-3">' +
            '<input type="text" class="passenger-name px-3 py-2 border border-gray-200 rounded-lg bg-white" placeholder="Nama lengkap" required>' +
            '<input type="text" class="passenger-phone px-3 py-2 border border-gray-200 rounded-lg bg-white" placeholder="No. HP">' +
            '<input type="number" class="passenger-baggage px-3 py-2 border border-gray-200 rounded-lg bg-white" placeholder="Bagasi (kg)" value="0" min="0">' +
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
        div.className = 'passenger-item bg-blue-50 rounded-xl p-4 border border-blue-200';
        div.innerHTML = 
            '<div class="flex justify-between items-start mb-2">' +
                '<span class="text-xs font-medium text-blue-600">👤 Pemilik Akun</span>' +
                '<button type="button" onclick="this.closest(\'.passenger-item\').remove(); hasUsedOwner = false; updateSummary();" class="text-red-500 text-xs hover:underline">Hapus</button>' +
            '</div>' +
            '<div class="grid grid-cols-3 gap-3">' +
                '<input type="text" class="passenger-name px-3 py-2 border border-gray-200 rounded-lg bg-blue-50" value="' + userData.name + '" required>' +
                '<input type="text" class="passenger-phone px-3 py-2 border border-gray-200 rounded-lg bg-blue-50" value="' + (userData.phone || '') + '">' +
                '<input type="number" class="passenger-baggage px-3 py-2 border border-gray-200 rounded-lg bg-blue-50" placeholder="Bagasi (kg)" value="0" min="0">' +
            '</div>';
        list.appendChild(div);
    }
    hasUsedOwner = true;
    document.getElementById('btnUseOwner').textContent = '✅ Pemilik Akun Ditambahkan';
    document.getElementById('btnUseOwner').classList.add('text-green-600');
    updateSummary();
}

function updateSummary() {
    document.getElementById('summaryPassengers').textContent = document.querySelectorAll('.passenger-item').length + ' orang';
}

function submitBooking() {
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
    document.getElementById('bookingForm').submit();
}

function showStep(step) {
    document.querySelectorAll('.step-content').forEach(function(el) { el.style.display = 'none'; });
    document.getElementById('step' + step).style.display = 'block';
    document.getElementById('progressBar').style.width = (step * 25) + '%';
    document.querySelectorAll('.step-indicator').forEach(function(el) {
        var elStep = parseInt(el.getAttribute('data-step'));
        el.classList.remove('text-primary-600', 'text-gray-400', 'text-green-600', 'font-bold');
        if (elStep < step) el.classList.add('text-green-600');
        else if (elStep === step) el.classList.add('text-primary-600', 'font-bold');
        else el.classList.add('text-gray-400');
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function formatRupiah(num) { return new Intl.NumberFormat('id-ID').format(num || 0); }
function escapeHtml(text) { if (!text) return ''; return text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }
// ═══════════════════════════════════════════════════════
// GEOLOCATION — Gunakan Lokasi Saat Ini
// ═══════════════════════════════════════════════════════

/**
 * Ambil lokasi GPS saat ini via browser Geolocation API
 * @param {string} type - 'pickup' atau 'destination'
 */
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
                case error.PERMISSION_DENIED:
                    msg = 'Akses lokasi ditolak. Izinkan di pengaturan browser.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    msg = 'Lokasi tidak tersedia.';
                    break;
                case error.TIMEOUT:
                    msg = 'Timeout mengambil lokasi.';
                    break;
                default:
                    msg = 'Gagal mengambil lokasi.';
            }

            if (statusEl) {
                statusEl.textContent = '❌ ' + msg;
                statusEl.classList.remove('hidden', 'text-blue-600', 'text-green-600', 'text-yellow-600');
                statusEl.classList.add('text-red-600');
            }
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000,
        }
    );
}

/**
 * Reverse geocode — ubah koordinat jadi alamat
 * Pakai OpenStreetMap Nominatim (GRATIS, no API key)
 */
function reverseGeocode(lat, lng, type) {
    var statusEl = document.getElementById(type + 'LocationStatus');

    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&accept-language=id')
        .then(function(res) { return res.json(); })
        .then(function(data) {
            var address = data.display_name || '';

            if (type === 'pickup') {
                // Isi alamat
                var addressField = document.getElementById('pickupAddress');
                if (addressField) addressField.value = address;

                // Isi link Google Maps
                var mapsField = document.getElementById('pickupMapsLink');
                if (mapsField) mapsField.value = 'https://www.google.com/maps?q=' + lat + ',' + lng;

                // ⭐ BARU: Simpan koordinat untuk dikirim ke backend
                // Kita tambahkan hidden input di form
                updateOrCreateHiddenInput('pickup_latitude', lat);
                updateOrCreateHiddenInput('pickup_longitude', lng);
            } else {
                var addressField = document.getElementById('destinationAddress');
                if (addressField) addressField.value = address;

                var mapsField = document.getElementById('destinationMapsLink');
                if (mapsField) mapsField.value = 'https://www.google.com/maps?q=' + lat + ',' + lng;

                // ⭐ BARU: Simpan koordinat untuk dikirim ke backend
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
            // Fallback tetap simpan koordinat
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

/**
 * Update atau buat hidden input untuk koordinat
 */
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