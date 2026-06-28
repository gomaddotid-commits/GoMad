@extends('layouts.agency')

@section('title', 'Transfer Penumpang')
@section('content')
@php
    $agency = auth()->user()->agency;
    $transferService = app(\App\Services\PassengerTransferService::class);
    $transfers = $transferService->getAgencyTransfers($agency->id);
    $outgoingTransfers = $transfers->where('from_agency_id', $agency->id);
    $incomingTransfers = $transfers->where('to_agency_id', $agency->id);
@endphp

<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">🔄 Transfer Penumpang</h1>

    <!-- Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 text-sm text-blue-800">
        <p class="font-semibold mb-2">💡 Tentang Transfer Penumpang</p>
        <ul class="list-disc list-inside space-y-1">
            <li>Transfer penumpang memungkinkan Anda memindahkan booking ke jadwal lain</li>
            <li>Berguna saat mobil sepi untuk menghindari kerugian</li>
            <li>Customer tetap berangkat, hanya pindah mobil/agency</li>
            <li>Biaya transfer ditentukan oleh agency penerima</li>
        </ul>
    </div>

    <!-- Tab -->
    <div class="flex border-b mb-6" id="transferTabs">
        <button onclick="showTab('incoming')" class="tab-btn px-4 py-2 text-sm font-semibold border-b-2 border-primary text-primary">
            📥 Transfer Masuk ({{ $incomingTransfers->count() }})
        </button>
        <button onclick="showTab('outgoing')" class="tab-btn px-4 py-2 text-sm font-semibold border-b-2 border-transparent text-gray-500">
            📤 Transfer Keluar ({{ $outgoingTransfers->count() }})
        </button>
    </div>

    <!-- Transfer Masuk -->
    <div id="tab-incoming" class="tab-content">
        @if($incomingTransfers->isEmpty())
        <div class="bg-white rounded-xl shadow p-8 text-center text-gray-500">Belum ada transfer masuk.</div>
        @else
        <div class="space-y-4">
            @foreach($incomingTransfers as $transfer)
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="font-bold text-lg">
                            Dari: {{ $transfer->fromAgency->agency_name }}
                        </h3>
                        <p class="text-sm text-gray-500">
                            {{ $transfer->fromSchedule->route->route_name }} | 
                            {{ $transfer->fromSchedule->departure_date->format('d M Y') }}
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-{{ $transfer->status_color }}-100 text-{{ $transfer->status_color }}-800">
                        {{ $transfer->status_label }}
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-4 text-sm mb-4">
                    <div class="bg-gray-50 rounded p-3">
                        <span class="text-gray-500">Penumpang</span>
                        <p class="font-bold text-lg">{{ $transfer->total_passengers }}</p>
                    </div>
                    <div class="bg-gray-50 rounded p-3">
                        <span class="text-gray-500">Biaya Transfer</span>
                        <p class="font-bold text-green-600">Rp {{ number_format($transfer->total_transfer_fee, 0, ',', '.') }}</p>
                    </div>
                    <div class="bg-gray-50 rounded p-3">
                        <span class="text-gray-500">Nilai Booking</span>
                        <p class="font-bold text-blue-600">Rp {{ number_format($transfer->total_booking_value, 0, ',', '.') }}</p>
                    </div>
                </div>

                @if($transfer->status == 'pending')
                <div class="flex gap-2">
                    <form action="{{ route('agency.transfers.approve', $transfer) }}" method="POST">
                        @csrf
                        <button class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600">✅ TERIMA</button>
                    </form>
                    <button onclick="rejectTransfer({{ $transfer->id }})" class="bg-red-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600">❌ TOLAK</button>
                </div>
                @endif

                @if($transfer->status == 'rejected' && $transfer->rejection_reason)
                <p class="text-sm text-red-600 mt-2">Alasan: {{ $transfer->rejection_reason }}</p>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- Transfer Keluar -->
    <div id="tab-outgoing" class="tab-content" style="display:none;">
        @if($outgoingTransfers->isEmpty())
        <div class="bg-white rounded-xl shadow p-8 text-center text-gray-500">
            <p>Belum ada transfer keluar.</p>
            <a href="{{ route('agency.schedules.index') }}" class="text-primary hover:underline mt-2 inline-block">Lihat Jadwal</a>
        </div>
        @else
        <div class="space-y-4">
            @foreach($outgoingTransfers as $transfer)
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="font-bold text-lg">
                            Ke: {{ $transfer->toAgency->agency_name }}
                        </h3>
                        <p class="text-sm text-gray-500">
                            {{ $transfer->toSchedule->route->route_name }} | 
                            {{ $transfer->toSchedule->departure_date->format('d M Y') }}
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-{{ $transfer->status_color }}-100 text-{{ $transfer->status_color }}-800">
                        {{ $transfer->status_label }}
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-4 text-sm mb-4">
                    <div class="bg-gray-50 rounded p-3">
                        <span class="text-gray-500">Penumpang</span>
                        <p class="font-bold text-lg">{{ $transfer->total_passengers }}</p>
                    </div>
                    <div class="bg-gray-50 rounded p-3">
                        <span class="text-gray-500">Biaya Transfer</span>
                        <p class="font-bold text-red-600">Rp {{ number_format($transfer->total_transfer_fee, 0, ',', '.') }}</p>
                    </div>
                    <div class="bg-gray-50 rounded p-3">
                        <span class="text-gray-500">Nilai Booking</span>
                        <p class="font-bold text-blue-600">Rp {{ number_format($transfer->total_booking_value, 0, ',', '.') }}</p>
                    </div>
                </div>

                @if($transfer->status == 'pending')
                <form action="{{ route('agency.transfers.cancel', $transfer) }}" method="POST">
                    @csrf
                    <button class="bg-gray-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-600">🚫 BATALKAN</button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" style="display:none;" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-md w-full">
        <h3 class="font-bold text-lg mb-2">❌ Tolak Transfer</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <textarea name="reason" rows="3" class="w-full px-4 py-3 border rounded-lg mb-4" placeholder="Alasan penolakan..." required></textarea>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-red-500 text-white py-2 rounded-lg font-semibold">KIRIM</button>
                <button type="button" onclick="closeRejectModal()" class="flex-1 border py-2 rounded-lg">BATAL</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.getElementById('tab-' + tab).style.display = 'block';
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-primary', 'text-primary');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    event.target.classList.add('border-primary', 'text-primary');
    event.target.classList.remove('border-transparent', 'text-gray-500');
}

function rejectTransfer(id) {
    document.getElementById('rejectForm').action = '/agency/transfers/' + id + '/reject';
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}
</script>
@endpush
@endsection