@extends('layouts.public')

@section('title', 'Cari Rental Mobil')
@section('meta_description', 'Sewa mobil di GoMad Rental. Tersedia lepas kunci atau dengan supir. Booking mudah, harga transparan.')
@section('og_image', asset('images/og-rental.jpg'))

@section('content')
@php
    $query = \App\Models\VehicleRentalSetting::with(['vehicle.agency'])
        ->where('is_available_for_rental', true)
        ->whereHas('vehicle', fn($q) => $q->where('is_active', true))
        ->whereHas('vehicle.agency', fn($q) => $q->where('is_verified', true));

    if (request('type')) {
        match (request('type')) {
            'self_drive' => $query->where('allow_self_drive', true),
            'with_driver' => $query->where('allow_with_driver', true),
            default => null,
        };
    }

    if (request('city_code')) {
        $query->whereHas('vehicle.agency', fn($q) => $q->where('city_code', request('city_code')));
    }

    if (request('agency_id')) {
        $query->whereHas('vehicle', fn($q) => $q->where('agency_id', request('agency_id')));
    }

    if (request('date')) {
        $date = request('date');
        $query->whereDoesntHave('vehicle.rentals', function ($q) use ($date) {
            $q->whereNotIn('status', ['cancelled'])
              ->whereDate('start_datetime', '<=', $date)
              ->whereDate('end_datetime', '>=', $date);
        });
    }

    $vehicles = $query->orderBy('created_at', 'desc')->paginate(12);
    $agencies = \App\Models\Agency::where('is_verified', true)->orderBy('agency_name')->get();
    $cities = \App\Models\City::with('province')->orderBy('name')->get();
@endphp

<div class="section">
    <div class="container-magazine">
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-[#111827] mb-2">Cari Rental Mobil</h1>
            <p class="text-gray-500 font-light">Sewa mobil untuk perjalanan Anda. Tersedia lepas kunci atau dengan supir.</p>
        </div>

        {{-- Filter --}}
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-5 mb-8 shadow-gomad">
            <form action="{{ route('rental.public') }}" method="GET" class="grid grid-cols-2 md:grid-cols-5 gap-4 items-end">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Tipe</label>
                    <select name="type" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] text-sm">
                        <option value="">Semua Tipe</option>
                        <option value="self_drive" {{ request('type') == 'self_drive' ? 'selected' : '' }}>🚗 Lepas Kunci</option>
                        <option value="with_driver" {{ request('type') == 'with_driver' ? 'selected' : '' }}>👨‍✈️ Dengan Supir</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Lokasi</label>
                    <select name="city_code" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] text-sm">
                        <option value="">Semua Kota</option>
                        @foreach($cities as $city)
                        <option value="{{ $city->code }}" {{ request('city_code') == $city->code ? 'selected' : '' }}>
                            {{ $city->name }}, {{ $city->province->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Tanggal</label>
                    <input type="date" name="date" value="{{ request('date') }}" 
                           class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] text-sm">
                </div>
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Agency</label>
                    <select name="agency_id" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] text-sm">
                        <option value="">Semua Agency</option>
                        @foreach($agencies as $agency)
                        <option value="{{ $agency->id }}" {{ request('agency_id') == $agency->id ? 'selected' : '' }}>{{ $agency->agency_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 btn-gomad-primary py-2.5 text-sm rounded-[10px]">Cari</button>
                    @if(request()->anyFilled(['type', 'date', 'agency_id', 'city_code']))
                    <a href="{{ route('rental.public') }}" class="flex-1 border border-[#E5E7EB] text-gray-600 py-2.5 text-sm rounded-[10px] text-center hover:bg-[#F9FAFB] transition">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Results --}}
        <div class="flex justify-between items-center mb-6 border-b border-[#E5E7EB] pb-4">
            <p class="text-sm text-gray-500 font-light">
                Menampilkan <strong class="text-[#111827]">{{ $vehicles->total() }}</strong> kendaraan
                @if(request('city_code'))
                <span class="text-gray-400">• {{ \App\Models\City::find(request('city_code'))?->name ?? '' }}</span>
                @endif
            </p>
        </div>

        @if($vehicles->isEmpty())
        <div class="card-gomad p-12 text-center border-[#E5E7EB]">
            <div class="w-16 h-16 bg-[#F9FAFB] rounded-[10px] flex items-center justify-center mx-auto mb-4 border border-[#E5E7EB]">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <p class="text-gray-500 text-lg font-light">Tidak ada kendaraan ditemukan.</p>
            <a href="{{ route('rental.public') }}" class="inline-block mt-4 text-[#BA1826] hover:underline font-medium">Reset Filter</a>
        </div>
        @else
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($vehicles as $setting)
            @php
                $vehicle = $setting->vehicle;
                $agency = $vehicle->agency;
                $rentalService = app(\App\Services\RentalService::class);
                $bookedDates = $rentalService->getBookedDates($vehicle->id);
                $bookedDatesJson = json_encode($bookedDates);
            @endphp
            <div class="bg-white border border-[#E5E7EB] rounded-[12px] overflow-hidden shadow-gomad hover:border-[#BA1826] transition-colors group">
                <div class="h-48 bg-[#F9FAFB] flex items-center justify-center overflow-hidden border-b border-[#E5E7EB] relative">
                    @if($vehicle->vehicle_image)
                    <img src="{{ $vehicle->vehicle_image }}" alt="{{ $vehicle->plate_number }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    @else
                    <span class="text-6xl text-gray-300">🚗</span>
                    @endif
                    
                    <div class="absolute top-3 left-3 flex gap-1">
                        @if($setting->allow_self_drive)
                        <span class="px-2 py-0.5 bg-blue-500 text-white text-[10px] font-mono uppercase tracking-wider rounded-full">Lepas Kunci</span>
                        @endif
                        @if($setting->allow_with_driver)
                        <span class="px-2 py-0.5 bg-green-500 text-white text-[10px] font-mono uppercase tracking-wider rounded-full">+Supir</span>
                        @endif
                    </div>
                </div>
                
                <div class="p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-full bg-[#F9FAFB] flex items-center justify-center overflow-hidden flex-shrink-0 border border-[#E5E7EB]">
                            @if($agency->logo)
                            <img src="{{ $agency->logo }}" alt="{{ $agency->agency_name }}" class="w-full h-full object-cover">
                            @else
                            <span class="text-lg">🏢</span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-sm text-[#111827] truncate">{{ $agency->agency_name }}</p>
                            <div class="flex items-center text-xs font-mono tracking-wider">
                                <span class="text-gray-500">⭐ {{ number_format($agency->rating, 1) }}</span>
                                @if($agency->is_verified)
                                <span class="text-[#BA1826] ml-2">✓ Terverifikasi</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <p class="font-bold text-[#111827]">{{ $vehicle->brand }} {{ $vehicle->model }} ({{ $vehicle->year }})</p>
                    <p class="text-xs text-gray-500 font-mono">{{ $vehicle->plate_number }}</p>
                    
                    @if($setting->specifications)
                    <div class="flex flex-wrap gap-1 mt-2">
                        @php $specs = is_array($setting->specifications) ? $setting->specifications : json_decode($setting->specifications, true) ?? []; @endphp
                        @foreach($specs as $key => $value)
                            @if($value && !is_array($value))
                            <span class="px-1.5 py-0.5 bg-[#F9FAFB] text-[10px] font-mono uppercase tracking-wider rounded text-gray-500">
                                {{ is_bool($value) ? str_replace('_', ' ', $key) : str_replace('_', ' ', $key) . ': ' . $value }}
                            </span>
                            @endif
                        @endforeach
                    </div>
                    @endif

                    <div class="mt-3 bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 font-light">Harga mulai</span>
                            <span class="font-bold text-[#BA1826] font-mono">
                                @if($setting->price_per_day)
                                    Rp {{ number_format($setting->price_per_day, 0, ',', '.') }}/hari
                                @elseif($setting->price_per_hour)
                                    Rp {{ number_format($setting->price_per_hour, 0, ',', '.') }}/jam
                                @else
                                    Hubungi agency
                                @endif
                            </span>
                        </div>
                        @if($setting->allow_with_driver && $setting->driver_fee_per_day > 0)
                        <div class="flex justify-between text-xs mt-1">
                            <span class="text-gray-400 font-light">+ Supir</span>
                            <span class="text-orange-600 font-mono">Rp {{ number_format($setting->driver_fee_per_day, 0, ',', '.') }}/hari</span>
                        </div>
                        @endif
                    </div>

                    {{-- Mini Kalender --}}
                    <div class="mt-3">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Ketersediaan 30 Hari</p>
                            <button type="button" 
                                    onclick="openCalendarModal('{{ $vehicle->id }}')"
                                    class="text-[10px] text-[#BA1826] hover:underline font-medium">
                                📅 Cek Tanggal
                            </button>
                        </div>
                        <div class="mini-calendar-{{ $vehicle->id }} flex flex-wrap gap-0.5" 
                             data-booked='{{ $bookedDatesJson }}'>
                            <span class="text-[10px] text-gray-400">⏳ Memuat...</span>
                        </div>
                    </div>

                    <div class="mt-4 border-t border-[#E5E7EB] pt-4">
                        @auth
                        <a href="{{ route('customer.rental.create', $setting) }}" 
                           class="block w-full text-center bg-[#BA1826] text-white py-2.5 rounded-[10px] font-semibold hover:bg-[#8A0F18] transition text-sm">
                            Sewa Sekarang
                        </a>
                        @else
                        <a href="{{ route('login') }}" 
                           class="block w-full text-center border border-[#BA1826] text-[#BA1826] py-2.5 rounded-[10px] font-semibold hover:bg-[#BA1826] hover:text-white transition text-sm">
                            Login untuk Sewa
                        </a>
                        @endauth
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $vehicles->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Calendar Modal --}}
<div id="calendarModal" class="fixed inset-0 bg-[#111827]/50 z-50 hidden items-center justify-center p-4" style="display:none;">
    <div class="bg-white rounded-[12px] shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto border border-[#E5E7EB]">
        {{-- Header --}}
        <div class="sticky top-0 bg-white border-b border-[#E5E7EB] p-5 rounded-t-[12px] z-10 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-lg text-[#111827]" id="modalVehicleName">Kalender Ketersediaan</h3>
                <p class="text-xs text-gray-500 font-light" id="modalVehiclePlate"></p>
            </div>
            <button onclick="closeCalendarModal()" class="w-8 h-8 flex items-center justify-center border border-[#E5E7EB] rounded-lg hover:bg-[#F9FAFB] transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        
        {{-- Calendar Container --}}
        <div class="p-5">
            {{-- Navigation --}}
            <div class="flex items-center justify-between mb-4">
                <button onclick="modalPrevMonth()" class="w-8 h-8 flex items-center justify-center border border-[#E5E7EB] rounded-lg hover:bg-[#F9FAFB] text-sm">&larr;</button>
                <span class="font-bold text-[#111827]" id="modalMonthLabel">-</span>
                <button onclick="modalNextMonth()" class="w-8 h-8 flex items-center justify-center border border-[#E5E7EB] rounded-lg hover:bg-[#F9FAFB] text-sm">&rarr;</button>
            </div>
            
            {{-- Day names --}}
            <div class="grid grid-cols-7 gap-1 text-center mb-1">
                <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 py-1">Min</div>
                <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 py-1">Sen</div>
                <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 py-1">Sel</div>
                <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 py-1">Rab</div>
                <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 py-1">Kam</div>
                <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 py-1">Jum</div>
                <div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 py-1">Sab</div>
            </div>
            
            {{-- Days Grid --}}
            <div class="grid grid-cols-7 gap-1 text-center" id="modalCalendarGrid">
                <div class="col-span-7 text-center py-4 text-gray-400">⏳ Memuat...</div>
            </div>
            
            {{-- Legend --}}
            <div class="flex items-center gap-4 mt-4 text-xs border-t border-[#E5E7EB] pt-4">
                <div class="flex items-center gap-1">
                    <div class="w-4 h-4 bg-green-100 border border-green-300 rounded"></div>
                    <span class="text-gray-500 font-light">Tersedia</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-4 h-4 bg-red-100 border border-red-300 rounded"></div>
                    <span class="text-gray-500 font-light">Dibooking</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-4 h-4 bg-yellow-100 border border-yellow-300 rounded"></div>
                    <span class="text-gray-500 font-light">Hari Ini</span>
                </div>
            </div>
        </div>
        
        {{-- Footer --}}
        <div class="sticky bottom-0 bg-white border-t border-[#E5E7EB] p-4 rounded-b-[12px] flex gap-3">
            <button onclick="closeCalendarModal()" class="flex-1 border border-[#E5E7EB] py-2.5 rounded-[10px] font-medium hover:bg-[#F9FAFB] transition text-sm">
                Tutup
            </button>
            <a href="#" id="modalBookNow" class="flex-1 bg-[#BA1826] text-white py-2.5 rounded-[10px] font-semibold hover:bg-[#8A0F18] transition text-sm text-center">
                Sewa Sekarang
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Mini calendar
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[class*="mini-calendar-"]').forEach(function(container) {
        var bookedData = {};
        try { bookedData = JSON.parse(container.getAttribute('data-booked') || '{}'); } catch(e) {}
        var today = new Date(); today.setHours(0,0,0,0);
        var html = '';
        for (var i = 0; i < 30; i++) {
            var date = new Date(today); date.setDate(date.getDate() + i);
            var dateStr = date.getFullYear() + '-' + String(date.getMonth()+1).padStart(2,'0') + '-' + String(date.getDate()).padStart(2,'0');
            var isBooked = bookedData[dateStr] !== undefined, isToday = i === 0;
            var bgClass = 'bg-green-100 border border-green-300';
            if (isBooked) bgClass = 'bg-red-200 border border-red-400';
            if (isToday) bgClass = 'bg-yellow-200 border border-yellow-400';
            html += '<div class="w-3.5 h-3.5 rounded-sm ' + bgClass + '" title="' + dateStr + (isBooked ? ' - Dibooking' : ' - Tersedia') + '"></div>';
        }
        container.innerHTML = html;
    });
});

// ═══════════════════════════════════════
// MODAL CALENDAR (Fix Missing Logic)
// ═══════════════════════════════════════
var modalVehicleId = null, modalBookedData = {}, modalCurrentMonth = new Date().getMonth(), modalCurrentYear = new Date().getFullYear();
var allVehicleData = {};

document.querySelectorAll('[class*="mini-calendar-"]').forEach(function(el) {
    var vehicleId = el.className.match(/mini-calendar-(\d+)/)[1];
    var card = el.closest('.bg-white');
    if (card) {
        var nameEl = card.querySelector('.font-bold');
        var plateEl = card.querySelector('.text-xs.text-gray-500.font-mono');
        allVehicleData[vehicleId] = { 
            name: nameEl ? nameEl.textContent.trim() : 'Kendaraan', 
            plate: plateEl ? plateEl.textContent.trim() : '', 
            booked: JSON.parse(el.getAttribute('data-booked') || '{}') 
        };
    }
});

function openCalendarModal(vehicleId) {
    modalVehicleId = vehicleId;
    var data = allVehicleData[vehicleId] || { name: 'Kendaraan', plate: '', booked: {} };
    modalBookedData = data.booked || {};
    
    var nameEl = document.getElementById('modalVehicleName');
    var plateEl = document.getElementById('modalVehiclePlate');
    if (nameEl) nameEl.textContent = data.name;
    if (plateEl) plateEl.textContent = data.plate;
    
    var bookLink = document.getElementById('modalBookNow');
    if (bookLink) bookLink.href = '/customer/rentals/create/' + vehicleId;
    
    modalCurrentMonth = new Date().getMonth(); 
    modalCurrentYear = new Date().getFullYear();
    renderModalCalendar();
    
    var modal = document.getElementById('calendarModal');
    if (modal) { modal.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}

function closeCalendarModal() { 
    var modal = document.getElementById('calendarModal');
    if (modal) { modal.style.display = 'none'; document.body.style.overflow = ''; }
}

function modalPrevMonth() { 
    if (modalCurrentMonth === 0) { modalCurrentMonth = 11; modalCurrentYear--; } 
    else { modalCurrentMonth--; } 
    renderModalCalendar(); 
}

function modalNextMonth() { 
    var today = new Date(); 
    var maxMonth = today.getMonth() + 6, maxYear = today.getFullYear(); 
    if (maxMonth > 11) { maxMonth -= 12; maxYear++; } 
    if (modalCurrentYear < maxYear || (modalCurrentYear === maxYear && modalCurrentMonth < maxMonth)) { 
        if (modalCurrentMonth === 11) { modalCurrentMonth = 0; modalCurrentYear++; } 
        else { modalCurrentMonth++; } 
    } 
    renderModalCalendar(); 
}

function renderModalCalendar() {
    var months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var daysInMonth = new Date(modalCurrentYear, modalCurrentMonth + 1, 0).getDate();
    var startDay = new Date(modalCurrentYear, modalCurrentMonth, 1).getDay();
    var today = new Date(); today.setHours(0,0,0,0);
    
    var labelEl = document.getElementById('modalMonthLabel');
    if (labelEl) labelEl.textContent = months[modalCurrentMonth] + ' ' + modalCurrentYear;
    
    var grid = document.getElementById('modalCalendarGrid');
    if (!grid) return;
    
    var html = '';
    for (var i = 0; i < startDay; i++) html += '<div class="py-2"></div>';
    
    for (var day = 1; day <= daysInMonth; day++) {
        var dateStr = modalCurrentYear + '-' + String(modalCurrentMonth+1).padStart(2,'0') + '-' + String(day).padStart(2,'0');
        var dateObj = new Date(modalCurrentYear, modalCurrentMonth, day);
        var isBooked = modalBookedData[dateStr] !== undefined;
        var isToday = dateObj.getTime() === today.getTime();
        var isPast = dateObj < today;
        
        var bgClass = 'bg-green-50 hover:bg-green-100 border border-green-200';
        if (isBooked) bgClass = 'bg-red-50 border border-red-200 cursor-not-allowed';
        if (isToday) bgClass = 'bg-yellow-100 border border-yellow-300 font-bold';
        if (isPast && !isToday) bgClass = 'bg-gray-100 text-gray-300 border border-gray-200 cursor-not-allowed';
        
        html += '<div class="py-2 rounded-lg text-sm ' + bgClass + '">' + day + (isBooked ? '<div class="text-[8px] text-red-500 leading-none">📌</div>' : '') + '</div>';
    }
    grid.innerHTML = html;
}

// Tutup modal dengan klik overlay
document.addEventListener('click', function(e) {
    if (e.target.id === 'calendarModal') closeCalendarModal();
});

// Tutup modal dengan ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeCalendarModal();
});
</script>
@endpush
@endsection