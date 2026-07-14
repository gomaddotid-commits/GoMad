@props([
    'vehicleSetting' => null,
    'mode' => 'create', // 'create' atau 'show'
    'showCheckboxes' => true,
])

@php
    // Syarat & Ketentuan dari agency (jika ada)
    $terms = $vehicleSetting->terms_conditions ?? [];
    if (is_string($terms)) {
        $terms = json_decode($terms, true) ?? [];
    }
    
    // Fallback ke system default jika kosong
    if (empty($terms)) {
        $terms = [
            'Penyewa wajib memiliki KTP dan SIM yang masih berlaku.',
            'Kendaraan hanya boleh digunakan untuk keperluan yang sah sesuai hukum Indonesia.',
            'Penyewa bertanggung jawab penuh atas kerusakan, kehilangan, atau kecelakaan yang terjadi selama masa sewa.',
            'Kendaraan harus dikembalikan dalam kondisi bersih dan tangki bensin terisi penuh.',
            'Keterlambatan pengembalian akan dikenakan denda sesuai ketentuan agency.',
            'Penyewa dilarang meminjamkan atau menyewakan kembali kendaraan kepada pihak lain.',
            'Segala pelanggaran lalu lintas menjadi tanggung jawab penyewa.',
        ];
    }
    
    // Kebijakan Pembatalan & Refund (system default)
    $refundPolicy = $vehicleSetting->refund_policy ?? [];
    if (is_string($refundPolicy)) {
        $refundPolicy = json_decode($refundPolicy, true) ?? [];
    }
    
    if (empty($refundPolicy)) {
        $refundPolicy = [
            'Pembatalan sebelum pembayaran: Tidak dikenakan biaya.',
            'Pembatalan setelah pembayaran: Dikenakan biaya 25% dari total sewa.',
            'Refund akan diproses dalam 1-14 hari kerja ke rekening yang terdaftar.',
            'Pembatalan setelah mobil diambil (status aktif): Tidak dapat dibatalkan.',
            'Jika terjadi force majeure (bencana alam, dll), kebijakan khusus berlaku.',
            'Deposit akan dikembalikan penuh setelah mobil kembali tanpa kerusakan.',
        ];
    }
@endphp

<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    
    {{-- ═══════════════════════════════════ --}}
    {{-- CHECKBOX SYARAT & KETENTUAN --}}
    {{-- ═══════════════════════════════════ --}}
    @if($showCheckboxes)
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4">
        <label class="flex items-start gap-3 cursor-pointer" id="termsCheckboxLabel">
            <input type="checkbox" 
                   id="agreeTerms" 
                   name="agree_terms" 
                   value="1"
                   class="mt-0.5 w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]"
                   onchange="toggleSubmitButton()">
            <div class="flex-1">
                <span class="text-sm font-medium text-[#111111]">
                    Saya telah membaca dan menyetujui 
                </span>
                <button type="button" 
                        onclick="openModal('termsModal')" 
                        class="text-[#C1121F] underline font-medium text-sm hover:text-[#8A0F18] transition">
                    Syarat & Ketentuan
                </button>
                <span class="text-sm font-medium text-[#111111]"> rental ini</span>
            </div>
        </label>
    </div>
    @endif

    {{-- ═══════════════════════════════════ --}}
    {{-- CHECKBOX KEBIJAKAN PEMBATALAN --}}
    {{-- ═══════════════════════════════════ --}}
    @if($showCheckboxes)
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4">
        <label class="flex items-start gap-3 cursor-pointer" id="refundCheckboxLabel">
            <input type="checkbox" 
                   id="agreeRefund" 
                   name="agree_refund" 
                   value="1"
                   class="mt-0.5 w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]"
                   onchange="toggleSubmitButton()">
            <div class="flex-1">
                <span class="text-sm font-medium text-[#111111]">
                    Saya memahami dan menyetujui 
                </span>
                <button type="button" 
                        onclick="openModal('refundModal')" 
                        class="text-[#C1121F] underline font-medium text-sm hover:text-[#8A0F18] transition">
                    Kebijakan Pembatalan & Refund
                </button>
            </div>
        </label>
        {{-- Ringkasan refund --}}
        <div class="mt-2 ml-8 text-xs text-gray-500 space-y-0.5 font-light">
            <p>• Pembatalan setelah bayar: biaya <strong class="text-[#C1121F]">25%</strong></p>
            <p>• Refund diproses <strong class="text-[#C1121F]">1-14 hari kerja</strong></p>
            <p>• Tidak bisa dibatalkan setelah mobil diambil</p>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════ --}}
    {{-- MODAL: SYARAT & KETENTUAN --}}
    {{-- ═══════════════════════════════════ --}}
    <div id="termsModal" class="fixed inset-0 bg-[#111111]/50 z-50 hidden items-center justify-center p-4" 
         style="display: none;">
        <div class="bg-white rounded-[12px] shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-y-auto border border-[#E5E5E5]">
            {{-- Header --}}
            <div class="sticky top-0 bg-white border-b border-[#E5E5E5] p-6 rounded-t-[12px] z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[#C1121F]/10 rounded-[12px] flex items-center justify-center text-xl border border-[#E5E5E5]">📄</div>
                    <div>
                        <h3 class="font-bold text-lg text-[#111111]">Syarat & Ketentuan Rental</h3>
                        <p class="text-xs text-gray-500 font-light">
                            @if($vehicleSetting && $vehicleSetting->vehicle)
                                {{ $vehicleSetting->vehicle->brand }} {{ $vehicleSetting->vehicle->model }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            
            {{-- Content --}}
            <div class="p-6 space-y-4">
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4">
                    <p class="text-xs text-gray-500 font-light mb-3">
                        Dengan menyewa kendaraan ini, Anda menyetujui syarat dan ketentuan berikut:
                    </p>
                    <ol class="list-decimal list-inside space-y-3 text-sm text-[#111111]">
                        @foreach($terms as $index => $term)
                        <li class="font-light leading-relaxed">{{ $term }}</li>
                        @endforeach
                    </ol>
                </div>
                
                {{-- Checkbox di dalam modal --}}
                <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" 
                               id="agreeTermsInModal" 
                               class="mt-0.5 w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]"
                               onchange="syncCheckbox('agreeTerms', 'agreeTermsInModal')">
                        <span class="text-sm text-[#111111] font-light">
                            Saya telah membaca, memahami, dan menyetujui seluruh syarat & ketentuan di atas
                        </span>
                    </label>
                </div>
            </div>
            
            {{-- Footer --}}
            <div class="sticky bottom-0 bg-white border-t border-[#E5E5E5] p-4 rounded-b-[12px] flex gap-3 justify-end">
                <button type="button" 
                        onclick="closeModal('termsModal')" 
                        class="px-6 py-2.5 border border-[#E5E5E5] rounded-[12px] text-sm font-medium hover:bg-[#F5F5F5] transition">
                    Tutup
                </button>
                <button type="button" 
                        onclick="agreeAndClose('termsModal', 'agreeTerms', 'agreeTermsInModal')" 
                        class="px-6 py-2.5 bg-[#C1121F] text-white rounded-[12px] text-sm font-semibold hover:bg-[#8A0F18] transition">
                    Setuju & Tutup
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════ --}}
    {{-- MODAL: KEBIJAKAN PEMBATALAN --}}
    {{-- ═══════════════════════════════════ --}}
    <div id="refundModal" class="fixed inset-0 bg-[#111111]/50 z-50 hidden items-center justify-center p-4" 
         style="display: none;">
        <div class="bg-white rounded-[12px] shadow-2xl max-w-2xl w-full max-h-[85vh] overflow-y-auto border border-[#E5E5E5]">
            {{-- Header --}}
            <div class="sticky top-0 bg-white border-b border-[#E5E5E5] p-6 rounded-t-[12px] z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-50 rounded-[12px] flex items-center justify-center text-xl border border-orange-200">🔄</div>
                    <div>
                        <h3 class="font-bold text-lg text-[#111111]">Kebijakan Pembatalan & Refund</h3>
                        <p class="text-xs text-gray-500 font-light">Mohon dibaca dengan seksama sebelum melakukan booking</p>
                    </div>
                </div>
            </div>
            
            {{-- Content --}}
            <div class="p-6 space-y-4">
                {{-- Biaya Pembatalan --}}
                <div class="bg-red-50 border border-red-200 rounded-[12px] p-4">
                    <h4 class="font-mono uppercase tracking-wider text-xs font-bold text-red-800 mb-2">⚠️ Biaya Pembatalan</h4>
                    <div class="text-sm text-red-700 space-y-2 font-light">
                        <p>• <strong>Sebelum pembayaran:</strong> Gratis, tidak ada biaya.</p>
                        <p>• <strong>Setelah pembayaran:</strong> Dikenakan biaya <strong>25%</strong> dari total sewa.</p>
                        <p>• <strong>Setelah mobil diambil:</strong> Tidak dapat dibatalkan.</p>
                    </div>
                </div>
                
                {{-- Kebijakan Refund --}}
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4">
                    <h4 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-2">📋 Kebijakan Refund</h4>
                    <ol class="list-decimal list-inside space-y-3 text-sm text-[#111111]">
                        @foreach($refundPolicy as $index => $policy)
                        <li class="font-light leading-relaxed">{{ $policy }}</li>
                        @endforeach
                    </ol>
                </div>
                
                {{-- Checkbox di dalam modal --}}
                <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" 
                               id="agreeRefundInModal" 
                               class="mt-0.5 w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F] focus:ring-[#C1121F]"
                               onchange="syncCheckbox('agreeRefund', 'agreeRefundInModal')">
                        <span class="text-sm text-[#111111] font-light">
                            Saya telah membaca, memahami, dan menyetujui kebijakan pembatalan & refund di atas
                        </span>
                    </label>
                </div>
            </div>
            
            {{-- Footer --}}
            <div class="sticky bottom-0 bg-white border-t border-[#E5E5E5] p-4 rounded-b-[12px] flex gap-3 justify-end">
                <button type="button" 
                        onclick="closeModal('refundModal')" 
                        class="px-6 py-2.5 border border-[#E5E5E5] rounded-[12px] text-sm font-medium hover:bg-[#F5F5F5] transition">
                    Tutup
                </button>
                <button type="button" 
                        onclick="agreeAndClose('refundModal', 'agreeRefund', 'agreeRefundInModal')" 
                        class="px-6 py-2.5 bg-[#C1121F] text-white rounded-[12px] text-sm font-semibold hover:bg-[#8A0F18] transition">
                    Setuju & Tutup
                </button>
            </div>
        </div>
    </div>

</div>

{{-- SCRIPTS --}}
@if($showCheckboxes)
@push('scripts')
<script>
// ═══════════════════════════════════
// MODAL FUNCTIONS
// ═══════════════════════════════════

function openModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    var modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function agreeAndClose(modalId, mainCheckboxId, modalCheckboxId) {
    // Centang checkbox di modal
    var modalCheckbox = document.getElementById(modalCheckboxId);
    if (modalCheckbox && !modalCheckbox.checked) {
        modalCheckbox.checked = true;
    }
    
    // Sync ke checkbox utama
    var mainCheckbox = document.getElementById(mainCheckboxId);
    if (mainCheckbox) {
        mainCheckbox.checked = true;
    }
    
    // Update tombol submit
    toggleSubmitButton();
    
    // Tutup modal
    closeModal(modalId);
}

// Sync checkbox modal ke checkbox utama
function syncCheckbox(mainCheckboxId, modalCheckboxId) {
    var mainCheckbox = document.getElementById(mainCheckboxId);
    var modalCheckbox = document.getElementById(modalCheckboxId);
    
    if (mainCheckbox && modalCheckbox) {
        mainCheckbox.checked = modalCheckbox.checked;
        toggleSubmitButton();
    }
}

// Toggle tombol submit
function toggleSubmitButton() {
    var agreeTerms = document.getElementById('agreeTerms');
    var agreeRefund = document.getElementById('agreeRefund');
    var btnSubmit = document.getElementById('btnSubmit');
    
    if (!agreeTerms || !agreeRefund || !btnSubmit) return;
    
    if (agreeTerms.checked && agreeRefund.checked) {
        btnSubmit.disabled = false;
        btnSubmit.className = 'w-full bg-[#C1121F] text-white py-4 rounded-[12px] font-bold text-lg hover:bg-[#8A0F18] transition cursor-pointer';
    } else {
        btnSubmit.disabled = true;
        btnSubmit.className = 'w-full bg-[#E5E5E5] text-gray-500 py-4 rounded-[12px] font-bold text-lg cursor-not-allowed transition';
    }
}

// Tutup modal dengan klik overlay
document.addEventListener('click', function(e) {
    if (e.target.id === 'termsModal') {
        closeModal('termsModal');
    }
    if (e.target.id === 'refundModal') {
        closeModal('refundModal');
    }
});

// Tutup modal dengan ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal('termsModal');
        closeModal('refundModal');
    }
});
</script>
@endpush
@endif