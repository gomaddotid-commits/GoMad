@extends('layouts.admin')

@section('title', 'Detail Warung')
@section('content')
<div class="max-w-5xl">
    <a href="{{ route('admin.payment-agents.index') }}" class="text-[#C1121F] text-sm mb-4 inline-block hover:underline">← Kembali</a>

    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-[#111111]">{{ $agent->agent_name }}</h1>
                <p class="text-gray-500 font-light">{{ $agent->full_address }}</p>
                @if($agent->is_verified)
                <span class="inline-block mt-1 px-2 py-0.5 bg-green-50 text-green-700 text-[10px] font-mono uppercase tracking-wider rounded-full border border-green-200">Terverifikasi</span>
                @else
                <span class="inline-block mt-1 px-2 py-0.5 bg-yellow-50 text-yellow-700 text-[10px] font-mono uppercase tracking-wider rounded-full border border-yellow-200">Pending</span>
                @endif
            </div>
            <div class="flex gap-2">
                @if(!$agent->is_verified)
                <form action="{{ route('admin.payment-agents.verify', $agent) }}" method="POST">
                    @csrf
                    <button class="bg-[#C1121F] text-white px-4 py-2 rounded-[12px] text-sm font-medium hover:bg-[#8A0F18]">Verifikasi</button>
                </form>
                <button onclick="openRejectModal()" class="bg-[#C1121F] text-white px-4 py-2 rounded-[12px] text-sm font-medium hover:bg-[#8A0F18]">Tolak</button>
                @endif
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3"><span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Pemilik</span><span class="font-medium text-[#111111]">{{ $agent->owner_name }} ({{ $agent->owner_phone }})</span></div>
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3"><span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Penjaga</span><span class="font-medium text-[#111111]">{{ $agent->guard_name ?? '-' }}</span></div>
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3"><span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Kecamatan</span><span class="font-medium text-[#111111]">{{ $agent->kecamatan ?? '-' }}</span></div>
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3"><span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Total Transaksi</span><span class="font-medium text-[#111111]">{{ $agent->total_transactions }}</span></div>
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3"><span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Total Komisi</span><span class="font-medium text-[#C1121F]">Rp {{ number_format($agent->total_commission, 0, ',', '.') }}</span></div>
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3"><span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Sisa Settlement</span><span class="font-medium text-yellow-600">Rp {{ number_format($agent->balance_to_settle, 0, ',', '.') }}</span></div>
        </div>

        @if($agent->rejection_reason)
        <div class="mt-4 bg-red-50 border border-red-200 rounded-[12px] p-3 text-sm text-red-700 font-light">
            <strong class="font-medium">Alasan Penolakan:</strong> {{ $agent->rejection_reason }}
        </div>
        @endif
    </div>
</div>

{{-- MODAL REJECT --}}
<div id="rejectModal" style="display:none;" class="fixed inset-0 bg-[#111111]/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-[12px] shadow-xl p-6 max-w-md w-full border border-[#E5E5E5]">
        <h3 class="font-bold text-lg text-[#111111] mb-2">Tolak Pengajuan Warung</h3>
        <p class="text-sm text-gray-500 font-light mb-4">Tulis alasan penolakan untuk {{ $agent->agent_name }}</p>
        <form action="{{ route('admin.payment-agents.reject', $agent) }}" method="POST">
            @csrf
            <textarea name="reason" rows="3" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition mb-4" placeholder="Alasan penolakan..." required></textarea>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-[#C1121F] text-white py-2 rounded-[12px] font-semibold hover:bg-[#8A0F18]">Kirim</button>
                <button type="button" onclick="closeRejectModal()" class="flex-1 border border-[#E5E5E5] py-2 rounded-[12px]">Batal</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openRejectModal() { document.getElementById('rejectModal').style.display = 'flex'; }
function closeRejectModal() { document.getElementById('rejectModal').style.display = 'none'; }
</script>
@endpush
@endsection