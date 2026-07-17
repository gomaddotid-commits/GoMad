@extends('layouts.admin')

@section('title', 'Tambah Rute')
@section('content')
<div x-data="routeForm()">
    <h1 class="text-lg font-bold text-[#111111] mb-6">Tambah Rute Baru</h1>

    <form action="{{ route('admin.routes.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        {{-- Foto Rute --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-2">🖼️ Foto Rute</label>
            <div class="flex items-center gap-4">
                <div class="w-40 h-32 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] flex items-center justify-center text-4xl overflow-hidden" id="photoPreview">
                    <span>🗺️</span>
                </div>
                <div class="flex-1">
                    <input type="file" name="photo" accept="image/*" class="w-full text-sm" onchange="previewPhoto(event)">
                    <p class="text-[10px] text-gray-400 mt-1 font-light">Format: JPG, PNG, WEBP. Max 5MB.</p>
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
                    <select name="origin_city_code" x-model="origin" @change="loadAvailableStops()" 
                            class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                        <option value="">Pilih Kota Asal</option>
                        @foreach($cities as $city)
                        <option value="{{ $city->code }}">{{ $city->name }} ({{ $city->province->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                        Kota Tujuan <span class="text-[#C1121F]">*</span>
                    </label>
                    <select name="destination_city_code" x-model="destination" @change="loadAvailableStops()"
                            class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
                        <option value="">Pilih Kota Tujuan</option>
                        @foreach($cities as $city)
                        <option value="{{ $city->code }}" :disabled="origin === '{{ $city->code }}'">{{ $city->name }} ({{ $city->province->name }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Nama Rute (auto-generated) --}}
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Nama Rute</label>
                <input type="text" name="route_name" x-model="routeName" 
                       class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition"
                       placeholder="Auto-generated dari kota asal & tujuan">
            </div>
        </div>

        {{-- Pilih Stop --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm" x-show="origin && destination">
            <h2 class="font-bold text-lg text-[#111111] mb-4">🛑 Pilih Kota Stop (Opsional)</h2>
            <p class="text-sm text-gray-500 mb-4 font-light">Kota yang tersedia di antara rute:</p>

            <div class="grid md:grid-cols-3 gap-3" x-show="availableStops.length > 0">
                <template x-for="city in availableStops" :key="city.code">
                    <label class="flex items-center gap-3 p-4 border-2 border-[#E5E5E5] rounded-[12px] cursor-pointer hover:border-[#C1121F] transition"
                           :class="selectedStops.includes(city.code) ? 'border-[#C1121F] bg-[#C1121F]/5' : ''">
                        <input type="checkbox" name="stop_city_codes[]" :value="city.code" x-model="selectedStops"
                               class="w-4 h-4 text-[#C1121F] rounded border-[#E5E5E5] focus:ring-[#C1121F]">
                        <div>
                            <span class="text-sm font-semibold text-[#111111]" x-text="city.name"></span>
                            <span class="text-[10px] text-gray-400 block font-light" x-text="city.province?.name"></span>
                        </div>
                    </label>
                </template>
            </div>

            <div x-show="availableStops.length === 0 && origin && destination" class="text-center py-4 text-gray-500 font-light">
                Tidak ada kota di antara rute ini.
            </div>
        </div>

        {{-- Preview Rute --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm" x-show="origin && destination">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">📋 Preview Rute</h3>
            <div class="flex items-center gap-2 text-sm font-mono" x-text="previewRoute"></div>
        </div>

        {{-- Pengaturan Lainnya --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">⚙️ Pengaturan</h3>
            
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Maks Harga (Rp)</label>
                    <input type="number" name="max_price" value="{{ old('max_price') }}"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">COD Tersedia?</label>
                    <select name="cod_available" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                        <option value="0">Tidak</option>
                        <option value="1">Ya</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Min Deposit COD (Rp)</label>
                    <input type="number" name="cod_min_deposit" value="{{ old('cod_min_deposit', 500000) }}"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition">
                </div>
            </div>

            {{-- Metode Pembayaran --}}
            <div class="mt-4">
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-2">Metode Pembayaran</label>
                <div class="grid grid-cols-3 gap-3">
                    @foreach(['midtrans' => '💳 Online', 'cash' => '🏪 Warung', 'cod' => '🚗 COD'] as $val => $label)
                    <label class="flex items-center gap-2 p-3 border border-[#E5E5E5] rounded-[12px] cursor-pointer hover:bg-[#F5F5F5]">
                        <input type="checkbox" name="payment_methods[]" value="{{ $val }}" checked
                               class="w-4 h-4 text-[#C1121F] rounded border-[#E5E5E5] focus:ring-[#C1121F]">
                        <span class="text-sm font-medium text-[#111111]">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Deskripsi</label>
                <textarea name="description" rows="2" class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] transition"
                          placeholder="Deskripsi singkat...">{{ old('description') }}</textarea>
            </div>
        </div>

        <button type="submit" class="w-full btn-gomad-primary py-3 rounded-[12px] font-semibold">
            💾 SIMPAN RUTE
        </button>
    </form>
</div>

@push('scripts')
<script>
function routeForm() {
    return {
        origin: '{{ old('origin_city_code', '') }}',
        destination: '{{ old('destination_city_code', '') }}',
        selectedStops: @json(old('stop_city_codes', [])),
        availableStops: [],
        cities: @json($cities->map(fn($c) => ['code' => $c->code, 'name' => $c->name])),

        get routeName() {
            if (!this.origin || !this.destination) return '';
            const originCity = this.cities.find(c => c.code === this.origin);
            const destCity = this.cities.find(c => c.code === this.destination);
            if (originCity && destCity) return `${originCity.name} - ${destCity.name}`;
            return '';
        },

        get previewRoute() {
            let stops = [this.origin, ...this.selectedStops, this.destination];
            return stops.map(code => {
                const city = this.cities.find(c => c.code === code);
                return city ? city.name : code;
            }).join(' → ');
        },

        async loadAvailableStops() {
            if (!this.origin || !this.destination) return;
            try {
                const res = await fetch(`/api/v1/route-stops/available?origin=${this.origin}&destination=${this.destination}`);
                const data = await res.json();
                this.availableStops = data.data || [];
            } catch (e) {
                this.availableStops = [];
            }
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
</script>
@endpush
@endsection