@extends('layouts.admin')

@section('title', 'Detail Agency')
@section('content')
<div class="max-w-5xl">
    <a href="{{ route('admin.agencies.index') }}" class="text-primary-600 text-sm mb-4 inline-block">← Kembali</a>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-secondary">{{ $agency->agency_name }}</h1>
                <p class="text-gray-500 text-sm">{{ $agency->slug }}</p>
                @if($agency->is_verified)
                <span class="inline-block mt-1 px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Terverifikasi</span>
                @else
                <span class="inline-block mt-1 px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs rounded-full">Pending</span>
                @endif
            </div>
            <div class="flex gap-2">
                @if(!$agency->is_verified)
                <form action="{{ route('admin.agencies.verify', $agency) }}" method="POST">
                    @csrf
                    <button class="bg-green-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-green-600 transition">Verifikasi</button>
                </form>
                <button onclick="openRejectModal()" class="bg-red-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-red-600 transition">Tolak</button>
                @endif
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">Pemilik:</span> <span class="font-medium">{{ $agency->user->name ?? '-' }}</span></div>
            <div><span class="text-gray-500">Email:</span> <span class="font-medium">{{ $agency->user->email ?? '-' }}</span></div>
            <div><span class="text-gray-500">Alamat:</span> <span class="font-medium">{{ $agency->address ?? '-' }}</span></div>
            <div><span class="text-gray-500">Tahun Berdiri:</span> <span class="font-medium">{{ $agency->founded_year ?? '-' }}</span></div>
            <div><span class="text-gray-500">Rating:</span> <span class="font-medium">⭐ {{ number_format((float)$agency->rating, 1) }}</span></div>
            <div><span class="text-gray-500">Total Booking:</span> <span class="font-medium">{{ $agency->total_bookings }}</span></div>
        </div>

        @if($agency->business_license)
        <div class="mt-4 p-4 bg-blue-50 rounded-xl">
            <a href="{{  $agency->business_license }}" target="_blank" class="text-blue-600 text-sm hover:underline">Lihat Dokumen PDF</a>
        </div>
        @endif

        {{-- Riwayat Verifikasi --}}
        @php $verifications = $agency->verifications()->latest()->get(); @endphp
        @if($verifications->isNotEmpty())
        <div class="mt-4">
            <h3 class="font-semibold mb-2">Riwayat Verifikasi</h3>
            <div class="space-y-2">
                @foreach($verifications as $v)
                <div class="text-sm p-3 rounded-xl 
                    @if($v->status == 'approved') bg-green-50 border border-green-200
                    @elseif($v->status == 'rejected') bg-red-50 border border-red-200
                    @else bg-yellow-50 border border-yellow-200 @endif">
                    <div class="flex justify-between">
                        <span class="font-medium">
                            @if($v->status == 'approved') ✅ Disetujui
                            @elseif($v->status == 'rejected') ❌ Ditolak
                            @else ⏳ Pending @endif
                        </span>
                        <span class="text-gray-500">{{ $v->created_at->format('d M Y H:i') }}</span>
                    </div>
                    @if($v->rejection_reason)<p class="text-red-700 mt-1">Alasan: {{ $v->rejection_reason }}</p>@endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

{{-- MODAL REJECT --}}
<div id="rejectModal" style="display:none;" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl p-6 max-w-md w-full">
        <h3 class="font-bold text-lg mb-2">Tolak Pengajuan</h3>
        <p class="text-sm text-gray-500 mb-4">Tulis alasan penolakan untuk {{ $agency->agency_name }}</p>
        <form action="{{ route('admin.agencies.reject', $agency) }}" method="POST">
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
document.getElementById('rejectModal').addEventListener('click', function(e) { if (e.target === this) closeRejectModal(); });
</script>
@endpush
@endsection