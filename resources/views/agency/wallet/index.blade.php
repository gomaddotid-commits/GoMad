@extends('layouts.agency')

@section('title', 'Dompet')
@section('content')
@php
    $walletService = app(\App\Services\WalletService::class);
    $agency = auth()->user()->agency;
    $topupAdminFee = (float) \App\Models\PlatformSetting::getValue('topup_admin_fee', 3500);
    $depositBalance = $balanceSummary['deposit_balance'] ?? 0;
    $codHold = $balanceSummary['cod_hold_balance'] ?? 0;
    $availableDeposit = $balanceSummary['available_deposit'] ?? 0;
@endphp

<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 border-b border-[#E5E7EB] pb-3">
        <h1 class="text-2xl font-bold text-[#111827]">Dompet Agency</h1>
        <a href="{{ route('agency.wallet.topup') }}" class="btn-gomad-primary text-sm inline-flex items-center gap-2 self-start rounded-[10px]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Top Up Saldo
        </a>
    </div>

    {{-- Saldo Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-5 shadow-gomad">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Saldo Tersedia</p>
            <p class="text-2xl font-bold text-green-600 mt-1">Rp {{ number_format($balanceSummary['available_balance'], 0, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400 mt-1 font-light">Dari booking selesai</p>
        </div>
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-5 shadow-gomad">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Saldo Pending</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1">Rp {{ number_format($balanceSummary['pending_balance'], 0, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400 mt-1 font-light">Menunggu perjalanan selesai</p>
        </div>
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-5 shadow-gomad">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Saldo Deposit</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">Rp {{ number_format($depositBalance, 0, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400 mt-1 font-light">
                Tersedia: Rp {{ number_format($availableDeposit, 0, ',', '.') }}
                @if($codHold > 0)<span class="text-orange-500"> • Hold: Rp {{ number_format($codHold, 0, ',', '.') }}</span>@endif
            </p>
        </div>
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-5 shadow-gomad">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Total Diterima</p>
            <p class="text-2xl font-bold text-purple-600 mt-1">Rp {{ number_format($balanceSummary['total_earned'], 0, ',', '.') }}</p>
            <p class="text-[10px] text-gray-400 mt-1 font-light">Total ditarik: Rp {{ number_format($balanceSummary['total_withdrawn'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Info COD --}}
    <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-5 mb-6 shadow-gomad">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full {{ $availableDeposit >= 500000 ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200' }} flex items-center justify-center text-lg border">
                    {{ $availableDeposit >= 500000 ? '✅' : '⚠️' }}
                </div>
                <div>
                    <h3 class="font-bold text-[#111827]">Status COD</h3>
                    <p class="text-sm text-gray-500 font-light">
                        @if($availableDeposit >= 500000)
                        Saldo deposit mencukupi untuk mengaktifkan fitur COD.
                        @else
                        Saldo deposit belum mencukupi minimal Rp 500.000 untuk COD.
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('agency.wallet.topup') }}" class="bg-[#BA1826] text-white px-4 py-2 rounded-[10px] text-sm font-medium hover:bg-[#8A0F18] transition whitespace-nowrap">Top Up</a>
        </div>
    </div>

    {{-- Transfer ke Deposit --}}
    <div class="bg-white border border-green-200 rounded-[12px] p-6 mb-6 shadow-gomad">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-green-50 border border-green-200 flex items-center justify-center text-lg">🔄</div>
            <div>
                <h3 class="font-bold text-[#111827]">Transfer ke Saldo Deposit</h3>
                <p class="text-sm text-gray-500 font-light">Pindahkan saldo dari booking selesai ke saldo deposit. <strong class="text-green-600">Tanpa biaya.</strong></p>
            </div>
        </div>
        <form action="{{ route('agency.wallet.transfer-deposit') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <div class="flex-1">
                <input type="number" name="amount" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition" placeholder="Nominal transfer" min="10000" max="{{ (int) $balanceSummary['available_balance'] }}" required>
                <p class="text-[10px] text-gray-400 mt-1 font-light">Minimal Rp 10.000 • Saldo tersedia: <strong>Rp {{ number_format($balanceSummary['available_balance'], 0, ',', '.') }}</strong></p>
            </div>
            <button type="submit" class="bg-[#BA1826] text-white px-6 py-3 rounded-[10px] font-semibold hover:bg-[#8A0F18] transition whitespace-nowrap">Transfer</button>
        </form>
    </div>

    {{-- Aksi --}}
    <div class="flex flex-wrap gap-3 mb-8">
        <a href="{{ route('agency.withdrawals.create') }}" class="btn-gomad-primary text-sm">Tarik Dana</a>
        <a href="{{ route('agency.withdrawals.index') }}" class="btn-gomad-outline text-sm">Riwayat Penarikan</a>
        <a href="{{ route('agency.wallet.topup') }}" class="bg-[#BA1826] text-white px-6 py-3 rounded-[10px] text-sm font-semibold hover:bg-[#8A0F18] transition inline-flex items-center gap-2">
            <span>💳</span> Top Up <span class="text-xs opacity-75 font-light">(Biaya admin Rp {{ number_format($topupAdminFee, 0, ',', '.') }})</span>
        </a>
    </div>

    {{-- Filter Mutasi --}}
    <div class="flex gap-2 mb-4 overflow-x-auto pb-2">
        <a href="{{ route('agency.wallet.index') }}" class="px-3 py-1.5 rounded-[8px] text-[10px] font-mono uppercase tracking-wider font-medium whitespace-nowrap border {{ !request('type') ? 'bg-[#BA1826] text-white border-[#BA1826]' : 'bg-[#F9FAFB] text-gray-600 border-[#E5E7EB]' }}">Semua</a>
        <a href="{{ route('agency.wallet.index', ['type' => 'topup']) }}" class="px-3 py-1.5 rounded-[8px] text-[10px] font-mono uppercase tracking-wider font-medium whitespace-nowrap border {{ request('type') == 'topup' ? 'bg-[#BA1826] text-white border-[#BA1826]' : 'bg-[#F9FAFB] text-gray-600 border-[#E5E7EB]' }}">Top Up</a>
        <a href="{{ route('agency.wallet.index', ['type' => 'booking']) }}" class="px-3 py-1.5 rounded-[8px] text-[10px] font-mono uppercase tracking-wider font-medium whitespace-nowrap border {{ request('type') == 'booking' ? 'bg-[#BA1826] text-white border-[#BA1826]' : 'bg-[#F9FAFB] text-gray-600 border-[#E5E7EB]' }}">Booking</a>
        <a href="{{ route('agency.wallet.index', ['type' => 'cod_schedule_hold']) }}" class="px-3 py-1.5 rounded-[8px] text-[10px] font-mono uppercase tracking-wider font-medium whitespace-nowrap border {{ request('type') == 'cod_schedule_hold' ? 'bg-[#BA1826] text-white border-[#BA1826]' : 'bg-[#F9FAFB] text-gray-600 border-[#E5E7EB]' }}">COD Hold</a>
        <a href="{{ route('agency.wallet.index', ['type' => 'cod_schedule_release']) }}" class="px-3 py-1.5 rounded-[8px] text-[10px] font-mono uppercase tracking-wider font-medium whitespace-nowrap border {{ request('type') == 'cod_schedule_release' ? 'bg-[#BA1826] text-white border-[#BA1826]' : 'bg-[#F9FAFB] text-gray-600 border-[#E5E7EB]' }}">COD Release</a>
        <a href="{{ route('agency.wallet.index', ['type' => 'transfer_to_deposit']) }}" class="px-3 py-1.5 rounded-[8px] text-[10px] font-mono uppercase tracking-wider font-medium whitespace-nowrap border {{ request('type') == 'transfer_to_deposit' ? 'bg-[#BA1826] text-white border-[#BA1826]' : 'bg-[#F9FAFB] text-gray-600 border-[#E5E7EB]' }}">Transfer</a>
        <a href="{{ route('agency.wallet.index', ['type' => 'withdrawal']) }}" class="px-3 py-1.5 rounded-[8px] text-[10px] font-mono uppercase tracking-wider font-medium whitespace-nowrap border {{ request('type') == 'withdrawal' ? 'bg-[#BA1826] text-white border-[#BA1826]' : 'bg-[#F9FAFB] text-gray-600 border-[#E5E7EB]' }}">Penarikan</a>
    </div>

    {{-- Riwayat Transaksi --}}
    <div>
        <h2 class="font-bold text-lg text-[#111827] mb-4">Riwayat Mutasi Saldo</h2>
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] shadow-gomad overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-[#F9FAFB] border-b border-[#E5E7EB]">
                        <tr>
                            <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Deskripsi</th>
                            <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-xs text-gray-500">Jumlah</th>
                            <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-xs text-gray-500">Saldo</th>
                            <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-xs text-gray-500">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E5E7EB]">
                        @forelse($transactions as $t)
                        <tr class="hover:bg-[#F9FAFB]">
                            <td class="px-4 py-3">
                                <span class="text-[#111827]">{{ $t->description }}</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @if($t->reference_type == 'topup')<span class="text-[10px] bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full border border-blue-200">Top Up</span>
                                    @elseif($t->reference_type == 'transfer_to_deposit')<span class="text-[10px] bg-green-50 text-green-700 px-2 py-0.5 rounded-full border border-green-200">Transfer ke Deposit</span>
                                    @elseif($t->reference_type == 'transfer_from_available')<span class="text-[10px] bg-green-50 text-green-700 px-2 py-0.5 rounded-full border border-green-200">Dari Saldo Tersedia</span>
                                    @elseif($t->reference_type == 'cod_schedule_hold')<span class="text-[10px] bg-orange-50 text-orange-700 px-2 py-0.5 rounded-full border border-orange-200">COD Schedule Hold</span>
                                    @elseif($t->reference_type == 'cod_schedule_release')<span class="text-[10px] bg-green-50 text-green-700 px-2 py-0.5 rounded-full border border-green-200">COD Schedule Release</span>
                                    @elseif($t->reference_type == 'cod_booking_hold')<span class="text-[10px] bg-orange-50 text-orange-700 px-2 py-0.5 rounded-full border border-orange-200">COD Booking Hold</span>
                                    @elseif($t->reference_type == 'cod_booking_release')<span class="text-[10px] bg-green-50 text-green-700 px-2 py-0.5 rounded-full border border-green-200">COD Booking Release</span>
                                    @elseif($t->reference_type == 'booking')<span class="text-[10px] bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full border border-purple-200">Booking</span>
                                    @elseif($t->reference_type == 'withdrawal')<span class="text-[10px] bg-red-50 text-red-700 px-2 py-0.5 rounded-full border border-red-200">Penarikan</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right {{ $t->type == 'credit' ? 'text-green-600' : 'text-red-600' }} font-medium">
                                {{ $t->type == 'credit' ? '+' : '-' }}Rp {{ number_format($t->amount, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-[#111827]">Rp {{ number_format($t->balance_after, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-xs text-gray-500 font-light">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 font-light">Belum ada transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Midtrans Snap untuk Top Up --}}
@if(session('snap_token'))
@push('scripts')
<script src="{{ config('gomad.midtrans.snap_url') }}" data-client-key="{{ config('gomad.midtrans.client_key') }}"></script>
<script>
snap.pay('{{ session('snap_token') }}', {
    onSuccess: function(result) { alert('Top Up berhasil!'); window.location.reload(); },
    onPending: function(result) { alert('Menunggu pembayaran...'); },
    onError: function(result) { alert('Pembayaran gagal.'); }
});
</script>
@endpush
@endif
@endsection