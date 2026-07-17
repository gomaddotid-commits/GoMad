@extends('layouts.admin')

@section('title', 'Edit Rute')
@section('content')
@php
    $cities = \App\Models\City::with('province')->orderBy('name')->get();
    
    // Dapatkan daftar city_code yang sudah jadi stop
    $existingStopCodes = $route->stops->pluck('city_code')->toArray();
    
    // Filter kota yang tersedia untuk stop
    $routeService = app(\App\Services\RouteService::class);
    $availableStopCities = $routeService->getAvailableStops(
        $route->origin_city_code,
        $route->destination_city_code
    );
    
    // Exclude kota yang sudah jadi stop
    $availableStopCities = $availableStopCities->filter(function($city) use ($existingStopCodes) {
        return !in_array($city->code, $existingStopCodes);
    })->values();
@endphp

<div x-data="routeForm()">
    <h1 class="text-lg font-bold text-[#111111] mb-6">Edit Rute</h1>

    {{-- VALIDASI ERROR --}}
    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[12px] mb-6 text-sm">
        <p class="font-medium mb-1">⚠️ Terjadi kesalahan:</p>
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-[12px] mb-6 text-sm">
        ✅ {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-[12px] mb-6 text-sm">
        ❌ {{ session('error') }}
    </div>
    @endif

    {{-- ═══════════════════════════════════════ --}}
    {{-- FORM UTAMA: UPDATE RUTE --}}
    {{-- ═══════════════════════════════════════ --}}
    <form action="{{ route('admin.routes.update', $route) }}" method="POST" enctype="multipart/form-data" id="updateRouteForm">
        @csrf 
        @method('PUT')

        <div class="space-y-6">
            {{-- Foto Rute --}}
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-2">🖼️ Foto Rute</label>
                <div class="flex items-center gap-4">
                    <div class="w-40 h-32 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] flex items-center justify-center text-4xl overflow-hidden" id="photoPreview">
                        @if($route->photo)
                        <img src="{{ $route->photo }}" alt="{{ $route->route_name }}" class="w-full h-full object-cover">
                        @else
                        <span>🗺️</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input type="file" name="photo" accept="image/*" class="w-full text-sm" onchange="previewPhoto(event)">
                        <p class="text-[10px] text-gray-400 mt-1 font-light">Biarkan kosong jika tidak ingin mengubah foto</p>
                        @if($route->photo)
                        <p class="text-[10px] text-green-600 mt-1 font-light">✅ Foto sudah diupload</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Pilih Kota Asal & Tujuan --}}
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
                <h2 class="font-bold text-lg text-[#111111] mb-4">📍 Pilih Kota</h2>
                
                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                            Kota Asal <span class="text-[#C1121F]">*</span>
                        </label>
                        <select name="origin_city_code" x-model="origin"
                                class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                            <option value="">Pilih Kota Asal</option>
                            @foreach($cities as $city)
                            <option value="{{ $city->code }}" {{ old('origin_city_code', $route->origin_city_code) == $city->code ? 'selected' : '' }}>
                                {{ $city->name }} ({{ $city->province->name }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                            Kota Tujuan <span class="text-[#C1121F]">*</span>
                        </label>
                        <select name="destination_city_code" x-model="destination"
                                class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                            <option value="">Pilih Kota Tujuan</option>
                            @foreach($cities as $city)
                            <option value="{{ $city->code }}" {{ old('destination_city_code', $route->destination_city_code) == $city->code ? 'selected' : '' }}
                                    :disabled="origin === '{{ $city->code }}'">
                                {{ $city->name }} ({{ $city->province->name }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Nama Rute --}}
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nama Rute <span class="text-[#C1121F]">*</span></label>
                    <input type="text" name="route_name" x-model="routeName" 
                           value="{{ old('route_name', $route->route_name) }}"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                </div>
            </div>

            {{-- Preview Rute --}}
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm" x-show="origin && destination">
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">📋 Preview Rute</h3>
                <div class="flex items-center gap-2 text-sm font-mono" x-text="previewRoute"></div>
                
                @if($route->distance_km)
                <div class="mt-2 text-sm text-gray-500 font-light">
                    📏 Jarak: <strong>{{ $route->distance_km }} km</strong> | 
                    ⏱️ Estimasi: <strong>{{ $route->estimated_duration }} menit</strong>
                </div>
                @endif
            </div>

            {{-- Pengaturan Lainnya --}}
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">⚙️ Pengaturan</h3>
                
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Maks Harga (Rp)</label>
                        <input type="number" name="max_price" value="{{ old('max_price', $route->max_price) }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">COD Tersedia?</label>
                        <select name="cod_available" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                            <option value="0" {{ !$route->cod_available ? 'selected' : '' }}>Tidak</option>
                            <option value="1" {{ $route->cod_available ? 'selected' : '' }}>Ya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Min Deposit COD (Rp)</label>
                        <input type="number" name="cod_min_deposit" value="{{ old('cod_min_deposit', $route->cod_min_deposit) }}"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                    </div>
                </div>

                {{-- Metode Pembayaran --}}
                <div class="mt-4">
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-2">Metode Pembayaran</label>
                    @php $selectedMethods = $route->payment_methods_array; @endphp
                    <div class="grid grid-cols-3 gap-3">
                        <label class="flex items-center gap-2 p-3 border-2 border-[#E5E5E5] rounded-[12px] cursor-pointer hover:border-[#C1121F] transition has-[:checked]:border-[#C1121F] has-[:checked]:bg-[#C1121F]/5">
                            <input type="checkbox" name="payment_methods[]" value="midtrans" 
                                   {{ in_array('midtrans', $selectedMethods) ? 'checked' : '' }}
                                   class="w-4 h-4 text-[#C1121F] rounded border-[#E5E5E5] focus:ring-[#C1121F]">
                            <span class="text-sm font-medium text-[#111111]">💳 Online</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 border-2 border-[#E5E5E5] rounded-[12px] cursor-pointer hover:border-[#C1121F] transition has-[:checked]:border-[#C1121F] has-[:checked]:bg-[#C1121F]/5">
                            <input type="checkbox" name="payment_methods[]" value="cash" 
                                   {{ in_array('cash', $selectedMethods) ? 'checked' : '' }}
                                   class="w-4 h-4 text-[#C1121F] rounded border-[#E5E5E5] focus:ring-[#C1121F]">
                            <span class="text-sm font-medium text-[#111111]">🏪 Warung</span>
                        </label>
                        <label class="flex items-center gap-2 p-3 border-2 border-[#E5E5E5] rounded-[12px] cursor-pointer hover:border-[#C1121F] transition has-[:checked]:border-[#C1121F] has-[:checked]:bg-[#C1121F]/5">
                            <input type="checkbox" name="payment_methods[]" value="cod" 
                                   {{ in_array('cod', $selectedMethods) ? 'checked' : '' }}
                                   class="w-4 h-4 text-[#C1121F] rounded border-[#E5E5E5] focus:ring-[#C1121F]">
                            <span class="text-sm font-medium text-[#111111]">🚗 COD</span>
                        </label>
                    </div>
                </div>

                {{-- Status --}}
                <div class="mt-4">
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Status</label>
                    <select name="is_active" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                        <option value="1" {{ $route->is_active ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ !$route->is_active ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <div class="mt-4">
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Deskripsi</label>
                    <textarea name="description" rows="2" class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] transition"
                              placeholder="Deskripsi singkat...">{{ old('description', $route->description) }}</textarea>
                </div>
            </div>

            {{-- TOMBOL --}}
            <div class="flex gap-4">
                <a href="{{ route('admin.routes.index') }}" class="flex-1 text-center border border-[#E5E5E5] text-gray-700 py-3 rounded-[12px] font-semibold hover:bg-[#F5F5F5] transition">
                    Batal
                </a>
                <button type="submit" class="flex-1 btn-gomad-primary py-3 rounded-[12px] font-semibold text-lg">
                    💾 UPDATE RUTE
                </button>
            </div>
        </div>
    </form>
    {{-- ⬆️ FORM UTAMA SELESAI --}}

    {{-- ═══════════════════════════════════════ --}}
    {{-- STOPS SAAT INI + TAMBAH/HAPUS STOP --}}
    {{-- ═══════════════════════════════════════ --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm mt-6">
        <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">🛑 Stops Saat Ini</h3>
        
        @if($route->stops->isEmpty())
        <p class="text-gray-500 text-center py-4 font-light">Belum ada stop.</p>
        @else
        <div class="space-y-2 mb-4">
            @foreach($route->stops as $stop)
            <div class="flex justify-between items-center bg-[#F5F5F5] border border-[#E5E5E5] px-4 py-3 rounded-[12px]">
                <div>
                    <span class="font-medium text-[#111111]">{{ $stop->city_name }}</span>
                    <span class="text-[10px] font-mono text-gray-400 ml-2">Order: {{ $stop->stop_order }}</span>
                    @if($stop->isFirst())
                    <span class="text-[10px] font-mono text-green-600 ml-2">📍 Asal</span>
                    @elseif($stop->isLast())
                    <span class="text-[10px] font-mono text-red-600 ml-2">🎯 Tujuan</span>
                    @endif
                    @if($stop->distance_from_origin)
                    <span class="text-[10px] font-mono text-gray-400 ml-2">📏 {{ $stop->distance_from_origin }} km</span>
                    @endif
                </div>
                @if(!$stop->isFirst() && !$stop->isLast())
                <button type="button" 
                        onclick="deleteStop({{ $route->id }}, {{ $stop->id }}, '{{ $stop->city_name }}')"
                        class="text-[#C1121F] text-sm hover:underline font-medium">
                    🗑️ Hapus
                </button>
                @else
                <span class="text-[10px] text-gray-400 font-mono">Wajib</span>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        {{-- FORM TERPISAH: TAMBAH STOP --}}
        <div class="bg-[#F5F5F5] border border-[#E5E5E5] p-4 rounded-[12px]">
            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-2">+ Tambah Stop</label>
            
            @if($availableStopCities->isEmpty())
            <p class="text-sm text-gray-500 font-light">
                ✅ Semua kota di antara rute sudah menjadi stop. Tidak ada kota yang bisa ditambahkan.
            </p>
            @else
            <form action="{{ route('admin.routes.stops.add', $route) }}" method="POST" class="flex gap-3">
                @csrf
                <select name="city_code" class="flex-1 px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111]" required>
                    <option value="">Pilih Kota ({{ $availableStopCities->count() }} tersedia)</option>
                    @foreach($availableStopCities as $city)
                    <option value="{{ $city->code }}">
                        {{ $city->name }} ({{ $city->province->name }}) 
                        — {{ number_format($city->distance_from_origin, 0) }} km dari asal
                    </option>
                    @endforeach
                </select>
                <button type="submit" class="bg-[#C1121F] text-white px-4 py-2 rounded-[12px] text-sm font-medium hover:bg-[#8A0F18] transition whitespace-nowrap">
                    Tambah
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- HIDDEN FORM UNTUK HAPUS STOP --}}
    <form id="deleteStopForm" action="" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>
</div>

@push('scripts')
<script>
function routeForm() {
    return {
        origin: '{{ old('origin_city_code', $route->origin_city_code) }}',
        destination: '{{ old('destination_city_code', $route->destination_city_code) }}',
        cities: @json($cities->map(fn($c) => ['code' => $c->code, 'name' => $c->name])),

        get routeName() {
            if (!this.origin || !this.destination) return '{{ $route->route_name }}';
            const originCity = this.cities.find(c => c.code === this.origin);
            const destCity = this.cities.find(c => c.code === this.destination);
            if (originCity && destCity) return `${originCity.name} - ${destCity.name}`;
            return '{{ $route->route_name }}';
        },

        get previewRoute() {
            const stops = @json($route->stops->pluck('city_name')->toArray());
            return stops.join(' → ');
        }
    }
}

function previewPhoto(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
        };
        reader.readAsDataURL(file);
    }
}

function deleteStop(routeId, stopId, cityName) {
    if (!confirm(`Hapus stop ${cityName}?`)) return;
    
    const form = document.getElementById('deleteStopForm');
    form.action = `/admin/routes/${routeId}/stops/${stopId}`;
    form.submit();
}

// Submit loading state untuk UPDATE RUTE
document.getElementById('updateRouteForm').addEventListener('submit', function() {
    const btn = this.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '⏳ Menyimpan...';
        btn.classList.add('opacity-50', 'cursor-not-allowed');
    }
});
</script>
@endpush
@endsection