@extends('layouts.agency')

@section('title', 'Top Up Saldo')
@section('content')
@php
    $walletService = app(\App\Services\WalletService::class);
    $agency = auth()->user()->agency;
    $balance = $walletService->getBalance($agency);
    $depositBalance = (float) ($agency->wallet->deposit_balance ?? 0);
    $codHold = (float) ($agency->wallet->cod_hold_balance ?? 0);
    $availableDeposit = $depositBalance - $codHold;
@endphp

<div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-bold text-secondary mb-6">Top Up Saldo Deposit</h1>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h3 class="font-bold text-secondary mb-4">Informasi Saldo</h3>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Saldo Deposit</span>
                <span class="font-bold">Rp {{ number_format($depositBalance, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">COD Hold</span>
                <span class="font-bold text-orange-600">Rp {{ number_format($codHold, 0, ',', '.') }}</span>
            </div>
            <hr>
            <div class="flex justify-between">
                <span class="text-gray-500">Tersedia</span>
                <span class="font-bold text-green-600">Rp {{ number_format($availableDeposit, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-bold text-secondary mb-4">Isi Saldo</h3>
        
        <form action="{{ route('agency.wallet.topup.process') }}" method="POST" id="topupForm">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-secondary mb-2">Nominal Top Up</label>
                <div class="grid grid-cols-3 gap-3 mb-3">
                    @foreach([100000, 500000, 1000000, 2000000, 5000000] as $nominal)
                    <button type="button" onclick="setNominal({{ $nominal }})" 
                            class="border-2 border-gray-200 rounded-xl py-3 text-sm font-semibold hover:border-primary-600 hover:bg-primary-50 transition">
                        Rp {{ number_format($nominal / 1000, 0, ',', '.') }}K
                    </button>
                    @endforeach
                </div>
                <input type="number" name="amount" id="amountInput" 
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl text-lg font-bold focus:ring-2 focus:ring-primary-600 bg-gray-50" 
                       placeholder="Atau masukkan nominal sendiri" min="10000" required>
            </div>
            
            <div class="bg-blue-50 rounded-xl p-4 mb-4 text-sm text-blue-800">
                <p class="font-medium mb-1">Informasi:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Biaya admin top up: <strong>Rp {{ number_format(\App\Models\PlatformSetting::getValue('topup_admin_fee', 3500), 0, ',', '.') }}</strong> per transaksi</li>
                    <li>Minimal top up: <strong>Rp {{ number_format(\App\Models\PlatformSetting::getValue('topup_min_amount', 50000), 0, ',', '.') }}</strong></li>
                    <li>Saldo deposit digunakan sebagai jaminan untuk fitur COD</li>
                    <li>Saldo tidak hangus dan bisa digunakan kapan saja</li>
                    <li>Bisa juga transfer dari Saldo Tersedia (tanpa biaya)</li>
                </ul>
            </div>

            {{-- Tampilkan total yang harus dibayar --}}
            <div id="totalDisplay" class="bg-gray-50 rounded-xl p-4 mb-4 hidden">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Nominal Top Up</span>
                    <span class="font-medium" id="nominalDisplay">-</span>
                </div>
                <div class="flex justify-between text-sm mt-1">
                    <span class="text-gray-600">Biaya Admin</span>
                    <span class="font-medium" id="adminFeeDisplay">-</span>
                </div>
                <hr class="my-2">
                <div class="flex justify-between text-sm font-bold">
                    <span>Total Dibayar</span>
                    <span class="text-primary-600" id="totalDisplay2">-</span>
                </div>
            </div>
            
            <button type="submit" class="w-full btn-primary text-lg py-3">Top Up Sekarang</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function setNominal(amount) {
    document.getElementById('amountInput').value = amount;
    document.getElementById('amountInput').focus();
}

const adminFee = {{ \App\Models\PlatformSetting::getValue('topup_admin_fee', 3500) }};
const amountInput = document.getElementById('amountInput');
const totalDisplay = document.getElementById('totalDisplay');

function updateTotal() {
    const amount = parseInt(amountInput.value) || 0;
    if (amount > 0) {
        totalDisplay.classList.remove('hidden');
        document.getElementById('nominalDisplay').textContent = 'Rp ' + formatRupiah(amount);
        document.getElementById('adminFeeDisplay').textContent = 'Rp ' + formatRupiah(adminFee);
        document.getElementById('totalDisplay2').textContent = 'Rp ' + formatRupiah(amount + adminFee);
    } else {
        totalDisplay.classList.add('hidden');
    }
}

amountInput.addEventListener('input', updateTotal);
</script>
@endpush
@endsection