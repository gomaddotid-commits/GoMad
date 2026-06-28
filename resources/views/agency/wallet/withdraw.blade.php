@extends('layouts.agency')

@section('title', 'Tarik Dana')
@section('content')

@php
    $walletService = app(\App\Services\WalletService::class);
    $balance = $walletService->getBalance(auth()->user()->agency);
@endphp

<div>
    <h1 class="text-lg font-bold text-secondary mb-6">Tarik Dana</h1>

    <div class="bg-primary-50 border border-primary-200 rounded-2xl p-5 mb-6">
        <p class="text-xs text-gray-500 uppercase font-medium mb-1">Saldo Tersedia</p>
        <p class="text-2xl font-bold text-primary-600">Rp {{ number_format($balance['available_balance'], 0, ',', '.') }}</p>
    </div>

    <form action="{{ route('agency.withdrawals.store') }}" method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-secondary mb-1">Jumlah (Rp) <span class="text-red-500">*</span></label>
            <input type="number" name="amount"
                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" 
                   min="{{ config('gomad.minimal_withdrawal', 100000) }}" 
                   max="{{ (int) $balance['available_balance'] }}" required>
            <p class="text-xs text-gray-500 mt-1">Minimal: Rp {{ number_format(config('gomad.minimal_withdrawal', 100000), 0, ',', '.') }}</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-secondary mb-1">Nama Bank <span class="text-red-500">*</span></label>
            <input type="text" name="bank_name"
                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50"
                   placeholder="Contoh: BCA, BNI, BRI" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-secondary mb-1">Nomor Rekening <span class="text-red-500">*</span></label>
            <input type="text" name="bank_account_number"
                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-secondary mb-1">Nama Pemilik Rekening <span class="text-red-500">*</span></label>
            <input type="text" name="bank_account_name"
                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" required>
        </div>
        <button type="submit" class="w-full bg-primary-600 text-white py-3 rounded-xl font-semibold hover:bg-primary-700 transition active:scale-95">
            AJUKAN PENARIKAN
        </button>
    </form>
</div>
@endsection