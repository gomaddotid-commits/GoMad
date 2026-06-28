@extends('layouts.customer')

@section('title', 'Lengkapi Profil')
@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-6">
            <div class="text-5xl mb-4">🧑</div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Lengkapi Profil</h1>
            <p class="text-gray-500">Isi data diri Anda untuk pengalaman yang lebih baik</p>
        </div>

        <form action="{{ route('customer.setup.save') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                <input type="email" value="{{ $user->email }}" disabled
                       class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                <p class="text-xs text-gray-400 mt-1">Email yang digunakan saat pendaftaran</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Nomor WhatsApp</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" 
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50" required>
                <p class="text-xs text-gray-500 mt-1">Untuk notifikasi booking via WhatsApp</p>
            </div>

            <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-bold hover:bg-primary-dark transition">
                💾 SIMPAN PROFIL
            </button>
        </form>

        <form action="{{ route('customer.setup.save') }}" method="POST" class="mt-3">
            @csrf
            <input type="hidden" name="name" value="{{ $user->name }}">
            <input type="hidden" name="skip" value="1">
            <button type="submit" class="w-full border border-gray-300 text-gray-600 py-3 rounded-lg font-semibold hover:bg-gray-50 transition">
                ⏭️ LEWATI (ISI NANTI)
            </button>
        </form>
    </div>
</div>
@endsection