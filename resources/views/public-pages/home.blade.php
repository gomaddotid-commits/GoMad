@extends('layouts.public')

@section('title', 'Beranda')
@section('meta_description', 'GoMad - Mobilitas orèng Madhurâ. Platform booking travel antar kota di Madura. Dijemput di rumah, diantar ke tujuan. Bayar online atau cash di warung terdekat.')
@section('og_image', asset('images/og-home.jpg'))

@section('content')
@php
    $cities = \App\Models\RouteStop::select('city_name')->distinct()->orderBy('city_name')->get();
    
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
@endphp

{{-- HERO SECTION --}}
<section class="relative min-h-[550px] md:min-h-[650px] flex items-center overflow-hidden">
    <div class="absolute inset-0 z-0">
        <img src="{{ asset('images/hero.png') }}" alt="GoMad Travel" class="w-full h-full object-cover object-right">
        <div class="absolute inset-0 hero-gradient"></div>
    </div>
    
    <div class="container-custom relative z-10">
        <div class="py-8 md:py-24">
            <div class="w-full md:w-1/2 lg:w-2/5">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-secondary leading-tight mb-4">
                    Mobilitas <span class="text-primary-600">orèng Madhurâ</span>
                </h1>
                <p class="text-lg md:text-xl text-gray-600 mb-8 leading-relaxed">
                    Dijemput di rumah, diantar ke tujuan. Platform booking travel antar kota di Madura yang aman, nyaman, dan terpercaya.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- FLOATING SEARCH --}}
<section id="search-form" class="floating-search">
    <div class="container-custom">
        <div class="card p-6 md:p-8 shadow-xl border-primary-100">
            <h2 class="text-xl font-bold text-secondary mb-6 text-center">Cari Jadwal Travel</h2>
            <form action="{{ route('search') }}" method="GET" class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Kota Asal</label>
                    <select name="origin" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-600 focus:border-primary-600 bg-gray-50">
                        <option value="">Semua Kota</option>
                        @foreach($cities as $city)
                        <option value="{{ $city->city_name }}">{{ $city->city_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Kota Tujuan</label>
                    <select name="destination" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-600 focus:border-primary-600 bg-gray-50">
                        <option value="">Semua Kota</option>
                        @foreach($cities as $city)
                        <option value="{{ $city->city_name }}">{{ $city->city_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal</label>
                    <input type="date" name="date" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-600 focus:border-primary-600 bg-gray-50">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Kelas</label>
                    <select name="travel_class" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-600 focus:border-primary-600 bg-gray-50">
                        <option value="">Semua</option>
                        <option value="economy">Ekonomi</option>
                        <option value="premium">Premium</option>
                        <option value="charter">Charter</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-primary-600 text-white py-2.5 rounded-xl font-semibold hover:bg-primary-700 transition text-sm">Cari Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</section>

{{-- LAYANAN GOMAD --}}
<section class="section bg-gray-50">
    <div class="container-custom">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-secondary mb-4">Layanan GoMad</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Pilih layanan yang sesuai dengan kebutuhan perjalanan Anda</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 md:gap-6">
            <div class="card p-6 text-center group"><div class="w-14 h-14 bg-primary-50 rounded-2xl flex items-center justify-center text-2xl mb-4 mx-auto group-hover:scale-110 transition-transform">🚐</div><h3 class="font-bold text-secondary mb-2">Ekonomi</h3><p class="text-xs text-gray-500 leading-relaxed">Mobil 8 seat, max overload +2, bagasi 15kg/orang</p></div>
            <div class="card p-6 text-center group"><div class="w-14 h-14 bg-primary-50 rounded-2xl flex items-center justify-center text-2xl mb-4 mx-auto group-hover:scale-110 transition-transform">🚗</div><h3 class="font-bold text-secondary mb-2">Premium</h3><p class="text-xs text-gray-500 leading-relaxed">8 seat strict, bagasi 20kg/orang, lebih nyaman</p></div>
            <div class="card p-6 text-center group"><div class="w-14 h-14 bg-primary-50 rounded-2xl flex items-center justify-center text-2xl mb-4 mx-auto group-hover:scale-110 transition-transform">🚙</div><h3 class="font-bold text-secondary mb-2">Charter</h3><p class="text-xs text-gray-500 leading-relaxed">Sewa mobil + supir, harga flat per mobil</p></div>
            <div class="card p-6 text-center group border-primary-200"><div class="w-14 h-14 bg-primary-50 rounded-2xl flex items-center justify-center text-2xl mb-4 mx-auto group-hover:scale-110 transition-transform">🏪</div><h3 class="font-bold text-secondary mb-2">Warung GoMad</h3><p class="text-xs text-gray-500 leading-relaxed">Bayar cash di warung terdekat, tanpa rekening</p></div>
        </div>
    </div>
</section>

{{-- RUTE POPULER --}}
<section class="section">
    <div class="container-custom">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-secondary mb-4">Rute Populer</h2>
            <p class="text-gray-600">Rute favorit pelanggan GoMad</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
            @forelse($popularRoutes ?? [] as $route)
            <div class="card overflow-hidden group cursor-pointer">
                @if($route->photo)
                <div class="h-40 overflow-hidden"><img src="{{  $route->photo }}" alt="{{ $route->route_name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"></div>
                @else
                <div class="h-40 bg-gradient-to-br from-primary-100 to-primary-50 flex items-center justify-center"><span class="text-4xl">🗺️</span></div>
                @endif
                <div class="p-4"><h3 class="font-bold text-secondary">{{ $route->route_name }}</h3><p class="text-sm text-gray-500 mt-1">{{ $route->origin_city }} → {{ $route->destination_city }}</p><p class="text-xs text-primary-600 font-medium mt-2">{{ $route->schedules_count ?? 0 }} jadwal tersedia</p></div>
            </div>
            @empty
            <div class="col-span-full text-center py-8 text-gray-500">Belum ada rute.</div>
            @endforelse
        </div>
    </div>
</section>

{{-- CTA DOWNLOAD APP --}}
<section class="section bg-primary-600">
    <div class="container-custom text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Download Aplikasi GoMad</h2>
        <p class="text-white/80 mb-8 max-w-xl mx-auto">Dapatkan pengalaman terbaik dengan aplikasi GoMad.</p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <a href="#" class="bg-white text-primary-600 px-8 py-4 rounded-2xl font-bold hover:bg-gray-100 transition inline-flex items-center gap-3 justify-center"><span class="text-2xl">▶</span><div class="text-left"><div class="text-xs">GET IT ON</div><div class="text-sm">Google Play</div></div></a>
            <a href="#" class="bg-white text-primary-600 px-8 py-4 rounded-2xl font-bold hover:bg-gray-100 transition inline-flex items-center gap-3 justify-center"><span class="text-2xl">🍎</span><div class="text-left"><div class="text-xs">DOWNLOAD ON</div><div class="text-sm">App Store</div></div></a>
        </div>
    </div>
</section>

{{-- METODE PEMBAYARAN --}}
<section class="section bg-gray-50">
    <div class="container-custom">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-secondary mb-4">Metode Pembayaran</h2>
            <p class="text-gray-600">Bayar dengan mudah melalui berbagai metode</p>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            {{-- Pembayaran Online - Logo Slider --}}
            <div class="card p-6 md:p-8 overflow-hidden">
                <div class="text-center mb-6">
                    <div class="text-4xl mb-3">💳</div>
                    <h3 class="font-bold text-xl text-secondary mb-2">Pembayaran Online</h3>
                    <p class="text-sm text-gray-500">Didukung oleh Midtrans dengan berbagai pilihan</p>
                </div>
                
                <div class="mb-6">
                    <p class="text-xs font-medium text-gray-500 uppercase mb-3 text-center">Transfer Bank</p>
                    <div class="overflow-hidden relative w-full" x-data="logoSlider()">
                        <div class="flex gap-3 md:gap-4 transition-transform duration-1000" :style="'transform: translateX(-' + offset + 'px)'">
                            <template x-for="i in 3" :key="i">
                                <template x-for="logo in logos" :key="logo.name + '-' + i">
                                    <div class="flex-shrink-0 w-16 h-16 md:w-20 md:h-20 bg-white rounded-2xl border border-gray-100 flex items-center justify-center p-2 md:p-3 hover:shadow-md transition">
                                        <img :src="logo.src" :alt="logo.name" class="max-w-full max-h-full object-contain" :title="logo.name">
                                    </div>
                                </template>
                            </template>
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase mb-3 text-center">E-Wallet & QRIS</p>
                    <div class="overflow-hidden relative w-full" x-data="ewalletSlider()">
                        <div class="flex gap-3 md:gap-4 transition-transform duration-1000" :style="'transform: translateX(-' + offset + 'px)'">
                            <template x-for="i in 3" :key="i">
                                <template x-for="logo in logos" :key="logo.name + '-' + i">
                                    <div class="flex-shrink-0 w-16 h-16 md:w-20 md:h-20 bg-white rounded-2xl border border-gray-100 flex items-center justify-center p-2 md:p-3 hover:shadow-md transition">
                                        <img :src="logo.src" :alt="logo.name" class="max-w-full max-h-full object-contain" :title="logo.name">
                                    </div>
                                </template>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Warung GoMad - Map --}}
            <div class="card p-6 md:p-8">
                <div class="text-center mb-6">
                    <div class="text-4xl mb-3">🏪</div>
                    <h3 class="font-bold text-xl text-secondary mb-2">Warung GoMad</h3>
                    <p class="text-sm text-gray-500">Bayar cash di warung terdekat</p>
                </div>
                <div id="homeWarungMap" style="height: 350px; z-index: 1;" class="rounded-xl border border-gray-200 mb-4 w-full"></div>
                <p class="text-xs text-gray-500 text-center">{{ $mapWarungs->count() }}+ Warung GoMad tersebar di Madura dan kota tujuan</p>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
// Bank Logo Slider
function logoSlider() {
    return {
        offset: 0,
        speed: 0.4,
        logos: [
            { name: 'BCA', src: '{{ asset("images/banks/bca.svg") }}' },
            { name: 'BNI', src: '{{ asset("images/banks/bni.svg") }}' },
            { name: 'BRI', src: '{{ asset("images/banks/bri.svg") }}' },
            { name: 'Mandiri', src: '{{ asset("images/banks/mandiri.svg") }}' },
            { name: 'BSI', src: '{{ asset("images/banks/bsi.svg") }}' },
            { name: 'CIMB Niaga', src: '{{ asset("images/banks/cimb.svg") }}' },
            { name: 'Permata', src: '{{ asset("images/banks/permata.svg") }}' },
            { name: 'Danamon', src: '{{ asset("images/banks/danamon.svg") }}' },
        ],
        init() { this.animate(); },
        animate() {
            setInterval(() => {
                this.offset += this.speed;
                // Reset based on logo width + gap: mobile 64+12=76, desktop 80+16=96
                const logoWidth = window.innerWidth < 768 ? 76 : 96;
                if (this.offset >= this.logos.length * logoWidth) this.offset = 0;
            }, 20);
        }
    }
}

function ewalletSlider() {
    return {
        offset: 0,
        speed: 0.4,
        logos: [
            { name: 'GoPay', src: '{{ asset("images/ewallet/gopay.svg") }}' },
            { name: 'OVO', src: '{{ asset("images/ewallet/ovo.svg") }}' },
            { name: 'DANA', src: '{{ asset("images/ewallet/dana.svg") }}' },
            { name: 'ShopeePay', src: '{{ asset("images/ewallet/shopeepay.svg") }}' },
            { name: 'QRIS', src: '{{ asset("images/ewallet/qris.svg") }}' },
            { name: 'LinkAja', src: '{{ asset("images/ewallet/linkaja.svg") }}' },
            { name: 'SPay', src: '{{ asset("images/ewallet/spay.svg") }}' },
        ],
        init() { this.offset = this.logos.length * (window.innerWidth < 768 ? 76 : 96); this.animateReverse(); },
        animateReverse() {
            setInterval(() => {
                this.offset -= this.speed;
                const logoWidth = window.innerWidth < 768 ? 76 : 96;
                if (this.offset <= 0) this.offset = this.logos.length * logoWidth;
            }, 20);
        }
    }
}

// Home Warung Map
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
    var count = 0;
    
    warungs.forEach(function(w) {
        var lat = parseFloat(w.latitude);
        var lng = parseFloat(w.longitude);
        
        if (isNaN(lat) || isNaN(lng)) return;
        
        var warungIcon = L.divIcon({
            html: '<div style="background:#16a34a;color:white;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;border:3px solid white;box-shadow:0 2px 6px rgba(0,0,0,0.3);">🏪</div>',
            className: '',
            iconSize: [32, 32],
            iconAnchor: [16, 16],
        });
        
        L.marker([lat, lng], { icon: warungIcon })
            .addTo(map)
            .bindPopup(
                '<div style="min-width:160px;">' +
                    '<strong>' + (w.agent_name || '') + '</strong><br>' +
                    '<span style="font-size:12px;color:#666;">' + (w.address || '') + '</span><br>' +
                    '<span style="font-size:12px;">📞 ' + (w.owner_phone || '-') + '</span><br>' +
                    '<a href="' + (w.maps_link || 'https://www.google.com/maps?q=' + lat + ',' + lng) + '" target="_blank" style="display:inline-block;margin-top:6px;background:#DC2626;color:white;padding:6px 12px;border-radius:8px;text-decoration:none;font-size:12px;font-weight:600;">🗺️ Google Maps</a>' +
                '</div>'
            );
        
        bounds.extend([lat, lng]);
        count++;
    });
    
    if (count > 0) {
        map.fitBounds(bounds, { padding: [30, 30], maxZoom: 12 });
    }
});
</script>
@endpush