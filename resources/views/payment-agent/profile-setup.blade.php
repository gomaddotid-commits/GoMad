@extends('layouts.payment-agent')

@section('title', 'Lengkapi Profil Warung')
@section('content')
@php $agent = auth()->user()->paymentAgent; @endphp

<div class="max-w-2xl">
    <div class="text-center mb-8">
        <div class="w-20 h-20 bg-green-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <span class="text-3xl">🏪</span>
        </div>
        <h1 class="text-2xl font-bold text-secondary mb-2">{{ $agent && $agent->agent_name ? 'Setup Ulang Profil Warung' : 'Lengkapi Profil Warung' }}</h1>
        <p class="text-gray-600">{{ $agent && $agent->agent_name ? 'Perbaiki data sesuai catatan penolakan' : 'Isi data warung Anda untuk menjadi mitra GoMad' }}</p>
    </div>

    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6 text-sm text-yellow-800">
        <strong>Semua field wajib diisi</strong> kecuali yang bertanda opsional.
    </div>

    <form action="{{ route('payment-agent.setup.save') }}" method="POST" class="space-y-6">
        @csrf
        
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-lg text-secondary mb-4">Informasi Warung</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">Nama Warung</label>
                    <input type="text" name="agent_name" value="{{ old('agent_name', $agent->agent_name ?? '') }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 bg-gray-50" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">Alamat Lengkap</label>
                    <textarea name="address" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-green-500 bg-gray-50" required>{{ old('address', $agent->address ?? '') }}</textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-1">Kecamatan</label>
                        <input type="text" name="kecamatan" value="{{ old('kecamatan', $agent->kecamatan ?? '') }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-1">PIN (6 digit)</label>
                        <input type="password" name="pin" maxlength="6" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-center text-lg tracking-widest bg-gray-50" required>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">Link Google Maps</label>
                    <input type="url" name="maps_link" value="{{ old('maps_link', $agent->maps_link ?? '') }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-lg text-secondary mb-4">Informasi Pemilik</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">Nama Pemilik</label>
                    <input type="text" name="owner_name" value="{{ old('owner_name', $agent->owner_name ?? auth()->user()->name) }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">Nomor HP Pemilik</label>
                    <input type="text" name="owner_phone" value="{{ old('owner_phone', $agent->owner_phone ?? auth()->user()->phone) }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50" required>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-lg text-secondary mb-4">Informasi Penjaga <span class="text-sm font-normal text-gray-500">(Opsional)</span></h3>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-secondary mb-1">Nama Penjaga</label><input type="text" name="guard_name" value="{{ old('guard_name', $agent->guard_name ?? '') }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50"></div>
                <div><label class="block text-sm font-medium text-secondary mb-1">Nomor HP Penjaga</label><input type="text" name="guard_phone" value="{{ old('guard_phone', $agent->guard_phone ?? '') }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50"></div>
            </div>
        </div>

        <button type="submit" class="w-full btn-primary text-lg py-4">Simpan & Ajukan Verifikasi</button>
    </form>
</div>
@endsection