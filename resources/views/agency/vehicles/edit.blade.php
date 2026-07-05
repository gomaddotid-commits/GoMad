@extends('layouts.agency')

@section('title', 'Edit Kendaraan')
@section('content')
<div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Kendaraan</h1>

    <form action="{{ route('agency.vehicles.update', $vehicle) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-md p-6 space-y-4">
        @csrf @method('PUT')
        
        <!-- Upload Foto -->
        <div>
            <label class="block text-sm font-medium mb-1">Foto Kendaraan</label>
            <div class="flex items-center gap-4">
                <div class="w-32 h-24 rounded-lg flex items-center justify-center text-3xl overflow-hidden" id="previewContainer">
                    @if($vehicle->vehicle_image)
                    <img src="{{  $vehicle->vehicle_image }}" class="w-full h-full object-cover">
                    @else
                    <div class="w-full h-full bg-gray-100 flex items-center justify-center">🚐</div>
                    @endif
                </div>
                <div class="flex-1">
                    <input type="file" name="vehicle_image" id="vehicleImage" accept="image/*" 
                           class="w-full text-sm" onchange="previewImage(event)">
                    <p class="text-xs text-gray-500 mt-1">Biarkan kosong jika tidak ingin mengubah foto</p>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Nomor Plat <span class="text-red-500">*</span></label>
            <input type="text" name="plate_number" value="{{ old('plate_number', $vehicle->plate_number) }}" 
                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary" required>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Merk <span class="text-red-500">*</span></label>
                <input type="text" name="brand" value="{{ old('brand', $vehicle->brand) }}" 
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Model <span class="text-red-500">*</span></label>
                <input type="text" name="model" value="{{ old('model', $vehicle->model) }}" 
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary" required>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Tahun</label>
                <input type="number" name="year" value="{{ old('year', $vehicle->year) }}" 
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary" min="2000">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Kapasitas <span class="text-red-500">*</span></label>
                <input type="number" name="capacity" value="{{ old('capacity', $vehicle->capacity) }}" 
                       class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary" min="4" max="20" required>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Tipe <span class="text-red-500">*</span></label>
            <select name="type" class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary" required>
                <option value="economy" {{ $vehicle->type == 'economy' ? 'selected' : '' }}>Ekonomi</option>
                <option value="premium" {{ $vehicle->type == 'premium' ? 'selected' : '' }}>Premium</option>
            </select>
        </div>

        <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-bold hover:bg-primary-dark transition">
            💾 UPDATE KENDARAAN
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