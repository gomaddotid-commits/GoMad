@extends('layouts.public')

@section('title', $agency->agency_name ?? 'Profil Agency')
@section('meta_description', $agency->description ?? 'Profil agency travel ' . $agency->agency_name)
@section('og_image', $agency->logo ?  $agency->logo : asset('images/og-default.jpg'))

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
<div class="h-48 md:h-64 bg-[#F9FAFB] relative overflow-hidden border-b border-[#E5E7EB]">
    @if($agency->cover_image)<img src="{{ $agency->cover_image }}" alt="" class="w-full h-full object-cover">@endif
</div>

{{-- STICKY PROFILE --}}
<div class="sticky top-16 md:top-20 z-30 bg-white border-b border-[#E5E7EB] shadow-gomad">
    <div class="container-magazine">
        <div class="flex items-center gap-4 py-3">
            {{-- Logo Melayang --}}
            <div class="w-16 h-16 md:w-20 md:h-20 rounded-[12px] border-2 border-white -mt-10 bg-white shadow-gomad overflow-hidden flex-shrink-0">
                @if($agency->logo)<img src="{{ $agency->logo }}" alt="" class="w-full h-full object-cover">
                @else <div class="w-full h-full bg-[#F9FAFB] flex items-center justify-center text-2xl text-[#BA1826]">🏢</div> @endif
            </div>
            
            <div class="flex-1 min-w-0">
                <h1 class="text-xl md:text-2xl font-bold text-[#111827] truncate">{{ $agency->agency_name }}</h1>
                <div class="flex items-center gap-2 mt-1 flex-wrap text-xs md:text-sm">
                    <span class="text-gray-500 font-mono">⭐ {{ $avgRating }}</span>
                    <span class="text-gray-300">|</span>
                    <span class="text-gray-500 font-light">{{ $totalReviews }} ulasan</span>
                    <span class="text-gray-300">|</span>
                    <span class="text-gray-500 font-light">{{ $agency->total_bookings }} booking</span>
                    @if($agency->is_verified)<span class="text-[#BA1826] font-mono uppercase tracking-wider ml-1 text-xs">✓ Terverifikasi</span>@endif
                </div>
            </div>
        </div>
        
        {{-- Tab Menu --}}
        <div class="flex gap-0 overflow-x-auto -mb-px">
            <button onclick="switchTab('tentang')" class="tab-btn whitespace-nowrap px-5 py-3 text-sm font-medium border-b-2 border-[#BA1826] text-[#BA1826] transition-colors">Tentang</button>
            <button onclick="switchTab('jadwal')" class="tab-btn whitespace-nowrap px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-[#BA1826] transition-colors">Jadwal</button>
            <button onclick="switchTab('armada')" class="tab-btn whitespace-nowrap px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-[#BA1826] transition-colors">Armada</button>
            <button onclick="switchTab('review')" class="tab-btn whitespace-nowrap px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-[#BA1826] transition-colors">Ulasan</button>
            <button onclick="switchTab('galeri')" class="tab-btn whitespace-nowrap px-5 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-[#BA1826] transition-colors">Galeri</button>
        </div>
    </div>
</div>

{{-- CONTENT --}}
<div class="section !pt-8">
    <div class="container-magazine">
        
        {{-- TAB: Tentang --}}
        <div id="tab-tentang" class="tab-content">
            <div class="grid lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <h2 class="text-xl font-bold text-[#111827] mb-4">Tentang {{ $agency->agency_name }}</h2>
                    <p class="text-gray-500 font-light leading-relaxed mb-6">{{ $agency->description ?? 'Belum ada deskripsi.' }}</p>
                    
                    @if(!empty($services))
                    <h3 class="font-mono uppercase tracking-wider text-sm font-bold text-[#111827] mb-3">Layanan Tambahan</h3>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($services as $key => $value)
                        @if($value)<div class="flex items-center gap-2 text-sm text-[#111827]"><span class="text-[#BA1826]">✓</span><span class="capitalize font-light">{{ str_replace('_', ' ', $key) }}</span></div>@endif
                        @endforeach
                    </div>
                    @endif
                </div>
                
                {{-- Sidebar Info --}}
                <div class="space-y-4">
                    <div class="card-gomad p-4 border-[#E5E7EB]">
                        <h4 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111827] mb-3">Kontak</h4>
                        @if($agency->contact_person)<p class="text-sm text-[#111827]"><span class="text-gray-400 font-mono uppercase text-[10px]">Person:</span> {{ $agency->contact_person }}</p>@endif
                        @if($agency->contact_alternate)<p class="text-sm text-[#111827]"><span class="text-gray-400 font-mono uppercase text-[10px]">HP:</span> {{ $agency->contact_alternate }}</p>@endif
                        @if($agency->email_alternate)<p class="text-sm text-[#111827]"><span class="text-gray-400 font-mono uppercase text-[10px]">Email:</span> {{ $agency->email_alternate }}</p>@endif
                    </div>
                    
                    @if(!empty($businessHours))
                    <div class="card-gomad p-4 border-[#E5E7EB]">
                        <h4 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111827] mb-3">Jam Operasional</h4>
                        @foreach($businessHours as $day => $hours)
                        <div class="flex justify-between text-sm py-1 border-b border-[#F9FAFB] last:border-0">
                            <span class="capitalize text-[#111827] font-medium">{{ $day }}</span>
                            <span class="text-gray-500 font-light">{{ $hours }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    
                    @if(!empty($socialMedia))
                    <div class="card-gomad p-4 border-[#E5E7EB]">
                        <h4 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111827] mb-3">Sosial Media</h4>
                        @foreach($socialMedia as $platform => $link)
                        @if($link)<a href="{{ $link }}" target="_blank" class="block text-sm text-[#BA1826] hover:underline capitalize font-medium">{{ $platform }}</a>@endif
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- TAB: Jadwal --}}
        <div id="tab-jadwal" class="tab-content" style="display:none;">
            <h2 class="text-xl font-bold text-[#111827] mb-6">Jadwal Aktif</h2>
            @if($activeSchedules->isNotEmpty())
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($activeSchedules as $s)
                <div class="card-gomad p-5 border-[#E5E7EB] hover:border-[#BA1826] transition-colors">
                    <p class="font-bold text-[#111827]">{{ $s->route->route_name }}</p>
                    <p class="text-sm text-gray-500 font-light mt-1 font-mono">{{ $s->departure_date->format('d M Y') }} {{ $s->departure_time }}</p>
                    <p class="text-xs text-gray-400 font-mono uppercase tracking-wider">{{ $s->vehicle->plate_number ?? '-' }}</p>
                    <p class="font-bold text-[#BA1826] font-mono mt-2 text-lg">Rp {{ number_format($s->price_per_seat, 0, ',', '.') }}/orang</p>
                    <div class="mt-3 border-t border-[#E5E7EB] pt-3">
                        @auth<a href="{{ route('customer.booking.create', $s) }}" class="btn-gomad-primary text-sm py-2 px-6 inline-block rounded-[10px]">Booking</a>
                        @else<a href="{{ route('login') }}" class="btn-gomad-outline text-sm py-2 px-6 inline-block rounded-[10px] border-[#BA1826] text-[#BA1826] hover:bg-[#BA1826] hover:text-white">Login</a>@endauth
                    </div>
                </div>
                @endforeach
            </div>
            @else<p class="text-gray-500 font-light">Tidak ada jadwal aktif saat ini.</p>@endif
        </div>

        {{-- TAB: Armada --}}
        <div id="tab-armada" class="tab-content" style="display:none;">
            <h2 class="text-xl font-bold text-[#111827] mb-6">Kendaraan</h2>
            @if($vehicles->isNotEmpty())
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                @foreach($vehicles as $v)
                <div class="card-gomad p-5 text-center border-[#E5E7EB] hover:border-[#BA1826] transition-colors">
                    @if($v->vehicle_image)<img src="{{ $v->vehicle_image }}" alt="" class="w-full h-32 object-cover rounded-[10px] mb-3">
                    @else <div class="w-full h-32 bg-[#F9FAFB] rounded-[10px] flex items-center justify-center text-4xl mb-3">🚐</div>@endif
                    <p class="font-bold text-[#111827] font-mono">{{ $v->plate_number }}</p>
                    <p class="text-sm text-gray-500 font-light">{{ $v->brand }} {{ $v->model }} ({{ $v->year }})</p>
                    <p class="text-xs text-gray-400 font-mono uppercase tracking-wider">{{ $v->capacity }} seat | {{ ucfirst($v->type) }}</p>
                </div>
                @endforeach
            </div>
            @else<p class="text-gray-500 font-light">Belum ada kendaraan terdaftar.</p>@endif
            
            <h2 class="text-xl font-bold text-[#111827] mb-6">Driver</h2>
            @if($drivers->isNotEmpty())
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($drivers as $d)
                <div class="card-gomad p-5 text-center border-[#E5E7EB] hover:border-[#BA1826] transition-colors">
                    <div class="w-20 h-20 rounded-full bg-[#F9FAFB] mx-auto mb-3 flex items-center justify-center text-3xl overflow-hidden border border-[#E5E7EB]">
                        @if($d->avatar_url)<img src="{{ $d->avatar_url }}" alt="" class="w-full h-full object-cover">@else 👤@endif
                    </div>
                    <p class="font-bold text-[#111827]">{{ $d->name }}</p>
                    <p class="text-sm text-gray-500 font-light">{{ $d->phone ?? '-' }}</p>
                </div>
                @endforeach
            </div>
            @else<p class="text-gray-500 font-light">Belum ada driver terdaftar.</p>@endif
        </div>

        {{-- TAB: Ulasan --}}
        <div id="tab-review" class="tab-content" style="display:none;">
            <div class="flex items-center gap-4 mb-6">
                <h2 class="text-xl font-bold text-[#111827]">Ulasan</h2>
                <span class="text-gray-500 font-mono text-lg">{{ $avgRating }} / 5</span>
                <span class="text-gray-400 text-xs font-mono uppercase tracking-wider">({{ $totalReviews }})</span>
            </div>
            @if($reviews->isNotEmpty())
            <div class="space-y-4">
                @foreach($reviews as $r)
                <div class="card-gomad p-4 border-[#E5E7EB] hover:border-[#BA1826] transition-colors">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-full bg-[#F9FAFB] flex items-center justify-center text-sm font-bold border border-[#E5E7EB]">{{ strtoupper(substr($r->customer->name ?? '?', 0, 1)) }}</div>
                            <div>
                                <p class="font-semibold text-sm text-[#111827]">{{ $r->customer->name ?? 'Anonim' }}</p>
                                <p class="text-gray-400 text-xs font-mono tracking-wider">@for($i=1;$i<=5;$i++){{ $i<=$r->rating?'★':'☆' }}@endfor</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 font-mono">{{ $r->created_at->format('d M Y') }}</span>
                    </div>
                    @if($r->review)<p class="text-sm text-gray-500 font-light mt-2">{{ $r->review }}</p>@endif
                </div>
                @endforeach
            </div>
            @else<p class="text-gray-500 font-light">Belum ada ulasan.</p>@endif
        </div>

        {{-- TAB: Galeri --}}
        <div id="tab-galeri" class="tab-content" style="display:none;">
            <h2 class="text-xl font-bold text-[#111827] mb-6">Galeri</h2>
            @if(!empty($gallery))
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($gallery as $photo)
                <img src="{{ $photo }}" alt="" class="w-full h-48 object-cover rounded-[10px] border border-[#E5E7EB] hover:shadow-gomad transition-shadow cursor-pointer">
                @endforeach
            </div>
            @else<p class="text-gray-500 font-light">Belum ada foto dalam galeri.</p>@endif
        </div>
        
    </div>
</div>

@push('scripts')
<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.getElementById('tab-' + tabName).style.display = 'block';
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-[#BA1826]', 'text-[#BA1826]');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Karena kita menggunakan onclick di HTML, event.target merujuk ke tombol yang diklik
    event.target.classList.add('border-[#BA1826]', 'text-[#BA1826]');
    event.target.classList.remove('border-transparent', 'text-gray-500');
    
    localStorage.setItem('agencyActiveTab', tabName);
}

document.addEventListener('DOMContentLoaded', function() {
    var activeTab = localStorage.getItem('agencyActiveTab') || 'tentang';
    document.querySelectorAll('.tab-btn').forEach(b => { 
        if (b.textContent.trim().toLowerCase().includes(activeTab)) {
            b.click(); 
        }
    });
});
</script>
@endpush
@endsection