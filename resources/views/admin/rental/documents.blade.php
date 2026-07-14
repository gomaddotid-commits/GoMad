@extends('layouts.admin')

@section('title', 'Verifikasi Dokumen')
@section('content')

@php
    $statusFilter = request('status', 'pending');
    
    $query = \App\Models\CustomerDocument::with('user')
        ->where('verification_status', $statusFilter)
        ->latest();
    
    $documents = $query->paginate(20);
    
    $pendingCount = \App\Models\CustomerDocument::where('verification_status', 'pending')->count();
    $verifiedCount = \App\Models\CustomerDocument::where('verification_status', 'verified')->count();
    $rejectedCount = \App\Models\CustomerDocument::where('verification_status', 'rejected')->count();
@endphp

<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 border-b border-[#E5E5E5] pb-3">
        <h1 class="text-2xl font-bold text-[#111111]">Verifikasi Dokumen Customer</h1>
        <a href="{{ route('admin.rental.dashboard') }}" class="text-[#C1121F] text-sm hover:underline font-medium">
            ← Dashboard Rental
        </a>
    </div>

    {{-- Statistik --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <a href="{{ route('admin.rental.documents', ['status' => 'pending']) }}" 
           class="bg-white border border-[#E5E5E5] rounded-[12px] p-5 text-center shadow-sm hover:border-yellow-500 transition {{ $statusFilter == 'pending' ? 'border-yellow-500 bg-yellow-50' : '' }}">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Pending</p>
            <p class="text-3xl font-bold text-yellow-600">{{ $pendingCount }}</p>
        </a>
        <a href="{{ route('admin.rental.documents', ['status' => 'verified']) }}" 
           class="bg-white border border-[#E5E5E5] rounded-[12px] p-5 text-center shadow-sm hover:border-green-500 transition {{ $statusFilter == 'verified' ? 'border-green-500 bg-green-50' : '' }}">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Verified</p>
            <p class="text-3xl font-bold text-green-600">{{ $verifiedCount }}</p>
        </a>
        <a href="{{ route('admin.rental.documents', ['status' => 'rejected']) }}" 
           class="bg-white border border-[#E5E5E5] rounded-[12px] p-5 text-center shadow-sm hover:border-red-500 transition {{ $statusFilter == 'rejected' ? 'border-red-500 bg-red-50' : '' }}">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Ditolak</p>
            <p class="text-3xl font-bold text-red-600">{{ $rejectedCount }}</p>
        </a>
    </div>

    {{-- Tabel Dokumen --}}
    @if($documents->isEmpty())
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-12 text-center shadow-sm">
        <div class="w-16 h-16 bg-[#C1121F]/5 rounded-[12px] flex items-center justify-center mx-auto mb-4 border border-[#E5E5E5]">
            <span class="text-2xl">📄</span>
        </div>
        <p class="text-gray-500 text-lg font-light">Tidak ada dokumen dengan status "{{ $statusFilter }}".</p>
    </div>
    @else
    <div class="space-y-6">
        @foreach($documents as $doc)
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm hover:border-[#C1121F] transition-colors">
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-4 mb-4">
                <div>
                    <h3 class="font-bold text-lg text-[#111111]">{{ $doc->user->name ?? 'Unknown' }}</h3>
                    <p class="text-sm text-gray-500 font-light">{{ $doc->user->email ?? '-' }} • {{ $doc->user->phone ?? '-' }}</p>
                    <p class="text-xs text-gray-400 mt-1 font-light">Disubmit: {{ $doc->created_at->format('d M Y H:i') }}</p>
                    
                    @if($doc->verification_status == 'rejected' && $doc->rejection_reason)
                    <div class="mt-2 bg-red-50 border border-red-200 rounded-lg p-2">
                        <p class="text-xs text-red-700 font-light"><strong>Alasan:</strong> {{ $doc->rejection_reason }}</p>
                    </div>
                    @endif
                </div>
                
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-[10px] font-mono uppercase tracking-wider border
                        @if($doc->verification_status == 'verified') bg-green-50 text-green-700 border-green-200
                        @elseif($doc->verification_status == 'rejected') bg-red-50 text-red-700 border-red-200
                        @else bg-yellow-50 text-yellow-700 border-yellow-200 @endif">
                        {{ $doc->verification_status }}
                    </span>
                </div>
            </div>
            
            {{-- Detail Dokumen --}}
            <div class="grid md:grid-cols-3 gap-4 mb-4">
                {{-- KTP --}}
                <div class="border border-[#E5E5E5] rounded-[12px] p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-mono uppercase tracking-wider text-[10px] text-gray-500">🪪 KTP</span>
                        <span class="text-[10px] font-mono {{ $doc->ktp_verified ? 'text-green-600' : 'text-yellow-600' }}">
                            {{ $doc->ktp_verified ? '✅ Verified' : '⏳ Pending' }}
                        </span>
                    </div>
                    <p class="text-sm font-mono text-[#111111]">{{ $doc->ktp_number ?? '-' }}</p>
                    @if($doc->ktp_photo)
                    <a href="{{ $doc->ktp_photo }}" target="_blank" class="text-xs text-[#C1121F] hover:underline mt-1 inline-block">Lihat Foto KTP →</a>
                    @endif
                </div>
                
                {{-- SIM --}}
                <div class="border border-[#E5E5E5] rounded-[12px] p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-mono uppercase tracking-wider text-[10px] text-gray-500">🚗 SIM</span>
                        <span class="text-[10px] font-mono {{ $doc->sim_verified ? 'text-green-600' : 'text-yellow-600' }}">
                            {{ $doc->sim_verified ? '✅ Verified' : '⏳ Pending' }}
                        </span>
                    </div>
                    <p class="text-sm font-mono text-[#111111]">{{ $doc->sim_number ?? '-' }}</p>
                    @if($doc->sim_photo)
                    <a href="{{ $doc->sim_photo }}" target="_blank" class="text-xs text-[#C1121F] hover:underline mt-1 inline-block">Lihat Foto SIM →</a>
                    @endif
                </div>
                
                {{-- NPWP --}}
                <div class="border border-[#E5E5E5] rounded-[12px] p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-mono uppercase tracking-wider text-[10px] text-gray-500">📄 NPWP</span>
                        <span class="text-[10px] font-mono {{ $doc->npwp_verified ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $doc->npwp_verified ? '✅ Verified' : ($doc->npwp_number ? '⏳ Pending' : '⚪ Opsional') }}
                        </span>
                    </div>
                    <p class="text-sm font-mono text-[#111111]">{{ $doc->npwp_number ?? '-' }}</p>
                    @if($doc->npwp_photo)
                    <a href="{{ $doc->npwp_photo }}" target="_blank" class="text-xs text-[#C1121F] hover:underline mt-1 inline-block">Lihat Foto NPWP →</a>
                    @endif
                </div>
            </div>
            
            {{-- Tombol Aksi (hanya untuk pending) --}}
            @if($doc->verification_status == 'pending')
            <div class="flex gap-3 border-t border-[#E5E5E5] pt-4">
                <form action="{{ route('admin.rental.documents.verify', $doc) }}" method="POST" class="flex-1">
                    @csrf
                    <input type="hidden" name="ktp_verified" value="1">
                    <input type="hidden" name="sim_verified" value="1">
                    @if($doc->npwp_number)
                    <input type="hidden" name="npwp_verified" value="1">
                    @endif
                    <button type="submit" class="w-full bg-[#C1121F] text-white py-2 rounded-[12px] font-semibold hover:bg-[#8A0F18] transition text-sm">
                        ✅ VERIFIKASI SEMUA
                    </button>
                </form>
                
                <button type="button" onclick="openRejectModal('{{ $doc->id }}')" 
                        class="flex-1 bg-[#C1121F] text-white py-2 rounded-[12px] font-semibold hover:bg-[#8A0F18] transition text-sm">
                    ❌ TOLAK
                </button>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    
    <div class="mt-6">
        {{ $documents->links() }}
    </div>
    @endif
</div>

{{-- MODAL REJECT --}}
<div id="rejectModal" style="display:none;" class="fixed inset-0 bg-[#111111]/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-[12px] shadow-xl p-6 max-w-md w-full border border-[#E5E5E5]">
        <h3 class="font-bold text-lg text-[#111111] mb-2">Tolak Dokumen</h3>
        <p class="text-sm text-gray-500 font-light mb-4">Tulis alasan penolakan</p>
        <form id="rejectForm" method="POST">
            @csrf
            <textarea name="reason" rows="3" class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] mb-4" placeholder="Alasan penolakan..." required></textarea>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-[#C1121F] text-white py-2 rounded-[12px] font-semibold hover:bg-[#8A0F18]">Kirim</button>
                <button type="button" onclick="closeRejectModal()" class="flex-1 border border-[#E5E5E5] py-2 rounded-[12px] font-medium hover:bg-[#F5F5F5]">Batal</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openRejectModal(docId) {
    document.getElementById('rejectForm').action = '/admin/rental/documents/' + docId + '/reject';
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});
</script>
@endpush
@endsection