@extends('layouts.agency')

@section('title', 'Tambah Driver')
@section('content')
<div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Tambah Driver</h1>

    <form action="{{ route('agency.drivers.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-md p-6 space-y-4">
        @csrf
        
        <!-- Upload Foto -->
        <div>
            <label class="block text-sm font-medium mb-1">Foto Driver</label>
            <div class="flex items-center gap-4">
                <div class="w-24 h-24 rounded-full bg-gray-100 flex items-center justify-center text-3xl overflow-hidden" id="previewContainer">
                    👨‍✈️
                </div>
                <div class="flex-1">
                    <input type="file" name="avatar" id="avatarInput" accept="image/*" 
                           class="w-full text-sm" onchange="previewImage(event)">
                    <p class="text-xs text-gray-500 mt-1">Format: JPG, PNG, WEBP. Max 2MB</p>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" 
                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary" required>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Email <span class="text-red-500">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" 
                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary" required>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Nomor HP <span class="text-red-500">*</span></label>
            <input type="text" name="phone" value="{{ old('phone') }}" 
                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary" required>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Password <span class="text-red-500">*</span></label>
            <input type="password" name="password" class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary" required>
        </div>
        <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-bold hover:bg-primary-dark transition">
            💾 SIMPAN DRIVER
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