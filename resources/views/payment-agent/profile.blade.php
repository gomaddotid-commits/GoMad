@extends('layouts.payment-agent')

@section('title', 'Profil Warung')
@section('content')
@php $agent = auth()->user()->paymentAgent; @endphp

<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-[#111111] mb-6">Profil Warung</h1>

    @if(!$agent)
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-8 text-center text-gray-500 shadow-sm font-light">Data warung tidak ditemukan.</div>
    @else
    {{-- Status --}}
    <div class="rounded-[12px] p-4 mb-6 {{ $agent->is_verified ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200' }}">
        <div class="flex items-center gap-3">
            <span class="text-3xl">{{ $agent->is_verified ? '✅' : '⏳' }}</span>
            <div>
                <p class="font-bold text-lg {{ $agent->is_verified ? 'text-green-800' : 'text-yellow-800' }}">{{ $agent->is_verified ? 'Warung Terverifikasi' : 'Menunggu Verifikasi' }}</p>
                <p class="text-sm {{ $agent->is_verified ? 'text-green-600' : 'text-yellow-700' }} font-light">{{ $agent->is_verified ? 'Siap menerima pembayaran.' : 'Hubungi admin untuk verifikasi.' }}</p>
            </div>
        </div>
        @if($agent->rejection_reason)
        <div class="mt-3 bg-red-50 border border-red-200 rounded-[12px] p-3 text-sm text-red-700 font-light">
            <strong class="font-medium">Alasan Penolakan:</strong> {{ $agent->rejection_reason }}
            <a href="{{ route('payment-agent.setup', ['reset' => 1]) }}" class="ml-2 text-[#C1121F] underline font-medium">Setup Ulang</a>
        </div>
        @endif
    </div>

    {{-- Info --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <div class="flex items-center mb-6">
            <div class="w-20 h-20 rounded-full bg-[#C1121F]/5 border border-[#E5E5E5] flex items-center justify-center text-3xl mr-4">🏪</div>
            <div>
                <h2 class="text-xl font-bold text-[#111111]">{{ $agent->agent_name }}</h2>
                <p class="text-sm text-gray-500 font-light">Komisi: {{ $agent->commission_rate }}% per transaksi</p>
            </div>
        </div>
        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3"><span class="text-[10px] text-gray-400 font-mono uppercase tracking-wider">Pemilik</span><p class="font-semibold text-[#111111]">{{ $agent->owner_name }}</p><p class="text-gray-600 font-light">{{ $agent->owner_phone }}</p></div>
            @if($agent->guard_name)
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3"><span class="text-[10px] text-gray-400 font-mono uppercase tracking-wider">Penjaga</span><p class="font-semibold text-[#111111]">{{ $agent->guard_name }}</p><p class="text-gray-600 font-light">{{ $agent->guard_phone }}</p></div>
            @endif
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3 md:col-span-2">
                <span class="text-[10px] text-gray-400 font-mono uppercase tracking-wider">Alamat</span>
                <p class="font-semibold text-[#111111]">{{ $agent->full_address }}</p>
            </div>
        </div>
    </div>

    @if($agent->is_verified)
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
        <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">Statistik</h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3 text-center"><span class="text-[10px] text-gray-400 font-mono uppercase tracking-wider">Total Transaksi</span><p class="text-2xl font-bold text-[#111111]">{{ $agent->total_transactions }}</p></div>
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3 text-center"><span class="text-[10px] text-gray-400 font-mono uppercase tracking-wider">Total Komisi</span><p class="text-2xl font-bold text-[#C1121F]">Rp {{ number_format($agent->total_commission, 0, ',', '.') }}</p></div>
        </div>
    </div>
    @endif
    @endif
</div>
@endsection