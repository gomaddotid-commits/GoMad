@extends('layouts.customer')

@section('title', 'Cari Mobil Rental')
@section('content')
@php
    $query = \App\Models\VehicleRentalSetting::with(['vehicle.agency'])
        ->where('is_available_for_rental', true)
        ->whereHas('vehicle', function ($q) {
            $q->where('is_active', true);
        })
        ->whereHas('vehicle.agency', function ($q) {
            $q->where('is_verified', true);
        });

    if (request('type')) {
        match (request('type')) {
            'self_drive' => $query->where('allow_self_drive', true),
            'with_driver' => $query->where('allow_with_driver', true),
            default => null,
        };
    }

    // FILTER BY LARAVOLT CITY
    if (request('city_code')) {
        $query->whereHas('vehicle.agency', function ($q) {
            $q->where('city_code', request('city_code'));
        });
    }

    if (request('province_code')) {
        $query->whereHas('vehicle.agency', function ($q) {
            $q->where('province_code', request('province_code'));
        });
    }

    if (request('agency_id')) {
        $query->whereHas('vehicle', function ($q) {
            $q->where('agency_id', request('agency_id'));
        });
    }

    if (request('price_min')) {
        $query->where(function ($q) {
            $q->where('price_per_day', '>=', request('price_min'))
              ->orWhere('price_per_hour', '>=', request('price_min'));
        });
    }
    if (request('price_max')) {
        $query->where(function ($q) {
            $q->where('price_per_day', '<=', request('price_max'))
              ->orWhere('price_per_hour', '<=', request('price_max'));
        });
    }

    if (request('date')) {
        $date = request('date');
        $query->whereDoesntHave('vehicle.rentals', function ($q) use ($date) {
            $q->whereNotIn('status', ['cancelled'])
              ->whereDate('start_datetime', '<=', $date)
              ->whereDate('end_datetime', '>=', $date);
        });
    }

    if (request('transmission')) {
        $query->where('specifications->transmission', request('transmission'));
    }

    // Sorting
    if (request('sort') == 'price_low') {
        $query->orderBy('price_per_day', 'asc');
    } elseif (request('sort') == 'price_high') {
        $query->orderBy('price_per_day', 'desc');
    } else {
        $query->orderBy('created_at', 'desc');
    }

    $vehicles = $query->paginate(12);
    $agencies = \App\Models\Agency::where('is_verified', true)->orderBy('agency_name')->get();
    $cities = \App\Models\City::with('province')->orderBy('name')->get();
    $provinces = \App\Models\Province::orderBy('name')->get();
    
    $hasFilter = request()->anyFilled(['type', 'date', 'agency_id', 'price_min', 'price_max', 'transmission', 'city_code', 'province_code']);
@endphp

<div class="container-magazine py-8" x-data="{ filterOpen: false }">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-8">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-[#111111] mb-2">Cari Mobil Rental</h1>
            <p class="text-gray-500 font-light">Sewa mobil lepas kunci atau dengan supir. Booking mudah, harga transparan.</p>
        </div>
        
        <div class="flex items-center gap-3">
            @if($hasFilter)
            <a href="{{ route('customer.rental.browse') }}" class="text-xs text-[#C1121F] hover:underline font-medium whitespace-nowrap">Reset Filter</a>
            @endif
            <button @click="filterOpen = !filterOpen" 
                    class="lg:hidden flex items-center gap-2 px-4 py-2 border border-[#E5E5E5] rounded-[12px] text-sm font-medium hover:bg-[#F5F5F5] transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
                @if($hasFilter)
                <span class="w-2 h-2 bg-[#C1121F] rounded-full"></span>
                @endif
            </button>
        </div>
    </div>

    <div class="grid lg:grid-cols-4 gap-8">
        {{-- ═══════════════════════════════════ --}}
        {{-- SIDEBAR FILTER --}}
        {{-- ═══════════════════════════════════ --}}
        <div class="lg:col-span-1" :class="filterOpen ? 'block' : 'hidden'" class="lg:block">
            <div class="card-gomad p-5 sticky top-24 border-[#E5E5E5]">
                <div class="flex items-center justify-between mb-4 border-b border-[#E5E5E5] pb-3">
                    <h3 class="font-bold text-[#111111] font-mono uppercase tracking-wider text-sm">Filter</h3>
                    <button @click="filterOpen = false" class="lg:hidden text-gray-400 hover:text-[#111111]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form action="{{ route('customer.rental.browse') }}" method="GET" class="space-y-4">
                    {{-- Tipe --}}
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Tipe Sewa</label>
                        <select name="type" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent font-medium text-[#111111] text-sm appearance-none cursor-pointer">
                            <option value="">Semua Tipe</option>
                            <option value="self_drive" {{ request('type') == 'self_drive' ? 'selected' : '' }}>🚗 Lepas Kunci</option>
                            <option value="with_driver" {{ request('type') == 'with_driver' ? 'selected' : '' }}>👨‍✈️ Dengan Supir</option>
                        </select>
                    </div>

                    {{-- FILTER LOKASI (LARAVOLT) --}}
                    <div x-data="locationFilter()">
                        <div>
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Provinsi</label>
                            <select name="province_code" x-model="province" @change="loadCities()" 
                                    class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent font-medium text-[#111111] text-sm appearance-none cursor-pointer">
                                <option value="">Semua Provinsi</option>
                                @foreach($provinces as $p)
                                <option value="{{ $p->code }}" {{ request('province_code') == $p->code ? 'selected' : '' }}>{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-4">
                            <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kota</label>
                            <select name="city_code" x-model="city" :disabled="!province"
                                    class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent font-medium text-[#111111] text-sm appearance-none cursor-pointer">
                                <option value="">Semua Kota</option>
                                <template x-for="c in cities" :key="c.code">
                                    <option :value="c.code" x-text="c.name"
                                            :selected="c.code === '{{ request('city_code') }}'"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    {{-- Tanggal --}}
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Tanggal</label>
                        <input type="date" name="date" value="{{ request('date') }}" 
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent font-medium text-[#111111] cursor-pointer">
                    </div>

                    {{-- Agency --}}
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Agency</label>
                        <select name="agency_id" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent font-medium text-[#111111] text-sm appearance-none cursor-pointer">
                            <option value="">Semua Agency</option>
                            @foreach($agencies as $agency)
                            <option value="{{ $agency->id }}" {{ request('agency_id') == $agency->id ? 'selected' : '' }}>
                                {{ $agency->agency_name }} ({{ $agency->city_name }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Transmisi --}}
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Transmisi</label>
                        <select name="transmission" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent font-medium text-[#111111] text-sm appearance-none cursor-pointer">
                            <option value="">Semua</option>
                            <option value="manual" {{ request('transmission') == 'manual' ? 'selected' : '' }}>Manual</option>
                            <option value="automatic" {{ request('transmission') == 'automatic' ? 'selected' : '' }}>Automatic</option>
                        </select>
                    </div>

                    {{-- Rentang Harga --}}
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Harga/Hari (Rp)</label>
                        <div class="flex gap-2 items-center">
                            <input type="number" name="price_min" value="{{ request('price_min') }}" 
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent font-medium text-[#111111] text-sm"
                                   placeholder="Min" min="0">
                            <span class="text-gray-400">-</span>
                            <input type="number" name="price_max" value="{{ request('price_max') }}" 
                                   class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent font-medium text-[#111111] text-sm"
                                   placeholder="Max" min="0">
                        </div>
                    </div>

                    <button type="submit" class="w-full btn-gomad-primary text-center py-2.5 text-sm mt-2">Terapkan Filter</button>
                </form>
            </div>
        </div>

        {{-- ═══════════════════════════════════ --}}
        {{-- RESULTS --}}
        {{-- ═══════════════════════════════════ --}}
        <div class="lg:col-span-3">
            {{-- Toolbar --}}
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6 border-b border-[#E5E5E5] pb-4">
                <p class="text-sm text-gray-500 font-light">
                    Menampilkan <strong class="text-[#111111]">{{ $vehicles->total() }}</strong> mobil
                    @if(request('type'))
                    <span class="text-gray-400">• {{ request('type') == 'self_drive' ? 'Lepas Kunci' : 'Dengan Supir' }}</span>
                    @endif
                    @if(request('city_code'))
                    <span class="text-gray-400">• {{ \App\Models\City::find(request('city_code'))?->name ?? '' }}</span>
                    @endif
                </p>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-mono uppercase tracking-wider text-gray-500">Urut:</span>
                    <select onchange="window.location.href=this.value" class="text-xs border border-[#E5E5E5] rounded-lg px-2 py-1 bg-white text-[#111111]">
                        <option value="{{ request()->fullUrlWithQuery(['sort' => 'newest']) }}" {{ request('sort', 'newest') == 'newest' ? 'selected' : '' }}>Terbaru</option>
                        <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_low']) }}" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Harga Terendah</option>
                        <option value="{{ request()->fullUrlWithQuery(['sort' => 'price_high']) }}" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Harga Tertinggi</option>
                    </select>
                </div>
            </div>

            @if($vehicles->isEmpty())
            <div class="card-gomad p-12 text-center border-[#E5E5E5]">
                <div class="w-16 h-16 bg-[#F5F5F5] rounded-[12px] flex items-center justify-center mx-auto mb-4 border border-[#E5E5E5]">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <p class="text-gray-500 text-lg font-light">Tidak ada mobil ditemukan.</p>
                <a href="{{ route('customer.rental.browse') }}" class="inline-block mt-4 text-[#C1121F] hover:underline font-medium">Reset Filter</a>
            </div>
            @else
            <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($vehicles as $setting)
                @php
                    $vehicle = $setting->vehicle;
                    $agency = $vehicle->agency;
                    $rentalService = app(\App\Services\RentalService::class);
                    $bookedDates = $rentalService->getBookedDates($vehicle->id);
                    $bookedDatesJson = json_encode($bookedDates);
                @endphp
                <div class="bg-white border border-[#E5E5E5] rounded-[12px] overflow-hidden shadow-sm hover:border-[#C1121F] transition-colors group">
                    {{-- Foto --}}
                    <div class="h-40 bg-[#F5F5F5] flex items-center justify-center overflow-hidden border-b border-[#E5E5E5] relative">
                        @if($vehicle->vehicle_image)
                        <img src="{{ $vehicle->vehicle_image }}" alt="{{ $vehicle->plate_number }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        @else
                        <span class="text-5xl text-gray-300">🚗</span>
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
                    
                    <div class="p-4">
                        {{-- Agency Info --}}
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-full bg-[#F5F5F5] flex items-center justify-center overflow-hidden flex-shrink-0 border border-[#E5E5E5]">
                                @if($agency->logo)<img src="{{ $agency->logo }}" class="w-full h-full object-cover">@else<span class="text-sm">🏢</span>@endif
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-xs text-[#111111] truncate">{{ $agency->agency_name }}</p>
                                <div class="flex items-center gap-1 text-[10px] text-gray-400 font-mono">
                                    <span>⭐ {{ number_format($agency->rating, 1) }}</span>
                                    <span>•</span>
                                    <span>{{ $agency->city_name }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Vehicle Info --}}
                        <p class="font-bold text-sm text-[#111111]">{{ $vehicle->brand }} {{ $vehicle->model }}</p>
                        <p class="text-xs text-gray-500 font-mono">{{ $vehicle->plate_number }} • {{ $vehicle->year }}</p>

                        {{-- Specs --}}
                        <div class="flex flex-wrap gap-1 mt-1.5">
                            @php $specs = is_array($setting->specifications) ? $setting->specifications : json_decode($setting->specifications, true) ?? []; @endphp
                            @if(isset($specs['transmission']))
                            <span class="px-1.5 py-0.5 bg-[#F5F5F5] text-[9px] font-mono uppercase rounded text-gray-500">{{ $specs['transmission'] == 'automatic' ? 'AT' : 'MT' }}</span>
                            @endif
                            @if($specs['ac'] ?? true)
                            <span class="px-1.5 py-0.5 bg-[#F5F5F5] text-[9px] font-mono uppercase rounded text-gray-500">AC</span>
                            @endif
                            <span class="px-1.5 py-0.5 bg-[#F5F5F5] text-[9px] font-mono uppercase rounded text-gray-500">{{ $vehicle->capacity }} Seat</span>
                        </div>

                        {{-- Price --}}
                        <div class="mt-3 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-2.5">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500 font-light">Mulai</span>
                                <span class="font-bold text-[#C1121F] font-mono">
                                    @if($setting->price_per_day) Rp {{ number_format($setting->price_per_day, 0, ',', '.') }}/hari
                                    @elseif($setting->price_per_hour) Rp {{ number_format($setting->price_per_hour, 0, ',', '.') }}/jam
                                    @else - @endif
                                </span>
                            </div>
                            @if($setting->allow_with_driver && $setting->driver_fee_per_day > 0)
                            <div class="flex justify-between text-[10px] mt-0.5">
                                <span class="text-gray-400 font-light">+ Supir</span>
                                <span class="text-orange-600 font-mono">Rp {{ number_format($setting->driver_fee_per_day, 0, ',', '.') }}/hari</span>
                            </div>
                            @endif
                        </div>

                        {{-- Calendar --}}
                        <div class="mt-2">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[9px] font-mono uppercase tracking-wider text-gray-400">30 Hari</span>
                                <button type="button" onclick="openCalendarModal('{{ $vehicle->id }}')" class="text-[9px] text-[#C1121F] hover:underline font-medium">📅 Cek Tanggal</button>
                            </div>
                            <div class="mini-calendar-{{ $vehicle->id }} flex flex-wrap gap-0.5" data-booked='{{ $bookedDatesJson }}'>
                                <span class="text-[9px] text-gray-400">⏳</span>
                            </div>
                        </div>

                        {{-- CTA --}}
                        <a href="{{ route('customer.rental.create', $setting) }}" 
                           class="block w-full text-center bg-[#C1121F] text-white py-2 rounded-[12px] font-semibold hover:bg-[#8A0F18] transition text-xs mt-3">
                            Sewa Sekarang
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="mt-8">{{ $vehicles->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════ --}}
{{-- MODAL KALENDER BESAR --}}
{{-- ═══════════════════════════════════ --}}
<div id="calendarModal" class="fixed inset-0 bg-[#111111]/50 z-50 hidden items-center justify-center p-4" style="display:none;">
    <div class="bg-white rounded-[12px] shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto border border-[#E5E5E5]">
        {{-- Header --}}
        <div class="sticky top-0 bg-white border-b border-[#E5E5E5] p-5 rounded-t-[12px] z-10 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-lg text-[#111111]" id="modalVehicleName">Kalender Ketersediaan</h3>
                <p class="text-xs text-gray-500 font-light" id="modalVehiclePlate"></p>
            </div>
            <button onclick="closeCalendarModal()" class="w-8 h-8 flex items-center justify-center border border-[#E5E5E5] rounded-lg hover:bg-[#F5F5F5] transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        
        {{-- Calendar Container --}}
        <div class="p-5">
            {{-- Navigation --}}
            <div class="flex items-center justify-between mb-4">
                <button onclick="modalPrevMonth()" class="w-8 h-8 flex items-center justify-center border border-[#E5E5E5] rounded-lg hover:bg-[#F5F5F5] text-sm">&larr;</button>
                <span class="font-bold text-[#111111]" id="modalMonthLabel">-</span>
                <button onclick="modalNextMonth()" class="w-8 h-8 flex items-center justify-center border border-[#E5E5E5] rounded-lg hover:bg-[#F5F5F5] text-sm">&rarr;</button>
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
            <div class="flex items-center gap-4 mt-4 text-xs border-t border-[#E5E5E5] pt-4">
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
        <div class="sticky bottom-0 bg-white border-t border-[#E5E5E5] p-4 rounded-b-[12px] flex gap-3">
            <button onclick="closeCalendarModal()" class="flex-1 border border-[#E5E5E5] py-2.5 rounded-[12px] font-medium hover:bg-[#F5F5F5] transition text-sm">
                Tutup
            </button>
            <a href="#" id="modalBookNow" class="flex-1 bg-[#C1121F] text-white py-2.5 rounded-[12px] font-semibold hover:bg-[#8A0F18] transition text-sm text-center">
                Sewa Sekarang
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ═══════════════════════════════════════
// LOCATION FILTER
// ═══════════════════════════════════════
function locationFilter() {
    return {
        province: '{{ request('province_code') }}',
        city: '{{ request('city_code') }}',
        cities: @json($cities->filter(fn($c) => !request('province_code') || $c->province_code == request('province_code'))->values()),

        async loadCities() {
            if (!this.province) { this.cities = []; this.city = ''; return; }
            try {
                const res = await fetch(`/api/v1/region/cities?province=${this.province}`);
                const data = await res.json();
                this.cities = data.data || data || [];
                if (this.city && !this.cities.find(c => c.code === this.city)) {
                    this.city = '';
                }
            } catch (e) {
                console.error('Failed to load cities:', e);
            }
        }
    }
}

// ═══════════════════════════════════════
// MINI CALENDAR
// ═══════════════════════════════════════
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
            html += '<div class="w-3 h-3 rounded-sm ' + bgClass + '" title="' + dateStr + (isBooked ? ' - Dibooking' : ' - Tersedia') + '"></div>';
        }
        container.innerHTML = html;
    });
});

// ═══════════════════════════════════════
// MODAL CALENDAR
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