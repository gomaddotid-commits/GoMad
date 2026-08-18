@extends('layouts.admin')
@section('title', 'Personalisasi Agency')
@section('content')

<div>
    <h1 class="text-2xl font-bold text-[#111111] mb-2">⚙️ Personalisasi Agency</h1>
    <p class="text-sm text-gray-500 font-light mb-6">Atur kebijakan finansial & operasional per-agency (COD, OTS, komisi, settlement).</p>

    {{-- Search & Filter --}}
    <form class="flex gap-3 mb-6">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama agency..."
               class="flex-1 px-4 py-2.5 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-sm">
        <select name="has_policy" onchange="this.form.submit()" class="px-4 py-2.5 border border-[#E5E5E5] rounded-[12px] bg-white text-sm">
            <option value="">Semua Status</option>
            <option value="1" {{ request('has_policy') == '1' ? 'selected' : '' }}>Sudah Di-personalisasi</option>
            <option value="0" {{ request('has_policy') == '0' ? 'selected' : '' }}>Default Global</option>
        </select>
        <button type="submit" class="px-5 py-2.5 bg-[#C1121F] text-white rounded-[12px] text-sm font-semibold">Cari</button>
    </form>

    {{-- Table --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F5F5F5] border-b border-[#E5E5E5]">
                    <tr>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Agency</th>
                        <th class="px-4 py-3 text-center font-mono uppercase tracking-wider text-xs text-gray-500">COD Tanpa Deposit</th>
                        <th class="px-4 py-3 text-center font-mono uppercase tracking-wider text-xs text-gray-500">OTS</th>
                        <th class="px-4 py-3 text-center font-mono uppercase tracking-wider text-xs text-gray-500">Komisi</th>
                        <th class="px-4 py-3 text-center font-mono uppercase tracking-wider text-xs text-gray-500">Settlement</th>
                        <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-xs text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-xs text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agencies as $agency)
                    @php $p = $agency->policy; @endphp
                    <tr class="border-t border-[#E5E5E5] hover:bg-[#F5F5F5]">
                        <td class="px-4 py-3">
                            <p class="font-medium text-[#111111]">{{ $agency->agency_name }}</p>
                            <p class="text-xs text-gray-400 font-light">{{ $agency->bookings_count }} booking</p>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($p && $p->allow_cod_without_deposit)
                                <span class="text-green-600 font-semibold">✅ Boleh</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($p && $p->allow_ots)
                                <span class="text-green-600 font-semibold">✅</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center font-mono">
                            {{ $p && $p->commission_override ? $p->commission_override . '%' : '<span class="text-gray-400">Global</span>' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs">{{ $p ? ucfirst($p->settlement_schedule) : 'Mingguan' }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($p)
                                <span class="px-2 py-1 rounded-full text-[10px] font-mono uppercase bg-blue-50 text-blue-700 border border-blue-200">Kustom</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-[10px] font-mono uppercase bg-gray-50 text-gray-500 border border-gray-200">Global</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.agency-policies.edit', $agency) }}" class="text-[#C1121F] hover:underline text-sm font-medium">
                                {{ $p ? 'Edit' : 'Personalisasi' }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-gray-400">Tidak ada agency.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $agencies->links() }}</div>
</div>
@endsection
