@extends('layouts.payment-agent')

@section('title', 'Profil Warung')
@section('content')
@php $agent = auth()->user()->paymentAgent; @endphp

<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-secondary mb-6">Profil Warung</h1>

    @if(!$agent)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">Data warung tidak ditemukan.</div>
    @else
    {{-- Status --}}
    <div class="rounded-2xl p-4 mb-6 {{ $agent->is_verified ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200' }}">
        <div class="flex items-center gap-3">
            <span class="text-3xl">{{ $agent->is_verified ? '✅' : '⏳' }}</span>
            <div>
                <p class="font-bold text-lg {{ $agent->is_verified ? 'text-green-800' : 'text-yellow-800' }}">{{ $agent->is_verified ? 'Warung Terverifikasi' : 'Menunggu Verifikasi' }}</p>
                <p class="text-sm {{ $agent->is_verified ? 'text-green-600' : 'text-yellow-700' }}">{{ $agent->is_verified ? 'Siap menerima pembayaran.' : 'Hubungi admin untuk verifikasi.' }}</p>
            </div>
        </div>
        @if($agent->rejection_reason)
        <div class="mt-3 bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700">
            <strong>Alasan Penolakan:</strong> {{ $agent->rejection_reason }}
            <a href="{{ route('payment-agent.setup', ['reset' => 1]) }}" class="ml-2 text-red-600 underline font-medium">Setup Ulang</a>
        </div>
        @endif
    </div>

    {{-- Info --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-center mb-6">
            <div class="w-20 h-20 rounded-full bg-green-50 flex items-center justify-center text-3xl mr-4">🏪</div>
            <div>
                <h2 class="text-xl font-bold text-secondary">{{ $agent->agent_name }}</h2>
                <p class="text-sm text-gray-500">Komisi: {{ $agent->commission_rate }}% per transaksi</p>
            </div>
        </div>
        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div class="bg-gray-50 rounded-xl p-3"><span class="text-gray-500 text-xs">Pemilik</span><p class="font-semibold">{{ $agent->owner_name }}</p><p class="text-gray-600">{{ $agent->owner_phone }}</p></div>
            @if($agent->guard_name)
            <div class="bg-gray-50 rounded-xl p-3"><span class="text-gray-500 text-xs">Penjaga</span><p class="font-semibold">{{ $agent->guard_name }}</p><p class="text-gray-600">{{ $agent->guard_phone }}</p></div>
            @endif
            <div class="bg-gray-50 rounded-xl p-3 md:col-span-2"><span class="text-gray-500 text-xs">Alamat</span><p class="font-semibold">{{ $agent->address }}</p><p class="text-gray-600">Kec. {{ $agent->kecamatan ?? '-' }}</p></div>
        </div>
    </div>

    @if($agent->is_verified)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-bold text-secondary mb-4">Statistik</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="bg-gray-50 rounded-xl p-3 text-center"><span class="text-gray-500">Total Transaksi</span><p class="text-2xl font-bold">{{ $agent->total_transactions }}</p></div>
            <div class="bg-gray-50 rounded-xl p-3 text-center"><span class="text-gray-500">Total Komisi</span><p class="text-2xl font-bold text-green-600">Rp {{ number_format($agent->total_commission, 0, ',', '.') }}</p></div>
        </div>
    </div>
    @endif
    @endif
</div>
@endsection