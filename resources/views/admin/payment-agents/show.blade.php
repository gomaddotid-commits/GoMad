@extends('layouts.admin')

@section('title', 'Detail Warung')
@section('content')
<div class="max-w-5xl">
    <a href="{{ route('admin.payment-agents.index') }}" class="text-primary-600 text-sm mb-4 inline-block">← Kembali</a>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary">{{ $agent->agent_name }}</h1>
                <p class="text-gray-500">{{ $agent->address }}</p>
                @if($agent->is_verified)
                <span class="inline-block mt-1 px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Terverifikasi</span>
                @else
                <span class="inline-block mt-1 px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs rounded-full">Pending</span>
                @endif
            </div>
            <div class="flex gap-2">
                @if(!$agent->is_verified)
                <form action="{{ route('admin.payment-agents.verify', $agent) }}" method="POST">
                    @csrf
                    <button class="bg-green-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-green-600">Verifikasi</button>
                </form>
                <button onclick="openRejectModal()" class="bg-red-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-red-600">Tolak</button>
                @endif
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">Pemilik:</span> <span class="font-medium">{{ $agent->owner_name }} ({{ $agent->owner_phone }})</span></div>
            <div><span class="text-gray-500">Penjaga:</span> <span class="font-medium">{{ $agent->guard_name ?? '-' }}</span></div>
            <div><span class="text-gray-500">Kecamatan:</span> <span class="font-medium">{{ $agent->kecamatan ?? '-' }}</span></div>
            <div><span class="text-gray-500">Total Transaksi:</span> <span class="font-medium">{{ $agent->total_transactions }}</span></div>
            <div><span class="text-gray-500">Total Komisi:</span> <span class="font-medium text-green-600">Rp {{ number_format($agent->total_commission, 0, ',', '.') }}</span></div>
            <div><span class="text-gray-500">Sisa Settlement:</span> <span class="font-medium text-yellow-600">Rp {{ number_format($agent->balance_to_settle, 0, ',', '.') }}</span></div>
        </div>

        @if($agent->rejection_reason)
        <div class="mt-4 bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700">
            <strong>Alasan Penolakan:</strong> {{ $agent->rejection_reason }}
        </div>
        @endif
    </div>
</div>

{{-- MODAL REJECT --}}
<div id="rejectModal" style="display:none;" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 max-w-md w-full">
        <h3 class="font-bold text-lg mb-2">Tolak Pengajuan Warung</h3>
        <p class="text-sm text-gray-500 mb-4">Tulis alasan penolakan untuk {{ $agent->agent_name }}</p>
        <form action="{{ route('admin.payment-agents.reject', $agent) }}" method="POST">
            @csrf
            <textarea name="reason" rows="3" class="w-full px-4 py-3 border rounded-xl mb-4 focus:ring-2 focus:ring-red-500" placeholder="Alasan penolakan..." required></textarea>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-red-500 text-white py-2 rounded-xl font-semibold hover:bg-red-600">Kirim</button>
                <button type="button" onclick="closeRejectModal()" class="flex-1 border py-2 rounded-xl">Batal</button>
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