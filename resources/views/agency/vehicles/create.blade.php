@extends('layouts.agency')

@section('title', 'Tambah Kendaraan')
@section('content')
<div>
    <h1 class="text-lg font-bold text-secondary mb-6">Tambah Kendaraan</h1>

    <form action="{{ route('agency.vehicles.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">Foto Kendaraan</label>
            <div class="flex items-center gap-4">
                <div class="w-32 h-24 bg-gray-100 rounded-xl flex items-center justify-center text-3xl overflow-hidden" id="previewContainer">
                    🚐
                </div>
                <div class="flex-1">
                    <input type="file" name="vehicle_image" id="vehicleImage" accept="image/*"
                           class="w-full text-sm" onchange="previewImage(event)">
                    <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, WEBP. Max 2MB</p>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">Nomor Plat <span class="text-red-500">*</span></label>
            <input type="text" name="plate_number" value="{{ old('plate_number') }}"
                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" placeholder="M 1234 AB" required>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Merk <span class="text-red-500">*</span></label>
                <input type="text" name="brand" value="{{ old('brand') }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" placeholder="Toyota" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Model <span class="text-red-500">*</span></label>
                <input type="text" name="model" value="{{ old('model') }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" placeholder="Hiace Commuter" required>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Tahun</label>
                <input type="number" name="year" value="{{ old('year', date('Y')) }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" min="2000" max="{{ date('Y') + 1 }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Kapasitas Seat <span class="text-red-500">*</span></label>
                <input type="number" name="capacity" value="{{ old('capacity', 8) }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" min="4" max="20" required>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">Tipe Kendaraan <span class="text-red-500">*</span></label>
            <select name="type" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" required>
                <option value="economy" {{ old('type') == 'economy' ? 'selected' : '' }}>Ekonomi</option>
                <option value="premium" {{ old('type') == 'premium' ? 'selected' : '' }}>Premium</option>
            </select>
        </div>

        <button type="submit" class="bg-primary-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-primary-700 transition active:scale-95">
            💾 SIMPAN KENDARAAN
        </button>
    </form>
</div>

@push('scripts')
<script>
function previewImage(event) {
    var file = event.target.files[0];
    if (file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var container = document.getElementById('previewContainer');
            container.innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover">';
        };
        reader.readAsDataURL(file);
    }
}
</script>
@endpush
@endsection