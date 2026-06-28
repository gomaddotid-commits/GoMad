@extends('layouts.payment-agent')

@section('title', 'Settlement')
@section('content')
@php
    $agent = auth()->user()->paymentAgent;
    $settlements = \App\Models\Settlement::where('payment_agent_id', $agent->id)->latest()->paginate(10);
    $settlementService = app(\App\Services\SettlementService::class);
    $snapTokens = [];
    foreach ($settlements as $s) {
        if (in_array($s->status, ['pending', 'overdue'])) {
            try { $snapTokens[$s->id] = $settlementService->paySettlement($s); } catch (\Exception $e) { $snapTokens[$s->id] = null; }
        }
    }
@endphp

<div>
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6">
        <h1 class="text-2xl font-bold text-secondary">Tagihan Settlement</h1>
        @if($agent->balance_to_settle > 0)
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-2">
            <span class="text-sm text-yellow-800">Sisa: <strong>Rp {{ number_format($agent->balance_to_settle, 0, ',', '.') }}</strong></span>
        </div>
        @endif
    </div>

    @if($settlements->isEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
        <div class="w-16 h-16 bg-green-50 rounded-xl flex items-center justify-center mx-auto mb-4"><span class="text-2xl">📋</span></div>
        <p class="text-gray-500 text-lg">Belum ada tagihan settlement.</p>
    </div>
    @else
    <div class="space-y-4">
        @foreach($settlements as $s)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-4">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <h3 class="font-bold text-lg">Periode {{ $s->period_start->format('d M') }} - {{ $s->period_end->format('d M Y') }}</h3>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            @if($s->status == 'verified') bg-green-100 text-green-700
                            @elseif($s->status == 'paid') bg-blue-100 text-blue-700
                            @elseif($s->status == 'pending') bg-yellow-100 text-yellow-700
                            @else bg-red-100 text-red-700 @endif">
                            @if($s->status == 'verified') Lunas
                            @elseif($s->status == 'paid') Menunggu Verifikasi
                            @elseif($s->status == 'pending') Menunggu Pembayaran
                            @else Terlambat @endif
                        </span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div class="bg-gray-50 rounded-xl p-3"><span class="text-gray-500 text-xs">Transaksi</span><p class="font-bold text-lg">{{ $s->total_transactions }}</p></div>
                        <div class="bg-gray-50 rounded-xl p-3"><span class="text-gray-500 text-xs">Total Diterima</span><p class="font-bold">Rp {{ number_format($s->total_amount, 0, ',', '.') }}</p></div>
                        <div class="bg-gray-50 rounded-xl p-3"><span class="text-gray-500 text-xs">Komisi</span><p class="font-bold text-green-600">Rp {{ number_format($s->total_commission, 0, ',', '.') }}</p></div>
                        <div class="bg-gray-50 rounded-xl p-3"><span class="text-gray-500 text-xs">Harus Disetor</span><p class="font-bold text-primary-600 text-lg">Rp {{ number_format($s->amount_to_settle, 0, ',', '.') }}</p></div>
                    </div>
                    @if($s->paid_at)<p class="text-xs text-gray-500 mt-3">Dibayar: {{ $s->paid_at->format('d M Y H:i') }}</p>@endif
                </div>

                @if(in_array($s->status, ['pending', 'overdue']))
                <div class="flex-shrink-0">
                    @if(isset($snapTokens[$s->id]))
                    <button onclick="paySettlement('{{ $snapTokens[$s->id] }}', {{ $s->id }})" id="pay-btn-{{ $s->id }}"
                            class="w-full lg:w-auto bg-primary-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-primary-700 transition text-center">Bayar Sekarang</button>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $settlements->links() }}</div>
    @endif
</div>

@if(!empty($snapTokens))
@push('scripts')
<script src="{{ config('gomad.midtrans.snap_url') }}" data-client-key="{{ config('gomad.midtrans.client_key') }}"></script>
<script>
function paySettlement(snapToken, settlementId) {
    if (!snapToken) return alert('Token tidak tersedia.');
    var btn = document.getElementById('pay-btn-' + settlementId);
    if (btn) { btn.disabled = true; btn.textContent = 'Memproses...'; }
    snap.pay(snapToken, {
        onSuccess: function() { window.location.reload(); },
        onPending: function() { if (btn) { btn.disabled = false; btn.textContent = 'Bayar Sekarang'; } },
        onError: function() { if (btn) { btn.disabled = false; btn.textContent = 'Bayar Sekarang'; } }
    });
}
</script>
@endpush
@endif
@endsection