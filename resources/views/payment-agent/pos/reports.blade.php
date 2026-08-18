@extends('layouts.payment-agent')
@section('title', 'Laporan')
@section('content')

<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#111827]">📊 Laporan Penjualan</h1>
            <p class="text-sm text-gray-500 font-light">Ringkasan transaksi harian</p>
        </div>
        <form class="flex gap-2">
            <input type="date" name="date" value="{{ $date }}" 
                   class="px-4 py-2 rounded-xl border border-[#E5E7EB] text-sm"
                   onchange="this.form.submit()">
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <p class="text-xs text-gray-400 mb-1">Transaksi</p>
            <p class="text-2xl font-bold text-[#111827]">{{ $summary['total_transactions'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <p class="text-xs text-gray-400 mb-1">Item Terjual</p>
            <p class="text-2xl font-bold text-[#111827]">{{ $summary['total_items'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <p class="text-xs text-gray-400 mb-1">Omzet</p>
            <p class="text-lg font-bold text-[#BA1826]">Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <p class="text-xs text-gray-400 mb-1">Cash</p>
            <p class="text-lg font-bold text-green-600">Rp {{ number_format($summary['cash'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <p class="text-xs text-gray-400 mb-1">QRIS</p>
            <p class="text-lg font-bold text-blue-600">Rp {{ number_format($summary['qris'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Transaction List --}}
    <div class="bg-white rounded-2xl border border-[#E5E7EB] overflow-hidden">
        <div class="p-4 bg-[#F9FAFB] border-b border-[#E5E7EB]">
            <h3 class="font-bold text-[#111827]">🧾 Daftar Transaksi — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h3>
        </div>
        @forelse($transactions as $tx)
        <div class="p-4 border-b border-[#E5E7EB] hover:bg-[#F9FAFB] transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-bold text-[#111827]">{{ $tx->invoice_no }}</p>
                    <p class="text-xs text-gray-400">{{ $tx->created_at->format('H:i') }} · {{ $tx->payment_method }} · {{ $tx->items()->count() }} item</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-[#BA1826]">Rp {{ number_format($tx->total, 0, ',', '.') }}</p>
                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $tx->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $tx->status }}
                    </span>
                </div>
            </div>
        </div>
        @empty
        <div class="p-12 text-center text-gray-400">Tidak ada transaksi di tanggal ini.</div>
        @endforelse
    </div>
</div>

@endsection
