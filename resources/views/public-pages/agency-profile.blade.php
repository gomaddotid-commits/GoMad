@extends('layouts.public')

@section('title', $agency->agency_name ?? 'Profil Agency')
@section('meta_description', $agency->description ?? 'Profil agency travel ' . $agency->agency_name)
@section('og_image', $agency->logo ? asset('storage/' . $agency->logo) : asset('images/og-default.jpg'))

@section('content')
@php
    function arr($data) { if (is_array($data)) return $data; if (is_string($data)) return json_decode($data, true) ?? []; return []; }
    $gallery = arr($agency->gallery ?? []);
    $socialMedia = arr($agency->social_media ?? []);
    $businessHours = arr($agency->business_hours ?? []);
    $services = arr($agency->services ?? []);
    $vehicles = $agency->vehicles()->where('is_active', true)->get();
    $drivers = $agency->drivers()->where('is_active', true)->get();
    $reviews = $agency->reviews()->with('customer')->latest()->take(10)->get();
    $totalReviews = $agency->reviews()->count();
    $avgRating = $totalReviews > 0 ? round($agency->reviews()->avg('rating'), 1) : 0;
    $activeSchedules = $agency->schedules()->where('departure_date', '>=', now()->toDateString())->where('is_active', true)->with(['route', 'vehicle'])->limit(5)->get();
@endphp

{{-- COVER --}}
<div class="h-48 md:h-64 bg-gradient-to-br from-primary-100 to-primary-50 relative overflow-hidden">
    @if($agency->cover_image)<img src="{{ asset('storage/' . $agency->cover_image) }}" alt="" class="w-full h-full object-cover">@endif
</div>

{{-- STICKY PROFILE --}}
<div class="sticky top-16 md:top-20 z-30 bg-white border-b border-gray-100 shadow-sm">
    <div class="container-custom">
        <div class="flex items-center gap-4 py-3">
            <div class="w-16 h-16 md:w-20 md:h-20 rounded-xl border-4 border-white -mt-10 bg-white shadow-lg overflow-hidden flex-shrink-0">
                @if($agency->logo)<img src="{{ asset('storage/' . $agency->logo) }}" alt="" class="w-full h-full object-cover">
                @else <div class="w-full h-full bg-primary-50 flex items-center justify-center text-2xl">🏢</div> @endif
            </div>
            <div class="flex-1 min-w-0">
                <h1 class="text-xl md:text-2xl font-bold text-secondary truncate">{{ $agency->agency_name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="text-yellow-500 text-sm">⭐ {{ $avgRating }}</span>
                    <span class="text-gray-400 text-sm">|</span>
                    <span class="text-gray-500 text-sm">{{ $totalReviews }} ulasan</span>
                    <span class="text-gray-400 text-sm">|</span>
                    <span class="text-gray-500 text-sm">{{ $agency->total_bookings }} booking</span>
                    @if($agency->is_verified)<span class="text-blue-600 text-sm font-medium ml-2">✓ Terverifikasi</span>@endif
                </div>
            </div>
        </div>
        <div class="flex gap-0 overflow-x-auto -mb-px">
            <button onclick="switchTab('tentang')" class="tab-btn whitespace-nowrap px-5 py-3 text-sm font-semibold border-b-2 border-primary-600 text-primary-600">Tentang</button>
            <button onclick="switchTab('jadwal')" class="tab-btn whitespace-nowrap px-5 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-primary-600">Jadwal</button>
            <button onclick="switchTab('armada')" class="tab-btn whitespace-nowrap px-5 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-primary-600">Armada</button>
            <button onclick="switchTab('review')" class="tab-btn whitespace-nowrap px-5 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-primary-600">Ulasan</button>
            <button onclick="switchTab('galeri')" class="tab-btn whitespace-nowrap px-5 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-primary-600">Galeri</button>
        </div>
    </div>
</div>

{{-- CONTENT --}}
<div class="section !pt-8">
    <div class="container-custom">
        {{-- Tentang --}}
        <div id="tab-tentang" class="tab-content">
            <div class="grid lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <h2 class="text-xl font-bold text-secondary mb-4">Tentang {{ $agency->agency_name }}</h2>
                    <p class="text-gray-600 leading-relaxed mb-6">{{ $agency->description ?? 'Belum ada deskripsi.' }}</p>
                    @if(!empty($services))
                    <h3 class="font-bold text-secondary mb-3">Layanan Tambahan</h3>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($services as $key => $value)
                        @if($value)<div class="flex items-center gap-2 text-sm text-gray-600"><span class="text-green-500">✓</span><span class="capitalize">{{ str_replace('_', ' ', $key) }}</span></div>@endif
                        @endforeach
                    </div>
                    @endif
                </div>
                <div class="space-y-4">
                    <div class="card p-4"><h4 class="font-bold text-secondary mb-3">Kontak</h4>
                        @if($agency->contact_person)<p class="text-sm"><strong>Person:</strong> {{ $agency->contact_person }}</p>@endif
                        @if($agency->contact_alternate)<p class="text-sm"><strong>HP:</strong> {{ $agency->contact_alternate }}</p>@endif
                        @if($agency->email_alternate)<p class="text-sm"><strong>Email:</strong> {{ $agency->email_alternate }}</p>@endif
                    </div>
                    @if(!empty($businessHours))
                    <div class="card p-4"><h4 class="font-bold text-secondary mb-3">Jam Operasional</h4>
                        @foreach($businessHours as $day => $hours)
                        <div class="flex justify-between text-sm py-1 border-b border-gray-100 last:border-0"><span class="capitalize">{{ $day }}</span><span class="text-gray-600">{{ $hours }}</span></div>
                        @endforeach
                    </div>
                    @endif
                    @if(!empty($socialMedia))
                    <div class="card p-4"><h4 class="font-bold text-secondary mb-3">Sosial Media</h4>
                        @foreach($socialMedia as $platform => $link)
                        @if($link)<a href="{{ $link }}" target="_blank" class="block text-sm text-blue-600 hover:underline capitalize">{{ $platform }}</a>@endif
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Jadwal --}}
        <div id="tab-jadwal" class="tab-content" style="display:none;">
            <h2 class="text-xl font-bold text-secondary mb-6">Jadwal Aktif</h2>
            @if($activeSchedules->isNotEmpty())
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($activeSchedules as $s)
                <div class="card p-5">
                    <p class="font-bold text-secondary">{{ $s->route->route_name }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ $s->departure_date->format('d M Y') }} {{ $s->departure_time }}</p>
                    <p class="text-sm text-gray-500">{{ $s->vehicle->plate_number ?? '-' }}</p>
                    <p class="font-bold text-primary-600 mt-2">Rp {{ number_format($s->price_per_seat, 0, ',', '.') }}/orang</p>
                    @auth<a href="{{ route('customer.booking.create', $s) }}" class="btn-primary text-sm py-2 mt-3 inline-block">Booking</a>
                    @else<a href="{{ route('login') }}" class="btn-outline text-sm py-2 mt-3 inline-block">Login</a>@endauth
                </div>
                @endforeach
            </div>
            @else<p class="text-gray-500">Tidak ada jadwal aktif.</p>@endif
        </div>

        {{-- Armada --}}
        <div id="tab-armada" class="tab-content" style="display:none;">
            <h2 class="text-xl font-bold text-secondary mb-6">Kendaraan</h2>
            @if($vehicles->isNotEmpty())
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                @foreach($vehicles as $v)
                <div class="card p-5 text-center">
                    @if($v->vehicle_image)<img src="{{ asset('storage/' . $v->vehicle_image) }}" alt="" class="w-full h-32 object-cover rounded-xl mb-3">
                    @else <div class="w-full h-32 bg-gray-100 rounded-xl flex items-center justify-center text-4xl mb-3">🚐</div>@endif
                    <p class="font-bold text-secondary">{{ $v->plate_number }}</p>
                    <p class="text-sm text-gray-500">{{ $v->brand }} {{ $v->model }} ({{ $v->year }})</p>
                    <p class="text-sm text-gray-500">{{ $v->capacity }} seat | {{ ucfirst($v->type) }}</p>
                </div>
                @endforeach
            </div>
            @else<p class="text-gray-500">Belum ada kendaraan.</p>@endif
            <h2 class="text-xl font-bold text-secondary mb-6">Driver</h2>
            @if($drivers->isNotEmpty())
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($drivers as $d)
                <div class="card p-5 text-center">
                    <div class="w-20 h-20 rounded-full bg-primary-50 mx-auto mb-3 flex items-center justify-center text-3xl overflow-hidden">
                        @if($d->avatar_url)<img src="{{ asset('storage/' . $d->avatar_url) }}" alt="" class="w-full h-full object-cover">@else 👤@endif
                    </div>
                    <p class="font-bold text-secondary">{{ $d->name }}</p>
                    <p class="text-sm text-gray-500">{{ $d->phone ?? '-' }}</p>
                </div>
                @endforeach
            </div>
            @else<p class="text-gray-500">Belum ada driver.</p>@endif
        </div>

        {{-- Ulasan --}}
        <div id="tab-review" class="tab-content" style="display:none;">
            <div class="flex items-center gap-4 mb-6"><h2 class="text-xl font-bold text-secondary">Ulasan</h2><span class="text-yellow-500 text-lg">{{ $avgRating }} / 5</span><span class="text-gray-500 text-sm">({{ $totalReviews }})</span></div>
            @if($reviews->isNotEmpty())
            <div class="space-y-4">
                @foreach($reviews as $r)
                <div class="card p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-sm font-bold">{{ strtoupper(substr($r->customer->name ?? '?', 0, 1)) }}</div>
                            <div><p class="font-semibold text-sm">{{ $r->customer->name ?? 'Anonim' }}</p>
                                <p class="text-yellow-500 text-xs">@for($i=1;$i<=5;$i++){{ $i<=$r->rating?'★':'☆' }}@endfor</p></div>
                        </div>
                        <span class="text-xs text-gray-400">{{ $r->created_at->format('d M Y') }}</span>
                    </div>
                    @if($r->review)<p class="text-sm text-gray-600 mt-2">{{ $r->review }}</p>@endif
                </div>
                @endforeach
            </div>
            @else<p class="text-gray-500">Belum ada ulasan.</p>@endif
        </div>

        {{-- Galeri --}}
        <div id="tab-galeri" class="tab-content" style="display:none;">
            <h2 class="text-xl font-bold text-secondary mb-6">Galeri</h2>
            @if(!empty($gallery))
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($gallery as $photo)<img src="{{ asset('storage/' . $photo) }}" alt="" class="w-full h-48 object-cover rounded-xl hover:shadow-lg transition">@endforeach
            </div>
            @else<p class="text-gray-500">Belum ada foto.</p>@endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.getElementById('tab-' + tabName).style.display = 'block';
    document.querySelectorAll('.tab-btn').forEach(btn => { btn.classList.remove('border-primary-600', 'text-primary-600'); btn.classList.add('border-transparent', 'text-gray-500'); });
    event.target.classList.add('border-primary-600', 'text-primary-600'); event.target.classList.remove('border-transparent', 'text-gray-500');
    localStorage.setItem('agencyActiveTab', tabName);
}
document.addEventListener('DOMContentLoaded', function() {
    var activeTab = localStorage.getItem('agencyActiveTab') || 'tentang';
    document.querySelectorAll('.tab-btn').forEach(b => { if (b.textContent.trim().toLowerCase().includes(activeTab)) b.click(); });
});
</script>
@endpush
@endsection