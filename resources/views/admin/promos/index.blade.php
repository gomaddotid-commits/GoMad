@extends('layouts.admin')

@section('title', 'Promo')
@section('content')
<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <h1 class="text-2xl font-bold text-secondary">Kelola Promo</h1>
        <a href="{{ route('admin.promos.create') }}" class="btn-primary text-sm inline-flex items-center gap-2 self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Promo
        </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        @php
            $activePromos = \App\Models\Promo::active()->count();
            $generalPromos = \App\Models\Promo::general()->count();
            $selectivePromos = \App\Models\Promo::selective()->count();
        @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
            <p class="text-sm text-gray-500">Promo Aktif</p>
            <p class="text-2xl font-bold text-green-600">{{ $activePromos }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
            <p class="text-sm text-gray-500">General</p>
            <p class="text-2xl font-bold text-blue-600">{{ $generalPromos }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 text-center">
            <p class="text-sm text-gray-500">Selektif</p>
            <p class="text-2xl font-bold text-purple-600">{{ $selectivePromos }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Nama</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Jenis</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Diskon</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Periode</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($promos as $promo)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $promo->name }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                @if($promo->type == 'general') bg-blue-100 text-blue-700
                                @elseif($promo->type == 'selective') bg-purple-100 text-purple-700
                                @else bg-green-100 text-green-700 @endif">
                                {{ $promo->type_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center font-bold">{{ $promo->discount_percent }}%</td>
                        <td class="px-4 py-3 text-xs">{{ $promo->start_date->format('d M') }} - {{ $promo->end_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $promo->isActiveNow() ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $promo->isActiveNow() ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.promos.edit', $promo) }}" class="text-blue-600 hover:underline text-xs mr-2">Edit</a>
                            <form action="{{ route('admin.promos.destroy', $promo) }}" method="POST" class="inline" onsubmit="return confirm('Hapus promo?')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:underline text-xs">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada promo.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $promos->links() }}</div>
</div>
@endsection