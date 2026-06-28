@extends('layouts.payment-agent')

@section('title', 'Riwayat Pembayaran')
@section('content')
@php
    $agent = auth()->user()->paymentAgent;
    $payments = \App\Models\CashPayment::with(['booking.schedule.route', 'booking.originStop', 'booking.destinationStop', 'booking.customer'])
        ->where('payment_agent_id', $agent->id)
        ->latest()
        ->paginate(15);
@endphp

<div>
    <h1 class="text-2xl font-bold text-secondary mb-6">Riwayat Pembayaran</h1>

    @if($payments->isEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
        <div class="w-16 h-16 bg-green-50 rounded-xl flex items-center justify-center mx-auto mb-4">
            <span class="text-2xl">💰</span>
        </div>
        <p class="text-gray-500 text-lg">Belum ada transaksi.</p>
    </div>
    @else
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Kode Bayar</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Booking</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Customer</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Jumlah</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Komisi</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Tanggal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($payments as $p)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $p->payment_code }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if($p->booking)
                            <span class="font-medium">{{ $p->booking->booking_code }}</span>
                            <br><span class="text-gray-500">{{ $p->booking->originStop->city_name ?? '?' }} → {{ $p->booking->destinationStop->city_name ?? '?' }}</span>
                            @else <span class="text-gray-400">-</span> @endif
                        </td>
                        <td class="px-4 py-3">{{ $p->booking->customer->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right font-medium">Rp {{ number_format($p->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-green-600 font-medium">Rp {{ number_format($p->agent_commission, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                @if($p->status == 'confirmed') bg-green-100 text-green-700
                                @elseif($p->status == 'settled') bg-blue-100 text-blue-700
                                @elseif($p->status == 'pending') bg-yellow-100 text-yellow-700
                                @else bg-gray-100 text-gray-600 @endif">{{ $p->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-xs text-gray-500">{{ $p->created_at->format('d M H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mb-6">{{ $payments->links() }}</div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-bold text-secondary mb-3">Ringkasan</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div class="bg-gray-50 rounded-xl p-3 text-center"><span class="text-gray-500">Total Transaksi</span><p class="font-bold text-lg">{{ $payments->total() }}</p></div>
            <div class="bg-gray-50 rounded-xl p-3 text-center"><span class="text-gray-500">Total Diterima</span><p class="font-bold text-lg">Rp {{ number_format($payments->sum('amount'), 0, ',', '.') }}</p></div>
            <div class="bg-gray-50 rounded-xl p-3 text-center"><span class="text-gray-500">Total Komisi</span><p class="font-bold text-lg text-green-600">Rp {{ number_format($payments->sum('agent_commission'), 0, ',', '.') }}</p></div>
            <div class="bg-gray-50 rounded-xl p-3 text-center"><span class="text-gray-500">Harus Disetor</span><p class="font-bold text-lg text-yellow-600">Rp {{ number_format($payments->sum('amount') - $payments->sum('agent_commission'), 0, ',', '.') }}</p></div>
        </div>
    </div>
    @endif
</div>
@endsection