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
                {{-- Kota Asal (searchable) --}}
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                        Kota Asal <span class="text-[#C1121F]">*</span>
                    </label>
                    <div class="relative">
                        <input type="hidden" name="origin_city_code" :value="origin">
                        <div class="flex items-center border-b-2 border-[#E5E5E5] focus-within:border-[#C1121F] transition">
                            <input type="text" x-model="originQuery"
                                   @focus="originOpen = true" @input="originOpen = true" @keydown.escape="originOpen = false"
                                   :placeholder="origin ? selectedOriginName : 'Ketik untuk cari kota asal…'"
                                   class="w-full px-0 py-2 bg-transparent outline-none text-[#111111] text-sm">
                            <button type="button" x-show="origin" @mousedown.prevent="clearOrigin()"
                                    class="text-gray-400 hover:text-[#C1121F] px-1 text-sm shrink-0">✕</button>
                        </div>
                        <div x-show="originOpen" x-cloak x-transition
                             class="absolute z-50 mt-1 w-full max-h-64 overflow-auto bg-white border border-[#E5E5E5] rounded-[12px] shadow-lg">
                            <template x-for="city in originFiltered" :key="city.code">
                                <button type="button" @mousedown.prevent="selectOrigin(city.code)"
                                        class="w-full text-left px-4 py-2 hover:bg-[#F5F5F5] flex items-center justify-between"
                                        :class="origin === city.code ? 'bg-[#C1121F]/5' : ''">
                                    <span>
                                        <span class="block font-medium text-[#111111] text-sm" x-text="city.name"></span>
                                        <span class="block text-[10px] text-gray-400" x-text="city.province"></span>
                                    </span>
                                    <span x-show="origin === city.code" class="text-[#C1121F] text-sm">✓</span>
                                </button>
                            </template>
                            <div x-show="originFiltered.length === 0" class="px-4 py-3 text-sm text-gray-500">Tidak ada kota yang cocok.</div>
                        </div>
                    </div>
                </div>

                {{-- Kota Tujuan (searchable) --}}
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">
                        Kota Tujuan <span class="text-[#C1121F]">*</span>
                    </label>
                    <div class="relative">
                        <input type="hidden" name="destination_city_code" :value="destination">
                        <div class="flex items-center border-b-2 border-[#E5E5E5] focus-within:border-[#C1121F] transition">
                            <input type="text" x-model="destQuery"
                                   @focus="destOpen = true" @input="destOpen = true" @keydown.escape="destOpen = false"
                                   :placeholder="destination ? selectedDestName : 'Ketik untuk cari kota tujuan…'"
                                   class="w-full px-0 py-2 bg-transparent outline-none text-[#111111] text-sm">
                            <button type="button" x-show="destination" @mousedown.prevent="clearDest()"
                                    class="text-gray-400 hover:text-[#C1121F] px-1 text-sm shrink-0">✕</button>
                        </div>
                        <div x-show="destOpen" x-cloak x-transition
                             class="absolute z-50 mt-1 w-full max-h-64 overflow-auto bg-white border border-[#E5E5E5] rounded-[12px] shadow-lg">
                            <template x-for="city in destFiltered" :key="city.code">
                                <button type="button" @mousedown.prevent="selectDest(city.code)"
                                        class="w-full text-left px-4 py-2 hover:bg-[#F5F5F5] flex items-center justify-between"
                                        :class="destination === city.code ? 'bg-[#C1121F]/5' : ''">
                                    <span>
                                        <span class="block font-medium text-[#111111] text-sm" x-text="city.name"></span>
                                        <span class="block text-[10px] text-gray-400" x-text="city.province"></span>
                                    </span>
                                    <span x-show="destination === city.code" class="text-[#C1121F] text-sm">✓</span>
                                </button>
                            </template>
                            <div x-show="destFiltered.length === 0" class="px-4 py-3 text-sm text-gray-500">Tidak ada kota yang cocok.</div>
                        </div>
                    </div>
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

        {{-- Pilih Stop (searchable) --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm" x-show="origin && destination">
            <h2 class="font-bold text-lg text-[#111111] mb-2">🛑 Pilih Kota Stop (Opsional)</h2>
            <p class="text-sm text-gray-500 mb-4 font-light">Cari kota mana saja untuk dijadikan pemberhentian:</p>

            {{-- Search box --}}
            <div class="relative mb-4">
                <input type="text" x-model="stopQuery" placeholder="🔍 Cari kota stop..."
                       @input="searchStops()"
                       @keydown.escape="stopOpen = false"
                       class="w-full px-4 py-2.5 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] focus:ring-2 focus:ring-[#C1121F]/20 outline-none text-[#111111] text-sm transition">
                <div x-show="stopOpen && stopResults.length > 0" x-cloak x-transition
                     class="absolute z-50 mt-1 w-full max-h-64 overflow-auto bg-white border border-[#E5E5E5] rounded-[12px] shadow-lg">
                    <template x-for="city in stopResults" :key="city.code">
                        <button type="button" @mousedown.prevent="addStop(city.code)"
                                class="w-full text-left px-4 py-2.5 hover:bg-[#F5F5F5] flex items-center justify-between"
                                :class="selectedStops.includes(city.code) ? 'bg-[#C1121F]/5' : ''">
                            <span>
                                <span class="block font-medium text-[#111111] text-sm" x-text="city.name"></span>
                                <span class="block text-[10px] text-gray-400" x-text="city.province"></span>
                            </span>
                            <span x-show="selectedStops.includes(city.code)" class="text-[#C1121F] text-sm">✓</span>
                        </button>
                    </template>
                </div>
                <div x-show="stopOpen && stopQuery.length >= 2 && stopResults.length === 0" x-cloak
                     class="absolute z-50 mt-1 w-full bg-white border border-[#E5E5E5] rounded-[12px] shadow-lg px-4 py-3 text-sm text-gray-500">
                    Tidak ada kota yang cocok.
                </div>
            </div>

            {{-- Selected stops chips --}}
            <div class="flex flex-wrap gap-2 mb-3" x-show="selectedStops.length > 0">
                <template x-for="code in selectedStops" :key="code">
                    <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-[#C1121F]/5 text-[#C1121F] rounded-full text-sm">
                        <span x-text="cityName(code)"></span>
                        <input type="hidden" name="stop_city_codes[]" :value="code">
                        <button type="button" @click="removeStop(code)" class="text-[#C1121F] hover:text-red-700 font-bold">✕</button>
                    </span>
                </template>
            </div>
            <p class="text-xs text-gray-400 font-light" x-show="selectedStops.length === 0">
                Belum ada stop dipilih. Stop akan diurutkan otomatis berdasarkan jarak dari kota asal.
            </p>
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
        cities: @json($cities->map(fn($c) => ['code' => $c->code, 'name' => $c->name, 'province' => $c->province->name ?? ''])),

        // ── Searchable combobox: asal & tujuan ──
        originQuery: '',
        originOpen: false,
        destQuery: '',
        destOpen: false,

        // ── Searchable stop selector ──
        stopQuery: '',
        stopOpen: false,
        stopResults: [],

        get selectedOrigin() { return this.cities.find(c => c.code === this.origin) || null; },
        get selectedOriginName() { return this.selectedOrigin ? this.selectedOrigin.name : ''; },
        get selectedDest() { return this.cities.find(c => c.code === this.destination) || null; },
        get selectedDestName() { return this.selectedDest ? this.selectedDest.name : ''; },

        get originFiltered() {
            const q = (this.originQuery || '').toLowerCase();
            return this.cities
                .filter(c => c.code !== this.destination && (!q || (c.name + ' ' + c.province).toLowerCase().includes(q)))
                .slice(0, 60);
        },
        get destFiltered() {
            const q = (this.destQuery || '').toLowerCase();
            return this.cities
                .filter(c => c.code !== this.origin && (!q || (c.name + ' ' + c.province).toLowerCase().includes(q)))
                .slice(0, 60);
        },
        selectOrigin(code) { this.origin = code; this.originQuery = ''; this.originOpen = false; },
        clearOrigin() { this.origin = ''; this.originQuery = ''; this.originOpen = true; },
        selectDest(code) { this.destination = code; this.destQuery = ''; this.destOpen = false; },
        clearDest() { this.destination = ''; this.destQuery = ''; this.destOpen = true; },

        cityName(code) {
            const city = this.cities.find(c => c.code === code);
            return city ? city.name : code;
        },

        async searchStops() {
            const q = (this.stopQuery || '').trim();
            if (q.length < 2) {
                this.stopResults = [];
                this.stopOpen = false;
                return;
            }
            this.stopOpen = true;
            try {
                const res = await fetch(`/api/v1/cities/search?q=${encodeURIComponent(q)}`);
                const data = await res.json();
                this.stopResults = (data.data || []).filter(c =>
                    c.code !== this.origin && c.code !== this.destination
                );
            } catch (e) {
                this.stopResults = [];
            }
        },
        addStop(code) {
            if (!this.selectedStops.includes(code)) {
                this.selectedStops.push(code);
            }
            this.stopQuery = '';
            this.stopResults = [];
            this.stopOpen = false;
        },
        removeStop(code) {
            this.selectedStops = this.selectedStops.filter(c => c !== code);
        },

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