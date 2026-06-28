@extends('layouts.payment-agent')

@section('title', 'Dashboard')
@section('content')
@php $agent = auth()->user()->paymentAgent; @endphp

@if(!$agent)
<div class="text-center py-12">
    <div class="w-20 h-20 bg-green-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <span class="text-3xl">🏪</span>
    </div>
    <h2 class="text-xl font-bold text-secondary mb-2">Setup Warung</h2>
    <p class="text-gray-600 mb-6">Lengkapi data warung Anda untuk mulai menerima pembayaran.</p>
    <a href="{{ route('payment-agent.setup') }}" class="btn-primary">Setup Sekarang</a>
</div>

@elseif(!$agent->is_verified)
<div class="text-center py-12">
    <div class="w-20 h-20 bg-yellow-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <span class="text-3xl">⏳</span>
    </div>
    <h2 class="text-xl font-bold text-secondary mb-2">Menunggu Verifikasi</h2>
    <p class="text-gray-600 max-w-md mx-auto mb-4">Warung <strong>{{ $agent->agent_name }}</strong> sedang dalam proses verifikasi.</p>
    
    @if($agent->rejection_reason)
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 max-w-md mx-auto mb-4 text-left">
        <p class="text-sm font-medium text-red-700">Alasan Penolakan:</p>
        <p class="text-sm text-red-600 mt-1">{{ $agent->rejection_reason }}</p>
        <a href="{{ route('payment-agent.setup', ['reset' => 1]) }}" class="inline-block mt-3 bg-yellow-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-yellow-600">Setup Ulang</a>
    </div>
    @endif
    
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 max-w-md mx-auto text-left">
        <h3 class="font-bold text-secondary mb-3">Data Warung Anda</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Nama</span><span class="font-semibold">{{ $agent->agent_name }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Pemilik</span><span class="font-semibold">{{ $agent->owner_name }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Alamat</span><span class="font-semibold text-right max-w-[200px]">{{ $agent->address }}</span></div>
        </div>
    </div>
</div>

@else
@php
    $agentService = app(\App\Services\PaymentAgentService::class);
    $stats = $agentService->getAgentStats($agent);
@endphp

<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-secondary">{{ $agent->agent_name }}</h1>
        <p class="text-gray-500 text-sm">{{ $agent->address }} • Kec. {{ $agent->kecamatan ?? '-' }}</p>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase">Transaksi Hari Ini</p>
            <p class="text-2xl font-bold text-secondary mt-1">{{ $stats['today_transactions'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase">Komisi Hari Ini</p>
            <p class="text-lg font-bold text-green-600 mt-1">Rp {{ number_format($stats['today_commission'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase">Bulan Ini</p>
            <p class="text-2xl font-bold text-secondary mt-1">{{ $stats['month_transactions'] }} transaksi</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase">Harus Disetor</p>
            <p class="text-lg font-bold text-yellow-600 mt-1">Rp {{ number_format($stats['balance_to_settle'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Konfirmasi Pembayaran --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
        <h2 class="font-bold text-lg text-secondary mb-4">Konfirmasi Pembayaran</h2>
        <form action="{{ route('payment-agent.payments.confirm') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">Kode Bayar</label>
                    <input type="text" name="payment_code" placeholder="WM-YYYYMMDD-XXXXXX" 
                           class="w-full px-4 py-3 border border-gray-200 rounded-xl font-mono uppercase tracking-wider focus:ring-2 focus:ring-green-500 bg-gray-50" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">PIN Konfirmasi</label>
                    <input type="password" name="pin" placeholder="******" maxlength="6" 
                           class="w-full md:w-48 px-4 py-3 border border-gray-200 rounded-xl text-lg text-center tracking-widest focus:ring-2 focus:ring-green-500 bg-gray-50" required>
                </div>
            </div>
            <button type="submit" class="bg-green-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-green-700 transition">Konfirmasi Pembayaran</button>
        </form>
    </div>

    {{-- Informasi --}}
    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-secondary mb-3">Informasi Warung</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Status</span><span class="font-medium text-green-600">Terverifikasi</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Komisi</span><span class="font-medium">{{ $agent->commission_rate }}%</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Total Transaksi</span><span class="font-medium">{{ $agent->total_transactions }}</span></div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-secondary mb-3">Panduan</h3>
            <ol class="space-y-2 text-sm text-gray-600 list-decimal list-inside">
                <li>Customer datang dengan kode bayar</li>
                <li>Masukkan kode bayar + PIN</li>
                <li>Terima uang CASH dari customer</li>
                <li>Klik Konfirmasi</li>
                <li>Setiap Senin, cek Settlement</li>
            </ol>
        </div>
    </div>
</div>
@endif
@endsection