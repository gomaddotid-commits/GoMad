@extends('layouts.admin')

@section('title', 'Tambah Rute')
@section('content')
<div>
    <h1 class="text-lg font-bold text-secondary mb-6">Tambah Rute Baru</h1>

    <form action="{{ route('admin.routes.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
        @csrf

        {{-- Foto Rute --}}
        <div>
            <label class="block text-sm font-medium text-secondary mb-2">🖼️ Foto Rute</label>
            <div class="flex items-center gap-4">
                <div class="w-40 h-32 bg-gray-100 rounded-xl flex items-center justify-center text-4xl overflow-hidden" id="photoPreview">
                    <span>🗺️</span>
                </div>
                <div class="flex-1">
                    <input type="file" name="photo" id="photoInput" accept="image/*"
                           class="w-full text-sm" onchange="previewPhoto(event)">
                    <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, WEBP. Max 5MB. Rekomendasi: 800x600px</p>
                </div>
            </div>
        </div>

        {{-- Informasi Dasar --}}
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Nama Rute <span class="text-red-500">*</span></label>
                <input type="text" name="route_name" value="{{ old('route_name') }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50"
                       placeholder="Sumenep - Surabaya" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Jarak (km)</label>
                <input type="number" name="distance_km" value="{{ old('distance_km') }}" step="0.01"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50">
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Kota Asal <span class="text-red-500">*</span></label>
                <input type="text" name="origin_city" value="{{ old('origin_city') }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Kota Tujuan <span class="text-red-500">*</span></label>
                <input type="text" name="destination_city" value="{{ old('destination_city') }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Estimasi Durasi (menit)</label>
                <input type="number" name="estimated_duration" value="{{ old('estimated_duration') }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" placeholder="300">
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Status</label>
                <select name="is_active" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50">
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>
        </div>

        {{-- Harga & COD --}}
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Maksimal Harga Tiket (Rp)</label>
                <input type="number" name="max_price" value="{{ old('max_price') }}"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50"
                    placeholder="Batas maksimal harga">
                <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ada batas</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Minimal Deposit COD (Rp)</label>
                <input type="number" name="cod_min_deposit" value="{{ old('cod_min_deposit', 500000) }}"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50">
                <p class="text-xs text-gray-500 mt-1">Saldo mengendap agency untuk bisa COD</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">COD Tersedia?</label>
                <select name="cod_available" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50">
                    <option value="0">Tidak</option>
                    <option value="1">Ya</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Izinkan pembayaran COD di rute ini</p>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">Deskripsi Rute</label>
            <textarea name="description" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50"
                      placeholder="Deskripsi singkat tentang rute ini...">{{ old('description') }}</textarea>
        </div>

        {{-- Stops --}}
        <div class="border-t border-gray-100 pt-6">
            <h3 class="font-bold text-secondary mb-3">🛑 Stops (Minimal 2)</h3>
            <div id="stopsContainer" class="space-y-3">
                <div class="stop-item flex gap-3 items-center">
                    <input type="text" name="stops[0][city_name]" placeholder="Nama Kota" class="flex-1 px-4 py-2 border border-gray-200 rounded-xl bg-gray-50" required>
                    <input type="number" name="stops[0][stop_order]" value="1" class="w-20 px-4 py-2 border border-gray-200 rounded-xl bg-gray-50" required>
                    <input type="number" name="stops[0][latitude]" step="0.0000001" placeholder="Latitude" class="w-32 px-4 py-2 border border-gray-200 rounded-xl bg-gray-50">
                    <input type="number" name="stops[0][longitude]" step="0.0000001" placeholder="Longitude" class="w-32 px-4 py-2 border border-gray-200 rounded-xl bg-gray-50">
                </div>
            </div>
            <button type="button" onclick="addStop()" class="mt-3 text-primary-600 text-sm font-medium hover:underline">+ Tambah Stop</button>
        </div>

        <button type="submit" class="bg-primary-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-primary-700 transition active:scale-95">
            💾 SIMPAN RUTE
        </button>
    </form>
</div>

@push('scripts')
<script>
function previewPhoto(event) {
    var file = event.target.files[0];
    if (file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover">';
        };
        reader.readAsDataURL(file);
    }
}

let stopCount = 1;
function addStop() {
    const div = document.createElement('div');
    div.className = 'stop-item flex gap-3 items-center';
    div.innerHTML = `<input type="text" name="stops[${stopCount}][city_name]" placeholder="Nama Kota" class="flex-1 px-4 py-2 border border-gray-200 rounded-xl bg-gray-50" required>
                     <input type="number" name="stops[${stopCount}][stop_order]" value="${stopCount + 1}" class="w-20 px-4 py-2 border border-gray-200 rounded-xl bg-gray-50" required>
                     <input type="number" name="stops[${stopCount}][latitude]" step="0.0000001" placeholder="Latitude" class="w-32 px-4 py-2 border border-gray-200 rounded-xl bg-gray-50">
                     <input type="number" name="stops[${stopCount}][longitude]" step="0.0000001" placeholder="Longitude" class="w-32 px-4 py-2 border border-gray-200 rounded-xl bg-gray-50">
                     <button type="button" onclick="this.parentElement.remove()" class="text-red-500 text-xl">✕</button>`;
    document.getElementById('stopsContainer').appendChild(div);
    stopCount++;
}
</script>
@endpush
@endsection