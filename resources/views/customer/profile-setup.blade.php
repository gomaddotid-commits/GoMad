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

        @if(session('warning'))
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-4 text-sm text-yellow-800">
            {{ session('warning') }}
        </div>
        @endif

        @if(!auth()->user()->phone)
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 text-sm text-blue-800">
            <p class="font-medium flex items-center gap-2">
                <span>📱</span> Nomor WhatsApp Diperlukan
            </p>
            <p class="mt-1">Nomor WhatsApp digunakan untuk:</p>
            <ul class="list-disc list-inside mt-2 space-y-1 text-blue-700">
                <li>Notifikasi booking via WhatsApp</li>
                <li>Kontak dari driver saat penjemputan</li>
                <li>Pengiriman E-Ticket</li>
            </ul>
        </div>
        @endif

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
                <label class="block text-sm font-medium text-secondary mb-1">
                    Nomor WhatsApp 
                    @if(!auth()->user()->phone)
                    <span class="text-red-500">*</span>
                    @endif
                </label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" 
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50" 
                       placeholder="081234567890"
                       {{ auth()->user()->phone ? '' : 'required' }}>
                <p class="text-xs text-gray-500 mt-1">
                    @if(auth()->user()->phone)
                    Nomor WhatsApp Anda saat ini
                    @else
                    Wajib diisi untuk menerima notifikasi booking
                    @endif
                </p>
            </div>

            <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-bold hover:bg-primary-dark transition">
                💾 SIMPAN PROFIL
            </button>
        </form>

        @if(auth()->user()->phone)
        <form action="{{ route('customer.setup.save') }}" method="POST" class="mt-3">
            @csrf
            <input type="hidden" name="name" value="{{ $user->name }}">
            <input type="hidden" name="skip" value="1">
            <button type="submit" class="w-full border border-gray-300 text-gray-600 py-3 rounded-lg font-semibold hover:bg-gray-50 transition">
                ⏭️ LEWATI (ISI NANTI)
            </button>
        </form>
        @endif
    </div>
</div>
@endsection