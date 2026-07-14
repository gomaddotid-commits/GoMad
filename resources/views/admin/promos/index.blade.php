@extends('layouts.admin')

@section('title', 'Promo')
@section('content')
<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 border-b border-[#E5E5E5] pb-3">
        <h1 class="text-2xl font-bold text-[#111111]">Kelola Promo</h1>
        <a href="{{ route('admin.promos.create') }}" class="btn-gomad-primary text-sm inline-flex items-center gap-2 self-start rounded-[12px]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Promo
        </a>
    </div>

    {{-- Statistik --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @php
            $activePromos = \App\Models\Promo::active()->count();
            $generalPromos = \App\Models\Promo::general()->count();
            $selectivePromos = \App\Models\Promo::selective()->count();
            $rentalPromos = \App\Models\Promo::where('module', 'rental')->count();
            $travelPromos = \App\Models\Promo::where('module', 'travel')->count();
        @endphp
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 text-center shadow-sm">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Promo Aktif</p>
            <p class="text-2xl font-bold text-green-600">{{ $activePromos }}</p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 text-center shadow-sm">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Travel</p>
            <p class="text-2xl font-bold text-blue-600">{{ $travelPromos }}</p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 text-center shadow-sm">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Rental</p>
            <p class="text-2xl font-bold text-orange-600">{{ $rentalPromos }}</p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 text-center shadow-sm">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Selektif</p>
            <p class="text-2xl font-bold text-purple-600">{{ $selectivePromos }}</p>
        </div>
    </div>

    {{-- Tabel Promo --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F5F5F5] border-b border-[#E5E5E5]">
                    <tr>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Nama</th>
                        <th class="px-4 py-3 text-center font-mono uppercase tracking-wider text-xs text-gray-500">Jenis</th>
                        <th class="px-4 py-3 text-center font-mono uppercase tracking-wider text-xs text-gray-500">Modul</th>
                        <th class="px-4 py-3 text-center font-mono uppercase tracking-wider text-xs text-gray-500">Diskon</th>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Periode</th>
                        <th class="px-4 py-3 text-center font-mono uppercase tracking-wider text-xs text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-xs text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5E5E5]">
                    @forelse($promos as $promo)
                    <tr class="hover:bg-[#F5F5F5]">
                        {{-- Nama --}}
                        <td class="px-4 py-3 font-medium text-[#111111]">{{ $promo->name }}</td>
                        
                        {{-- Jenis --}}
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                @if($promo->type == 'general') bg-blue-50 text-blue-700 border-blue-200
                                @elseif($promo->type == 'selective') bg-purple-50 text-purple-700 border-purple-200
                                @else bg-green-50 text-green-700 border-green-200 @endif">
                                {{ $promo->type_label }}
                            </span>
                        </td>
                        
                        {{-- Modul --}}
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                @if($promo->module == 'travel') bg-blue-50 text-blue-700 border-blue-200
                                @elseif($promo->module == 'rental') bg-orange-50 text-orange-700 border-orange-200
                                @else bg-purple-50 text-purple-700 border-purple-200 @endif">
                                {{ $promo->module_label }}
                            </span>
                        </td>
                        
                        {{-- Diskon --}}
                        <td class="px-4 py-3 text-center font-bold text-[#C1121F]">
                            @if($promo->module == 'rental')
                                @if(($promo->rental_discount_type ?? 'percent') == 'fixed')
                                    Rp {{ number_format($promo->rental_discount_amount, 0, ',', '.') }}
                                @elseif($promo->rental_discount_amount > 0)
                                    {{ $promo->rental_discount_amount }}%
                                @else
                                    {{ $promo->discount_percent }}%
                                @endif
                            @else
                                {{ $promo->discount_percent }}%
                            @endif
                        </td>
                        
                        {{-- Periode --}}
                        <td class="px-4 py-3 text-[10px] font-mono text-gray-500">
                            {{ $promo->start_date->format('d M') }} - {{ $promo->end_date->format('d M Y') }}
                        </td>
                        
                        {{-- Status --}}
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border {{ $promo->isActiveNow() ? 'bg-green-50 text-green-700 border-green-200' : 'bg-[#F5F5F5] text-gray-600 border-[#E5E5E5]' }}">
                                {{ $promo->isActiveNow() ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        
                        {{-- Aksi --}}
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.promos.edit', $promo) }}" class="text-[#C1121F] hover:underline text-xs font-medium mr-2">Edit</a>
                            <form action="{{ route('admin.promos.destroy', $promo) }}" method="POST" class="inline" onsubmit="return confirm('Hapus promo?')">
                                @csrf @method('DELETE')
                                <button class="text-[#C1121F] hover:underline text-xs font-medium">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500 font-light">
                            Belum ada promo. 
                            <a href="{{ route('admin.promos.create') }}" class="text-[#C1121F] hover:underline font-medium">Buat promo pertama</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-4">
        {{ $promos->links() }}
    </div>
</div>
@endsection