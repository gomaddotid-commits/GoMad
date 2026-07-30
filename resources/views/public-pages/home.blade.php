@extends('layouts.public')

@section('title', 'Beranda')
@section('meta_description', 'GoMad - Solusi transportasi Anda. Booking travel antar kota dan sewa mobil dengan mudah, dijemput di rumah, dan diantar ke tujuan.')
@section('og_image', asset('images/og-home.png'))

@section('content')
@php
    $cities = \App\Models\City::with('province')->orderBy('name')->get();
    $popularRoutes = \App\Models\Route::with(['originCity', 'destinationCity'])
        ->withCount('schedules')
        ->orderByDesc('schedules_count')
        ->limit(10)
        ->get();
    
    $activePromos = \App\Models\Promo::active()->latest()->limit(10)->get();
    
    $rentalByCity = \App\Models\VehicleRentalSetting::with(['vehicle.agency'])
        ->where('is_available_for_rental', true)
        ->whereHas('vehicle', fn($q) => $q->where('is_active', true))
        ->whereHas('vehicle.agency', fn($q) => $q->where('is_verified', true))
        ->get()
        ->groupBy(function($item) {
            return $item->vehicle->agency->city_name ?? 'Unknown';
        })
        ->take(4);
    
    $rentalVehiclesCount = \App\Models\VehicleRentalSetting::where('is_available_for_rental', true)
        ->whereHas('vehicle', fn($q) => $q->where('is_active', true))
        ->whereHas('vehicle.agency', fn($q) => $q->where('is_verified', true))
        ->count();
    
    $mapWarungs = \App\Models\PaymentAgent::where('is_verified', true)
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->get()
        ->map(function($w) {
            return [
                'agent_name' => $w->agent_name,
                'address' => $w->address,
                'latitude' => (float) $w->latitude,
                'longitude' => (float) $w->longitude,
                'owner_phone' => $w->owner_phone,
                'maps_link' => $w->maps_link,
            ];
        });
    
    $agencies = \App\Models\Agency::where('is_verified', true)->orderBy('agency_name')->get();
    $citiesData = $cities->map(fn($c) => [
        'code' => $c->code,
        'name' => $c->name,
        'province_name' => $c->province->name ?? '',
    ])->values()->toArray();
@endphp

{{-- HERO SECTION --}}
<section class="relative bg-[#BA1826] overflow-hidden py-12 md:py-20 min-h-[40vh] md:min-h-[60vh] flex items-center justify-center">
    <div class="container-magazine relative z-10 w-full flex justify-center">
        <div class="text-white space-y-6 md:space-y-8 max-w-3xl text-center">
            <h1 class="text-4xl md:text-7xl lg:text-8xl font-bold tracking-tight leading-[0.9]">
                Travel & Rental<br>
                <span class="text-white/70 italic">Door to Door</span><br>
                Service
            </h1>
            <div class="text-base md:text-lg text-white/100 max-w-md leading-relaxed font-light mx-auto min-h-[3rem] md:min-h-[4rem] flex items-center justify-center"
                x-data="{
                    texts: [
                        'Tak perlu datang ke terminal. Gomad siap menjemput.',
                        'Mobilitas antar kota, tanpa batas jarak.',
                        'Sewa mobil lepas kunci atau dengan supir.',
                        'Pesan sekarang, sampai ke rumah tanpa ribet.'
                    ],
                    currentIndex: 0,
                    interval: null,
                    startRotation() {
                        this.interval = setInterval(() => {
                            this.currentIndex = (this.currentIndex + 1) % this.texts.length;
                        }, 4000);
                    },
                    stopRotation() {
                        clearInterval(this.interval);
                    }
                }"
                x-init="startRotation()"
                @mouseenter="stopRotation()"
                @mouseleave="startRotation()">
                <span x-show="true" 
                    x-text="texts[currentIndex]"
                    x-transition:enter="transition ease-in-out duration-500 transform"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in-out duration-500 transform"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2">
                </span>
            </div>
        </div>
    </div>
    <div class="absolute bottom-0 left-0 right-0 h-px bg-white/20"></div>
</section>

{{-- FLOATING SEARCH BAR --}}
<section class="-mt-16 relative z-20 container-magazine" x-data="{ searchMode: 'travel' }">
    <div class="card-gomad shadow-gomad-lg border-0 bg-white">
        <div class="flex justify-center mb-5">
            <div class="flex bg-[#F9FAFB] rounded-lg p-1">
                <button @click="searchMode = 'travel'" 
                        :class="searchMode === 'travel' ? 'bg-white shadow text-[#BA1826]' : 'text-gray-500 hover:text-[#111827]'"
                        class="px-6 py-2 rounded-md text-sm font-semibold transition">
                    Cari Travel
                </button>
                <button @click="searchMode = 'rental'" 
                        :class="searchMode === 'rental' ? 'bg-white shadow text-[#BA1826]' : 'text-gray-500 hover:text-[#111827]'"
                        class="px-6 py-2 rounded-md text-sm font-semibold transition">
                    Cari Rental
                </button>
            </div>
        </div>

        {{-- Mode Travel --}}
        <div x-show="searchMode === 'travel'" x-data="searchableSelect()">
            <form action="{{ route('search') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="col-span-1">
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Asal</label>
                    <div class="relative">
                        <div class="relative">
                            <input type="text" x-model="originSearch" @click="originOpen = !originOpen" @input="originOpen = true"
                                   placeholder="Ketik atau pilih kota..."
                                   class="w-full px-0 py-2 pr-8 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent font-medium text-[#111827] transition-colors duration-300 cursor-pointer">
                            <svg @click.stop="originOpen = !originOpen" class="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 cursor-pointer hover:text-[#111827] transition" :class="{'rotate-180': originOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7 7"/></svg>
                        </div>
                        <div x-show="originOpen" @click.away="originOpen = false" x-cloak
                             class="absolute z-50 w-full mt-1 bg-white border border-[#E5E7EB] rounded-[12px] shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="city in filteredCities(originSearch)" :key="city.code">
                                <div @click="selectOrigin(city); originOpen = false;"
                                     class="px-4 py-2.5 text-sm hover:bg-[#BA1826]/5 cursor-pointer transition border-b border-[#F5F5F5] last:border-0"
                                     :class="{'bg-[#BA1826]/5 font-semibold text-[#BA1826]': city.code === selectedOrigin}">
                                    <span x-text="city.name + ' (' + city.province_name + ')'"></span>
                                </div>
                            </template>
                            <div x-show="filteredCities(originSearch).length === 0" class="px-4 py-2.5 text-sm text-gray-400 text-center">Tidak ditemukan</div>
                        </div>
                        <input type="hidden" name="origin" :value="selectedOriginName">
                    </div>
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Tujuan</label>
                    <div class="relative">
                        <div class="relative">
                            <input type="text" x-model="destinationSearch" @click="destinationOpen = !destinationOpen" @input="destinationOpen = true"
                                   placeholder="Ketik atau pilih kota..."
                                   class="w-full px-0 py-2 pr-8 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent font-medium text-[#111827] transition-colors duration-300 cursor-pointer">
                            <svg @click.stop="destinationOpen = !destinationOpen" class="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 cursor-pointer hover:text-[#111827] transition" :class="{'rotate-180': destinationOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7 7"/></svg>
                        </div>
                        <div x-show="destinationOpen" @click.away="destinationOpen = false" x-cloak
                             class="absolute z-50 w-full mt-1 bg-white border border-[#E5E7EB] rounded-[12px] shadow-lg max-h-60 overflow-y-auto">
                            <template x-for="city in filteredCities(destinationSearch)" :key="city.code">
                                <div @click="selectDestination(city); destinationOpen = false;"
                                     class="px-4 py-2.5 text-sm hover:bg-[#BA1826]/5 cursor-pointer transition border-b border-[#F5F5F5] last:border-0"
                                     :class="{'bg-[#BA1826]/5 font-semibold text-[#BA1826]': city.code === selectedDestination}">
                                    <span x-text="city.name + ' (' + city.province_name + ')'"></span>
                                </div>
                            </template>
                            <div x-show="filteredCities(destinationSearch).length === 0" class="px-4 py-2.5 text-sm text-gray-400 text-center">Tidak ditemukan</div>
                        </div>
                        <input type="hidden" name="destination" :value="selectedDestinationName">
                    </div>
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Tanggal</label>
                    <input type="date" name="date" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent font-medium text-[#111827] transition-colors duration-300">
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kelas</label>
                    <select name="travel_class" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent font-medium text-[#111827] appearance-none cursor-pointer transition-colors duration-300">
                        <option value="">Semua</option>
                        <option value="economy">Ekonomi</option>
                        <option value="premium">Premium</option>
                        <option value="charter">Charter</option>
                    </select>
                </div>
                <div class="col-span-1 flex items-end">
                    <button type="submit" class="w-full btn-gomad-primary text-center py-2.5 text-sm rounded-[10px]">Cari Jadwal</button>
                </div>
            </form>
        </div>

        {{-- Mode Rental --}}
        <div x-show="searchMode === 'rental'" x-cloak>
            <form action="{{ route('rental.public') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="col-span-1">
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Tipe</label>
                    <select name="type" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent font-medium text-[#111827] appearance-none cursor-pointer transition-colors duration-300">
                        <option value="">Semua Tipe</option>
                        <option value="self_drive">🚗 Lepas Kunci</option>
                        <option value="with_driver">👨‍✈️ Dengan Supir</option>
                    </select>
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Tanggal</label>
                    <input type="date" name="date" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent font-medium text-[#111827] transition-colors duration-300">
                </div>
                <div class="col-span-1">
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Agency</label>
                    <select name="agency_id" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent font-medium text-[#111827] appearance-none cursor-pointer transition-colors duration-300">
                        <option value="">Semua Agency</option>
                        @foreach($agencies as $agency)
                        <option value="{{ $agency->id }}">{{ $agency->agency_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-1 flex items-end">
                    <button type="submit" class="w-full bg-[#BA1826] text-white py-2.5 text-sm rounded-[10px] font-semibold hover:bg-[#8A0F18] transition">Cari Rental</button>
                </div>
            </form>
        </div>
    </div>
</section>

{{-- STATISTIK --}}
<section class="section container-magazine">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center">
            <p class="text-3xl md:text-4xl font-bold text-[#BA1826]">{{ \App\Models\Agency::where('is_verified', true)->count() }}+</p>
            <p class="text-sm text-gray-500 font-light mt-1">Agency Terverifikasi</p>
        </div>
        <div class="text-center">
            <p class="text-3xl md:text-4xl font-bold text-[#BA1826]">{{ \App\Models\Route::where('is_active', true)->count() }}+</p>
            <p class="text-sm text-gray-500 font-light mt-1">Rute Travel</p>
        </div>
        <div class="text-center">
            <p class="text-3xl md:text-4xl font-bold text-[#BA1826]">{{ $rentalVehiclesCount }}+</p>
            <p class="text-sm text-gray-500 font-light mt-1">Mobil Rental</p>
        </div>
        <div class="text-center">
            <p class="text-3xl md:text-4xl font-bold text-[#BA1826]">{{ $mapWarungs->count() }}+</p>
            <p class="text-sm text-gray-500 font-light mt-1">Warung GoMad</p>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════ --}}
{{-- SECTION 1: PROMO ROLLING --}}
{{-- ═══════════════════════════════════════════ --}}
<x-home.promo-rolling :promos="$activePromos" />

{{-- ═══════════════════════════════════════════ --}}
{{-- SECTION 2: RUTE POPULER SLIDER --}}
{{-- ═══════════════════════════════════════════ --}}
<x-home.route-slider :routes="$popularRoutes" />

{{-- ═══════════════════════════════════════════ --}}
{{-- SECTION 3: TEMUKAN RENTAL DI KOTAMU --}}
{{-- ═══════════════════════════════════════════ --}}
<x-home.rental-by-city :rentalByCity="$rentalByCity" />

{{-- LAYANAN GOMAD --}}
<section id="services" class="section container-magazine">
    <div class="flex items-center gap-4 mb-12">
        <div class="h-px w-12 bg-[#BA1826]"></div>
        <h2 class="text-3xl font-bold tracking-tight text-[#111827]">Layanan GoMad</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="card-gomad flex flex-col gap-4 group">
            <div class="text-4xl text-[#BA1826]">🚐</div>
            <h3 class="text-xl font-bold text-[#111827]">Travel Ekonomi</h3>
            <p class="text-sm text-gray-500 leading-relaxed">Mobil 8 seat + overload 2, bagasi 15kg. Solusi mobilitas harian terpercaya antar kota.</p>
            <div class="mt-auto pt-4 border-t border-[#E5E7EB] group-hover:border-[#BA1826] transition-colors flex justify-between items-center">
                <span class="text-xs font-mono uppercase">Mulai 50k</span>
                <span class="text-[#BA1826] group-hover:translate-x-2 transition-transform">→</span>
            </div>
        </div>
        <div class="card-gomad flex flex-col gap-4 group">
            <div class="text-4xl text-[#BA1826]">🚗</div>
            <h3 class="text-xl font-bold text-[#111827]">Travel Premium</h3>
            <p class="text-sm text-gray-500 leading-relaxed">8 seat strict, bagasi 20kg. Kenyamanan ekstra untuk perjalanan bisnis dan keluarga.</p>
            <div class="mt-auto pt-4 border-t border-[#E5E7EB] group-hover:border-[#BA1826] transition-colors flex justify-between items-center">
                <span class="text-xs font-mono uppercase">Mulai 80k</span>
                <span class="text-[#BA1826] group-hover:translate-x-2 transition-transform">→</span>
            </div>
        </div>
        <div class="card-gomad flex flex-col gap-4 group">
            <div class="text-4xl text-[#BA1826]">🚙</div>
            <h3 class="text-xl font-bold text-[#111827]">Rental Lepas Kunci</h3>
            <p class="text-sm text-gray-500 leading-relaxed">Sewa mobil tanpa supir. Bebas eksplorasi dengan syarat KTP & SIM valid. Harga flat per hari.</p>
            <div class="mt-auto pt-4 border-t border-[#E5E7EB] group-hover:border-[#BA1826] transition-colors flex justify-between items-center">
                <span class="text-xs font-mono uppercase">Mulai 250k/hari</span>
                <span class="text-[#BA1826] group-hover:translate-x-2 transition-transform">→</span>
            </div>
        </div>
        <div class="card-gomad flex flex-col gap-4 group">
            <div class="text-4xl text-[#BA1826]">👨‍✈️</div>
            <h3 class="text-xl font-bold text-[#111827]">Rental + Supir</h3>
            <p class="text-sm text-gray-500 leading-relaxed">Sewa mobil dengan supir profesional. Nyaman, aman, tanpa repot. Bebas rute tujuan.</p>
            <div class="mt-auto pt-4 border-t border-[#E5E7EB] group-hover:border-[#BA1826] transition-colors flex justify-between items-center">
                <span class="text-xs font-mono uppercase">Mulai 350k/hari</span>
                <span class="text-[#BA1826] group-hover:translate-x-2 transition-transform">→</span>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <div class="card-gomad flex flex-col gap-4 group">
            <div class="text-4xl text-[#BA1826]">🚌</div>
            <h3 class="text-xl font-bold text-[#111827]">Charter</h3>
            <p class="text-sm text-gray-500 leading-relaxed">Sewa mobil + supir untuk perjalanan khusus. Harga flat per mobil, fleksibel sesuai kebutuhan rombongan.</p>
            <div class="mt-auto pt-4 border-t border-[#E5E7EB] group-hover:border-[#BA1826] transition-colors flex justify-between items-center">
                <span class="text-xs font-mono uppercase">Hubungi Agency</span>
                <span class="text-[#BA1826] group-hover:translate-x-2 transition-transform">→</span>
            </div>
        </div>
        <div class="card-gomad flex flex-col gap-4 group">
            <div class="text-4xl text-[#BA1826]">🏪</div>
            <h3 class="text-xl font-bold text-[#111827]">Warung GoMad</h3>
            <p class="text-sm text-gray-500 leading-relaxed">Bayar cash di warung terdekat. Tanpa rekening, tanpa ribet. Tersebar di seluruh Madura.</p>
            <div class="mt-auto pt-4 border-t border-[#E5E7EB] group-hover:border-[#BA1826] transition-colors flex justify-between items-center">
                <span class="text-xs font-mono uppercase">{{ $mapWarungs->count() }}+ Titik</span>
                <span class="text-[#BA1826] group-hover:translate-x-2 transition-transform">→</span>
            </div>
        </div>
    </div>
</section>

{{-- METODE PEMBAYARAN --}}
<section class="section bg-[#F9FAFB]">
    <div class="container-magazine grid md:grid-cols-2 gap-12">
        <div>
            <h2 class="text-3xl font-bold text-[#111827] mb-6">Metode Pembayaran</h2>
            <p class="text-gray-500 mb-8">Didukung oleh sistem pembayaran modern dan ekosistem warung lokal.</p>
            <div class="grid grid-cols-4 gap-4 opacity-70 grayscale hover:grayscale-0 transition-all duration-500">
                <div class="bg-white p-3 rounded-[10px] shadow-sm border border-[#E5E7EB] flex items-center justify-center h-12 font-mono text-sm text-[#111827] font-semibold">BCA</div>
                <div class="bg-white p-3 rounded-[10px] shadow-sm border border-[#E5E7EB] flex items-center justify-center h-12 font-mono text-sm text-[#111827] font-semibold">OVO</div>
                <div class="bg-white p-3 rounded-[10px] shadow-sm border border-[#E5E7EB] flex items-center justify-center h-12 font-mono text-sm text-[#111827] font-semibold">DANA</div>
                <div class="bg-white p-3 rounded-[10px] shadow-sm border border-[#E5E7EB] flex items-center justify-center h-12 font-mono text-sm text-[#111827] font-semibold">QRIS</div>
                <div class="bg-white p-3 rounded-[10px] shadow-sm border border-[#E5E7EB] flex items-center justify-center h-12 font-mono text-sm text-[#111827] font-semibold col-span-2">Transfer Bank</div>
                <div class="bg-white p-3 rounded-[10px] shadow-sm border border-[#E5E7EB] flex items-center justify-center h-12 font-mono text-sm text-[#111827] font-semibold col-span-2">Bayar ke Supir (COD)</div>
            </div>
        </div>
        <div>
            <div id="homeWarungMap" style="height: 300px; z-index: 1;" class="rounded-[12px] border border-[#E5E7EB] overflow-hidden"></div>
            <p class="text-xs text-center mt-2 text-gray-400 font-mono uppercase tracking-wider">{{ $mapWarungs->count() }}+ Warung GoMad tersebar di Madura</p>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="section container-magazine text-center">
    <div class="max-w-2xl mx-auto">
        <h2 class="text-3xl font-bold text-[#111827] mb-4">Siap Berangkat?</h2>
        <p class="text-gray-500 mb-8 font-light">Download aplikasi GoMad untuk pengalaman booking yang lebih mudah.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('download-app') }}" class="bg-[#111827] text-white px-8 py-4 rounded-[10px] inline-flex items-center gap-4 hover:bg-[#111827]/80 transition justify-center">
                <span class="text-3xl font-mono">▶</span>
                <div class="text-left"><div class="text-[10px] font-mono uppercase tracking-wider opacity-80">GET IT ON</div><div class="text-lg font-bold">Google Play</div></div>
            </a>
            <a href="{{ route('download-app') }}" class="bg-[#111827] text-white px-8 py-4 rounded-[10px] inline-flex items-center gap-4 hover:bg-[#111827]/80 transition justify-center">
                <span class="text-3xl font-mono">🍎</span>
                <div class="text-left"><div class="text-[10px] font-mono uppercase tracking-wider opacity-80">DOWNLOAD ON</div><div class="text-lg font-bold">App Store</div></div>
            </a>
        </div>
    </div>
</section>

@push('scripts')
<script>
// ═══════════════════════════════════════════
// SEARCHABLE SELECT
// ═══════════════════════════════════════════
function searchableSelect() {
    return {
        originSearch: '',
        originOpen: false,
        selectedOrigin: '',
        selectedOriginName: '',
        destinationSearch: '',
        destinationOpen: false,
        selectedDestination: '',
        selectedDestinationName: '',
        
        allCities: @json($citiesData),
        
        filteredCities(search) {
            var q = (search || '').toLowerCase();
            if (!q) return this.allCities;
            return this.allCities.filter(function(c) {
                return c.name.toLowerCase().includes(q) || c.province_name.toLowerCase().includes(q);
            });
        },
        
        selectOrigin(city) {
            this.selectedOrigin = city.code;
            this.selectedOriginName = city.name;
            this.originSearch = city.name + ' (' + city.province_name + ')';
        },
        
        selectDestination(city) {
            this.selectedDestination = city.code;
            this.selectedDestinationName = city.name;
            this.destinationSearch = city.name + ' (' + city.province_name + ')';
        }
    }
}

// ═══════════════════════════════════════════
// PROMO ROLLING (ATAS-BAWAH) - GLOBAL Alpine.data
// ═══════════════════════════════════════════
document.addEventListener('alpine:init', () => {
    Alpine.data('promoRolling', (items) => ({
        promoItems: items || [],
        currentPromoIndex: 0,
        isPlaying: true,
        interval: null,
        isManualOverride: false,
        manualTimeout: null,
        loading: true,
        
        initPromoRolling() {
            // Simulasi loading
            setTimeout(() => {
                this.loading = false;
            }, 600);
            
            if (this.promoItems.length > 1) {
                this.startAutoRolling();
            }
        },
        
        startAutoRolling() {
            if (this.interval) clearInterval(this.interval);
            this.isPlaying = true;
            this.interval = setInterval(() => {
                if (this.isPlaying && !this.isManualOverride) {
                    this.nextPromo();
                }
            }, 3000);
        },
        
        stopAutoRolling() {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
            this.isPlaying = false;
        },
        
        togglePlay() {
            if (this.isPlaying) {
                this.stopAutoRolling();
            } else {
                this.startAutoRolling();
            }
        },
        
        nextPromo() {
            if (this.promoItems.length <= 1) return;
            this.manualOverride();
            this.currentPromoIndex = (this.currentPromoIndex + 1) % this.promoItems.length;
        },
        
        prevPromo() {
            if (this.promoItems.length <= 1) return;
            this.manualOverride();
            this.currentPromoIndex = (this.currentPromoIndex - 1 + this.promoItems.length) % this.promoItems.length;
        },
        
        goToPromo(index) {
            if (index === this.currentPromoIndex) return;
            this.manualOverride();
            this.currentPromoIndex = index;
        },
        
        manualOverride() {
            this.isManualOverride = true;
            if (this.manualTimeout) {
                clearTimeout(this.manualTimeout);
                this.manualTimeout = null;
            }
            if (this.isPlaying) {
                this.manualTimeout = setTimeout(() => {
                    this.isManualOverride = false;
                    this.manualTimeout = null;
                }, 5000);
            }
        }
    }));
});

// ═══════════════════════════════════════════
// ROUTE SLIDER (KIRI-KANAN) - GLOBAL Alpine.data
// ═══════════════════════════════════════════
document.addEventListener('alpine:init', () => {
    Alpine.data('routeSlider', (items) => ({
        routeItems: items || [],
        currentRouteIndex: 0,
        itemsPerView: 3,
        maxRouteIndex: 0,
        routePages: [],
        currentRoutePage: 0,
        loading: true,
        
        initRouteSlider() {
            // Simulasi loading
            setTimeout(() => {
                this.loading = false;
            }, 800);
            
            this.updateItemsPerView();
            this.maxRouteIndex = Math.max(0, this.routeItems.length - this.itemsPerView);
            
            var totalPages = Math.ceil(this.routeItems.length / this.itemsPerView);
            this.routePages = Array.from({ length: totalPages }, function(_, i) { return i; });
            
            this.currentRoutePage = Math.floor(this.currentRouteIndex / this.itemsPerView);
            
            var self = this;
            window.addEventListener('resize', function() {
                self.updateItemsPerView();
                self.maxRouteIndex = Math.max(0, self.routeItems.length - self.itemsPerView);
                
                if (self.currentRouteIndex > self.maxRouteIndex) {
                    self.currentRouteIndex = self.maxRouteIndex;
                }
                
                var totalPages = Math.ceil(self.routeItems.length / self.itemsPerView);
                self.routePages = Array.from({ length: totalPages }, function(_, i) { return i; });
                self.currentRoutePage = Math.floor(self.currentRouteIndex / self.itemsPerView);
            });
        },
        
        updateItemsPerView() {
            var width = window.innerWidth;
            if (width < 640) {
                this.itemsPerView = 1;
            } else if (width < 1024) {
                this.itemsPerView = 2;
            } else {
                this.itemsPerView = 3;
            }
        },
        
        nextRoute() {
            if (this.currentRouteIndex < this.maxRouteIndex) {
                this.currentRouteIndex++;
                this.currentRoutePage = Math.floor(this.currentRouteIndex / this.itemsPerView);
            }
        },
        
        prevRoute() {
            if (this.currentRouteIndex > 0) {
                this.currentRouteIndex--;
                this.currentRoutePage = Math.floor(this.currentRouteIndex / this.itemsPerView);
            }
        },
        
        goToRoutePage(page) {
            var newIndex = page * this.itemsPerView;
            if (newIndex <= this.maxRouteIndex) {
                this.currentRouteIndex = newIndex;
                this.currentRoutePage = page;
            }
        }
    }));
});

// ═══════════════════════════════════════════
// LEAFET MAP
// ═══════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    var mapEl = document.getElementById('homeWarungMap');
    if (!mapEl) return;

    var map = L.map('homeWarungMap').setView([-7.1, 113.2], 8);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 18,
    }).addTo(map);

    var warungs = @json($mapWarungs);
    if (warungs.length === 0) return;

    var bounds = L.latLngBounds();
    
    warungs.forEach(function(w) {
        var lat = parseFloat(w.latitude);
        var lng = parseFloat(w.longitude);
        if (isNaN(lat) || isNaN(lng)) return;
        
        var warungIcon = L.divIcon({
            html: '<div style="background:#BA1826;color:white;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.3);">🏪</div>',
            className: '',
            iconSize: [32, 32],
            iconAnchor: [16, 16],
        });
        
        L.marker([lat, lng], { icon: warungIcon })
            .addTo(map)
            .bindPopup(
                '<div style="min-width:160px;font-family:Montserrat, sans-serif;">' +
                    '<strong style="color:#111827;">' + (w.agent_name || '') + '</strong><br>' +
                    '<span style="font-size:12px;color:#666;">' + (w.address || '') + '</span><br>' +
                    '<span style="font-size:12px;">📞 ' + (w.owner_phone || '-') + '</span><br>' +
                    '<a href="' + (w.maps_link || 'https://www.google.com/maps?q=' + lat + ',' + lng) + '" target="_blank" style="display:inline-block;margin-top:6px;background:#BA1826;color:white;padding:6px 12px;border-radius:8px;text-decoration:none;font-size:12px;font-weight:600;">🗺️ Google Maps</a>' +
                '</div>'
            );
        
        bounds.extend([lat, lng]);
    });
    
    if (warungs.length > 0) {
        map.fitBounds(bounds, { padding: [30, 30], maxZoom: 12 });
    }
});
</script>
@endpush
@endsection