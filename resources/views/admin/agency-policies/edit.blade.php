@extends('layouts.admin')
@section('title', 'Personalisasi ' . $agency->agency_name)
@section('content')

<div class="max-w-3xl mx-auto">
    <a href="{{ route('admin.agency-policies.index') }}" class="text-sm text-gray-500 hover:text-[#C1121F] mb-4 inline-block">← Kembali</a>

    <h1 class="text-2xl font-bold text-[#111111] mb-1">⚙️ Personalisasi {{ $agency->agency_name }}</h1>
    <p class="text-sm text-gray-500 font-light mb-6">Atur kebijakan khusus untuk agency ini. Kosongkan komisi untuk memakai global.</p>

    {{-- Info Saldo --}}
    @if($wallet)
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mb-1">Saldo Tersedia</p>
            <p class="text-lg font-bold text-[#111111]">Rp {{ number_format($wallet->available_balance ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mb-1">Deposit</p>
            <p class="text-lg font-bold text-[#111111]">Rp {{ number_format($wallet->deposit_balance ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400 mb-1">COD Hold</p>
            <p class="text-lg font-bold text-[#111111]">Rp {{ number_format($wallet->cod_hold_balance ?? 0, 0, ',', '.') }}</p>
        </div>
    </div>
    @endif

    <form action="{{ route('admin.agency-policies.update', $agency) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- COD --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">🚗 Kebijakan COD (Travel)</h3>
            <div class="space-y-4">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="allow_cod_without_deposit" value="1" class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F]"
                        {{ ($policy->allow_cod_without_deposit ?? false) ? 'checked' : '' }}>
                    <div>
                        <span class="font-semibold text-[#111111]">Boleh COD tanpa deposit</span>
                        <p class="text-xs text-gray-500 font-light">Agency tetap boleh terima COD meski saldo deposit 0.</p>
                    </div>
                </label>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Min Saldo Deposit (Rp)</label>
                        <input type="number" name="cod_min_balance" value="{{ old('cod_min_balance', $policy->cod_min_balance ?? 0) }}" min="0"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111]">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Limit COD/Hari (Rp)</label>
                        <input type="number" name="cod_daily_limit" value="{{ old('cod_daily_limit', $policy->cod_daily_limit ?? 0) }}" min="0"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111]">
                    </div>
                    <div>
                        <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Max/Booking (Rp)</label>
                        <input type="number" name="cod_max_per_booking" value="{{ old('cod_max_per_booking', $policy->cod_max_per_booking ?? 0) }}" min="0"
                               class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111]">
                    </div>
                </div>
            </div>
        </div>

        {{-- OTS --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">💵 Kebijakan OTS (Rental)</h3>
            <div class="space-y-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="allow_ots" value="1" class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F]"
                        {{ ($policy->allow_ots ?? false) ? 'checked' : '' }}>
                    <div>
                        <span class="font-semibold text-[#111111]">Aktifkan OTS</span>
                        <p class="text-xs text-gray-500 font-light">Customer boleh bayar di tempat untuk rental.</p>
                    </div>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="ots_deposit_required" value="1" class="w-5 h-5 rounded border-[#E5E5E5] text-[#C1121F]"
                        {{ ($policy->ots_deposit_required ?? true) ? 'checked' : '' }}>
                    <div>
                        <span class="font-semibold text-[#111111]">Wajib deposit untuk OTS</span>
                        <p class="text-xs text-gray-500 font-light">Deposit harus dibayar sebelum pengambilan mobil.</p>
                    </div>
                </label>
            </div>
        </div>

        {{-- Komisi & Settlement --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">💰 Komisi & Settlement</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Komisi Khusus (%)</label>
                    <input type="number" name="commission_override" value="{{ old('commission_override', $policy->commission_override) }}"
                           min="0" max="100" step="0.5" placeholder="Kosongkan = pakai global"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111]">
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Jadwal Settlement</label>
                    <select name="settlement_schedule" class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111]">
                        <option value="daily" {{ ($policy->settlement_schedule ?? 'weekly') == 'daily' ? 'selected' : '' }}>Harian</option>
                        <option value="weekly" {{ ($policy->settlement_schedule ?? 'weekly') == 'weekly' ? 'selected' : '' }}>Mingguan</option>
                        <option value="monthly" {{ ($policy->settlement_schedule ?? 'weekly') == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Kredit & Catatan --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6">
            <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">🏦 Kredit & Catatan</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Limit Kredit / Saldo Minus (Rp)</label>
                    <input type="number" name="credit_limit" value="{{ old('credit_limit', $policy->credit_limit ?? 0) }}" min="0"
                           class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111]"
                           placeholder="0 = tidak boleh minus">
                </div>
                <div>
                    <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Catatan Admin</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] text-sm"
                              placeholder="Alasan personalisasi / kesepakatan khusus...">{{ old('notes', $policy->notes) }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="flex-1 btn-gomad-primary py-3 rounded-[12px] font-semibold">💾 Simpan Kebijakan</button>
            @if($agency->policy)
            <button type="button" onclick="confirmReset()" class="px-6 py-3 border border-red-200 text-red-600 rounded-[12px] font-semibold hover:bg-red-50 transition">Reset ke Global</button>
            @endif
        </div>
    </form>

    {{-- Form reset dipisah di luar form utama (HTML tidak mengizinkan form bersarang) --}}
    @if($agency->policy)
    <form id="resetPolicyForm" action="{{ route('admin.agency-policies.destroy', $agency) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
    @endif
</div>

@endsection
@push('scripts')
<script>
function confirmReset() {
    if (confirm('Hapus personalisasi? Agency kembali ke kebijakan global.')) {
        document.getElementById('resetPolicyForm').submit();
    }
}
</script>
@endpush
