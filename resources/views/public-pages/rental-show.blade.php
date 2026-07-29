@extends('layouts.public')

@section('title', $vehicleSetting->vehicle->brand . ' ' . $vehicleSetting->vehicle->model . ' - Rental')
@section('meta_description', 'Sewa ' . $vehicleSetting->vehicle->brand . ' ' . $vehicleSetting->vehicle->model . ' di ' . $vehicleSetting->vehicle->agency->agency_name . '. Tersedia lepas kunci atau dengan supir.')
@section('og_image', $vehicleSetting->vehicle->vehicle_image ?? asset('images/og-rental.jpg'))

@section('content')
@php
    $vehicle = $vehicleSetting->vehicle;
    $agency = $vehicle->agency;
    $rentalService = app(\App\Services\RentalService::class);
    $bookedDates = $rentalService->getBookedDates($vehicle->id);
    $bookedDatesJson = json_encode($bookedDates);
    
    function parseSpecs($data) {
        if (is_array($data)) return $data;
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }
    $specs = parseSpecs($vehicleSetting->specifications ?? []);
    
    $customTerms = parseSpecs($vehicleSetting->terms_conditions ?? []);
    $customRefund = parseSpecs($vehicleSetting->refund_policy ?? []);
@endphp

<div class="section">
    <div class="container-magazine">
        <a href="{{ route('rental.public') }}" class="text-[#BA1826] text-sm mb-4 inline-block hover:underline">← Kembali ke Pencarian</a>

        <div class="grid lg:grid-cols-3 gap-8">
            {{-- Kolom Kiri: Detail Mobil --}}
            <div class="lg:col-span-2">
                {{-- Foto Mobil --}}
                <div class="bg-white border border-[#E5E7EB] rounded-[12px] overflow-hidden mb-6 shadow-gomad">
                    <div class="h-64 md:h-96 bg-[#F9FAFB] flex items-center justify-center overflow-hidden">
                        @if($vehicle->vehicle_image)
                        <img src="{{ $vehicle->vehicle_image }}" alt="{{ $vehicle->plate_number }}" class="w-full h-full object-cover">
                        @else
                        <span class="text-8xl text-gray-300">🚗</span>
                        @endif
                    </div>
                    <div class="p-6">
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            @if($vehicleSetting->allow_self_drive)
                            <span class="px-3 py-1 bg-blue-500 text-white text-xs font-mono uppercase tracking-wider rounded-full">🚗 Lepas Kunci</span>
                            @endif
                            @if($vehicleSetting->allow_with_driver)
                            <span class="px-3 py-1 bg-green-500 text-white text-xs font-mono uppercase tracking-wider rounded-full">👨‍✈️ Dengan Supir</span>
                            @endif
                        </div>
                        <h1 class="text-2xl md:text-3xl font-bold text-[#111827] mb-2">{{ $vehicle->brand }} {{ $vehicle->model }} ({{ $vehicle->year }})</h1>
                        <p class="text-gray-500 font-mono text-lg">{{ $vehicle->plate_number }}</p>
                        
                        {{-- Agency Info --}}
                        <div class="flex items-center gap-3 mt-4 p-4 bg-[#F9FAFB] rounded-[12px] border border-[#E5E7EB]">
                            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center overflow-hidden flex-shrink-0 border border-[#E5E7EB]">
                                @if($agency->logo)
                                <img src="{{ $agency->logo }}" alt="{{ $agency->agency_name }}" class="w-full h-full object-cover">
                                @else
                                <span class="text-xl">🏢</span>
                                @endif
                            </div>
                            <div>
                                <p class="font-bold text-[#111827]">{{ $agency->agency_name }}</p>
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="text-gray-500 font-mono">⭐ {{ number_format($agency->rating, 1) }}</span>
                                    @if($agency->is_verified)
                                    <span class="text-[#BA1826] font-mono text-xs">✓ Terverifikasi</span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-400 font-light">{{ $agency->address }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Spesifikasi --}}
                <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 mb-6 shadow-gomad">
                    <h2 class="font-bold text-lg text-[#111827] mb-4">Spesifikasi Kendaraan</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @if(!empty($specs))
                            @foreach($specs as $key => $value)
                                @if($value && !is_array($value))
                                <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3 text-center">
                                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">{{ str_replace('_', ' ', $key) }}</span>
                                    <p class="font-semibold text-[#111827] text-sm mt-1">{{ is_bool($value) ? ($value ? 'Ya' : 'Tidak') : $value }}</p>
                                </div>
                                @endif
                            @endforeach
                        @endif
                        <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3 text-center">
                            <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Kapasitas</span>
                            <p class="font-semibold text-[#111827] text-sm mt-1">{{ $vehicle->capacity }} Seat</p>
                        </div>
                        <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-3 text-center">
                            <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Tahun</span>
                            <p class="font-semibold text-[#111827] text-sm mt-1">{{ $vehicle->year }}</p>
                        </div>
                    </div>
                </div>

                {{-- Kalender Ketersediaan --}}
                <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 mb-6 shadow-gomad">
                    <h2 class="font-bold text-lg text-[#111827] mb-4">📅 Kalender Ketersediaan</h2>
                    <div id="availabilityCalendar" class="mb-4">
                        <div class="flex items-center justify-center py-8">
                            <span class="text-gray-400 font-light">⏳ Memuat kalender...</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-xs">
                        <div class="flex items-center gap-1"><div class="w-4 h-4 bg-white border border-[#E5E5E5] rounded"></div><span class="text-gray-500 font-light">Tersedia</span></div>
                        <div class="flex items-center gap-1"><div class="w-4 h-4 bg-red-100 border border-red-300 rounded"></div><span class="text-gray-500 font-light">Dibooking</span></div>
                        <div class="flex items-center gap-1"><div class="w-4 h-4 bg-yellow-100 border border-yellow-300 rounded"></div><span class="text-gray-500 font-light">Hari Ini</span></div>
                        <div class="flex items-center gap-1"><div class="w-4 h-4 bg-gray-100 text-gray-300 rounded flex items-center justify-center text-[10px]">-</div><span class="text-gray-500 font-light">Lewat</span></div>
                    </div>
                </div>

                {{-- Syarat & Ketentuan --}}
                @if($vehicleSetting->use_system_terms && !empty($customTerms))
                <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 mb-6 shadow-gomad">
                    <h2 class="font-bold text-lg text-[#111827] mb-4">📄 Syarat & Ketentuan</h2>
                    <ol class="list-decimal list-inside space-y-3 text-sm text-[#111827]">
                        @foreach($customTerms as $term)
                        <li class="font-light leading-relaxed">{{ $term }}</li>
                        @endforeach
                    </ol>
                </div>
                @endif

                {{-- Kebijakan Refund --}}
                @if($vehicleSetting->use_system_refund && !empty($customRefund))
                <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 mb-6 shadow-gomad">
                    <h2 class="font-bold text-lg text-[#111827] mb-4">🔄 Kebijakan Pembatalan & Refund</h2>
                    <ol class="list-decimal list-inside space-y-3 text-sm text-[#111827]">
                        @foreach($customRefund as $policy)
                        <li class="font-light leading-relaxed">{{ $policy }}</li>
                        @endforeach
                    </ol>
                </div>
                @endif
            </div>

            {{-- Kolom Kanan: Harga & Booking --}}
            <div class="lg:col-span-1">
                <div class="sticky top-24 space-y-6">
                    {{-- Card Harga --}}
                    <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 shadow-gomad">
                        <h3 class="font-bold text-lg text-[#111827] mb-4">Harga Sewa</h3>
                        
                        @if($vehicleSetting->price_per_day)
                        <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-4 mb-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500 font-light">Harian</span>
                                <span class="text-xl font-bold text-[#BA1826] font-mono">Rp {{ number_format($vehicleSetting->price_per_day, 0, ',', '.') }}</span>
                            </div>
                            <p class="text-xs text-gray-400 font-light mt-1">per hari</p>
                        </div>
                        @endif

                        @if($vehicleSetting->price_per_hour)
                        <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-[10px] p-4 mb-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500 font-light">Per Jam</span>
                                <span class="text-xl font-bold text-[#BA1826] font-mono">Rp {{ number_format($vehicleSetting->price_per_hour, 0, ',', '.') }}</span>
                            </div>
                            <p class="text-xs text-gray-400 font-light mt-1">per jam</p>
                        </div>
                        @endif

                        @if($vehicleSetting->allow_with_driver)
                        <div class="bg-orange-50 border border-orange-200 rounded-[10px] p-4 mb-3">
                            <p class="text-sm font-medium text-[#111827] mb-2">👨‍✈️ Biaya Supir (Tambahan)</p>
                            @if($vehicleSetting->driver_fee_per_day)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 font-light">Harian</span>
                                <span class="font-bold text-orange-700">Rp {{ number_format($vehicleSetting->driver_fee_per_day, 0, ',', '.') }}/hari</span>
                            </div>
                            @endif
                            @if($vehicleSetting->driver_fee_per_hour)
                            <div class="flex justify-between text-sm mt-1">
                                <span class="text-gray-500 font-light">Per Jam</span>
                                <span class="font-bold text-orange-700">Rp {{ number_format($vehicleSetting->driver_fee_per_hour, 0, ',', '.') }}/jam</span>
                            </div>
                            @endif
                        </div>
                        @endif

                        @if($vehicleSetting->deposit_amount > 0)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-[10px] p-4 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 font-light">🔒 Deposit</span>
                                <span class="font-bold text-yellow-700">Rp {{ number_format($vehicleSetting->deposit_amount, 0, ',', '.') }}</span>
                            </div>
                            <p class="text-xs text-yellow-600 font-light mt-1">Dikembalikan setelah rental selesai</p>
                        </div>
                        @endif

                        {{-- Tombol Booking --}}
                        @auth
                        <a href="{{ route('customer.rental.create', $vehicleSetting) }}" 
                           class="block w-full text-center bg-[#BA1826] text-white py-3.5 rounded-[12px] font-bold text-lg hover:bg-[#8A0F18] transition shadow-sm">
                            🚗 Sewa Sekarang
                        </a>
                        @else
                        <a href="{{ route('login') }}" 
                           class="block w-full text-center border-2 border-[#BA1826] text-[#BA1826] py-3.5 rounded-[12px] font-bold text-lg hover:bg-[#BA1826] hover:text-white transition shadow-sm">
                            🔑 Login untuk Sewa
                        </a>
                        @endauth

                        {{-- Kontak Agency --}}
                        @if($agency->contact_alternate)
                        <div class="mt-4 text-center">
                            <p class="text-xs text-gray-400 font-light">Atau hubungi agency:</p>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $agency->contact_alternate) }}" target="_blank" 
                               class="inline-flex items-center gap-2 text-sm text-green-600 font-medium hover:text-green-700 mt-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                Chat WhatsApp
                            </a>
                        </div>
                        @endif
                    </div>

                    {{-- Info Agency --}}
                    <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-6 shadow-gomad">
                        <h3 class="font-bold text-lg text-[#111827] mb-4">Tentang Agency</h3>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-[#F9FAFB] flex items-center justify-center overflow-hidden flex-shrink-0 border border-[#E5E7EB]">
                                @if($agency->logo)
                                <img src="{{ $agency->logo }}" alt="{{ $agency->agency_name }}" class="w-full h-full object-cover">
                                @else
                                <span class="text-lg">🏢</span>
                                @endif
                            </div>
                            <div>
                                <p class="font-semibold text-[#111827]">{{ $agency->agency_name }}</p>
                                <p class="text-xs text-gray-500 font-mono">⭐ {{ number_format($agency->rating, 1) }} ({{ $agency->total_bookings }} booking)</p>
                            </div>
                        </div>
                        <a href="{{ route('agency.profile', $agency->slug) }}" 
                           class="block w-full text-center border border-[#E5E7EB] text-[#111827] py-2.5 rounded-[10px] text-sm font-medium hover:bg-[#F9FAFB] transition mt-3">
                            Lihat Profil Agency →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
var bookedDates = @json($bookedDates);
var currentMonth = new Date().getMonth();
var currentYear = new Date().getFullYear();
var vehicleId = {{ $vehicle->id }};

function renderCalendar(month, year) {
    var container = document.getElementById('availabilityCalendar');
    var months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    var daysInMonth = new Date(year, month + 1, 0).getDate();
    var today = new Date(); today.setHours(0,0,0,0);
    
    var html = '<div class="text-center mb-4 flex items-center justify-center gap-4">';
    html += '<button onclick="prevMonth()" class="w-8 h-8 flex items-center justify-center border border-[#E5E5E5] rounded-lg hover:bg-[#F5F5F5] text-sm">&larr;</button>';
    html += '<span class="font-bold text-[#111827] text-lg min-w-[150px]">' + months[month] + ' ' + year + '</span>';
    html += '<button onclick="nextMonth()" class="w-8 h-8 flex items-center justify-center border border-[#E5E5E5] rounded-lg hover:bg-[#F5F5F5] text-sm">&rarr;</button></div>';
    
    html += '<div class="grid grid-cols-7 gap-1 text-center">';
    ['Min','Sen','Sel','Rab','Kam','Jum','Sab'].forEach(function(d) {
        html += '<div class="text-[10px] font-mono uppercase tracking-wider text-gray-500 py-1">'+d+'</div>';
    });
    
    var startDay = new Date(year, month, 1).getDay();
    for (var i = 0; i < startDay; i++) html += '<div class="py-2"></div>';
    
    for (var day = 1; day <= daysInMonth; day++) {
        var dateStr = year + '-' + String(month+1).padStart(2,'0') + '-' + String(day).padStart(2,'0');
        var dateObj = new Date(year, month, day);
        var isBooked = bookedDates[dateStr] !== undefined;
        var isToday = dateObj.getTime() === today.getTime();
        var isPast = dateObj < today;
        
        var bgClass = 'bg-white hover:bg-green-50 border border-transparent cursor-pointer';
        var tooltip = '';
        
        if (isBooked) {
            bgClass = 'bg-red-50 border border-red-200 cursor-not-allowed';
            var rentals = bookedDates[dateStr];
            var rentalInfo = '';
            if (Array.isArray(rentals)) {
                rentalInfo = rentals.map(function(r) { return r.rental_code || r.type || 'Dibooking'; }).join(', ');
            }
            tooltip = 'title="' + rentalInfo + '"';
        }
        if (isToday) bgClass = 'bg-yellow-100 border border-yellow-300 font-bold';
        if (isPast && !isToday) bgClass = 'bg-gray-100 text-gray-300 border border-gray-200 cursor-not-allowed';
        
        html += '<div class="py-2 rounded-lg text-sm ' + bgClass + '" ' + tooltip + '>' + day + (isBooked ? '<div class="text-[8px] text-red-500 leading-none">📌</div>' : '') + '</div>';
    }
    html += '</div>';
    container.innerHTML = html;
}

function prevMonth() { 
    if (currentMonth === 0) { currentMonth = 11; currentYear--; } 
    else { currentMonth--; } 
    renderCalendar(currentMonth, currentYear); 
}

function nextMonth() { 
    var today = new Date(); 
    var maxMonth = today.getMonth() + 6, maxYear = today.getFullYear(); 
    if (maxMonth > 11) { maxMonth -= 12; maxYear++; } 
    if (currentYear < maxYear || (currentYear === maxYear && currentMonth < maxMonth)) { 
        if (currentMonth === 11) { currentMonth = 0; currentYear++; } 
        else { currentMonth++; } 
    } 
    renderCalendar(currentMonth, currentYear); 
}

document.addEventListener('DOMContentLoaded', function() {
    renderCalendar(currentMonth, currentYear);
});
</script>
@endpush
@endsection