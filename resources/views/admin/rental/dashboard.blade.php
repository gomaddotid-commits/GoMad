@extends('layouts.admin')

@section('title', 'Dashboard Rental')
@section('content')

@php
    $totalRentals = \App\Models\Rental::count();
    $pendingRentals = \App\Models\Rental::where('status', 'pending')->count();
    $activeRentals = \App\Models\Rental::where('status', 'active')->count();
    $completedRentals = \App\Models\Rental::where('status', 'completed')->count();
    $totalRevenue = \App\Models\Rental::where('status', 'completed')->sum('total_price');
    $pendingDocuments = \App\Models\CustomerDocument::where('verification_status', 'pending')->count();
    
    $recentRentals = \App\Models\Rental::with(['vehicle', 'agency', 'customer'])
        ->latest()
        ->limit(10)
        ->get();
@endphp

<div>
    {{-- Welcome Banner --}}
    <div class="bg-gradient-to-r from-[#C1121F] to-[#8A0F18] rounded-[12px] p-6 mb-8 text-white shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Dashboard Rental</h1>
                <p class="text-white/80 text-sm mt-1 font-light">Pantau semua aktivitas rental kendaraan</p>
            </div>
            <div class="text-right">
                <p class="text-4xl font-bold">{{ $totalRentals }}</p>
                <p class="text-white/80 text-sm font-light">Total Rental</p>
            </div>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-5 shadow-sm text-center">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Total</p>
            <p class="text-2xl font-bold text-[#111111] mt-1">{{ $totalRentals }}</p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-5 shadow-sm text-center">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Pending</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $pendingRentals }}</p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-5 shadow-sm text-center">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Aktif</p>
            <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $activeRentals }}</p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-5 shadow-sm text-center">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Selesai</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $completedRentals }}</p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-5 shadow-sm text-center">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Revenue</p>
            <p class="text-lg font-bold text-[#C1121F] mt-1">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-5 shadow-sm text-center">
            <p class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Dokumen</p>
            <p class="text-2xl font-bold text-orange-600 mt-1">{{ $pendingDocuments }}</p>
            <p class="text-[10px] text-gray-400 font-light">pending</p>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <a href="{{ route('admin.rental.index') }}" class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 text-center shadow-sm hover:border-[#C1121F] transition-colors group">
            <div class="w-12 h-12 bg-[#C1121F]/5 rounded-[12px] flex items-center justify-center text-xl mx-auto mb-3 group-hover:scale-110 transition-transform border border-[#E5E5E5]">🎫</div>
            <p class="font-semibold text-[#111111] text-sm">Semua Rental</p>
        </a>
        <a href="{{ route('admin.rental.documents') }}" class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 text-center shadow-sm hover:border-[#C1121F] transition-colors group">
            <div class="w-12 h-12 bg-orange-50 rounded-[12px] flex items-center justify-center text-xl mx-auto mb-3 group-hover:scale-110 transition-transform border border-orange-200">📄</div>
            <p class="font-semibold text-[#111111] text-sm">Verifikasi Dokumen</p>
            @if($pendingDocuments > 0)
            <span class="text-[10px] bg-red-500 text-white px-2 py-0.5 rounded-full font-mono">{{ $pendingDocuments }} pending</span>
            @endif
        </a>
        <a href="{{ route('admin.promos.index') }}" class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 text-center shadow-sm hover:border-[#C1121F] transition-colors group">
            <div class="w-12 h-12 bg-purple-50 rounded-[12px] flex items-center justify-center text-xl mx-auto mb-3 group-hover:scale-110 transition-transform border border-purple-200">🎫</div>
            <p class="font-semibold text-[#111111] text-sm">Promo</p>
        </a>
        <a href="{{ route('admin.reports') }}" class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 text-center shadow-sm hover:border-[#C1121F] transition-colors group">
            <div class="w-12 h-12 bg-blue-50 rounded-[12px] flex items-center justify-center text-xl mx-auto mb-3 group-hover:scale-110 transition-transform border border-blue-200">📈</div>
            <p class="font-semibold text-[#111111] text-sm">Laporan</p>
        </a>
    </div>

    {{-- Rental Terbaru --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
        <div class="flex justify-between items-center mb-4 border-b border-[#E5E5E5] pb-3">
            <h2 class="font-bold text-lg text-[#111111]">Rental Terbaru</h2>
            <a href="{{ route('admin.rental.index') }}" class="text-[#C1121F] text-sm hover:underline font-medium">Lihat Semua →</a>
        </div>

        @if($recentRentals->isEmpty())
        <p class="text-gray-500 text-center py-4 font-light">Belum ada data rental.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F5F5F5] border-b border-[#E5E5E5]">
                    <tr>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Kode</th>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Customer</th>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Agency</th>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Mobil</th>
                        <th class="px-4 py-3 text-center font-mono uppercase tracking-wider text-xs text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-xs text-gray-500">Total</th>
                        <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-xs text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5E5E5]">
                    @foreach($recentRentals as $rental)
                    <tr class="hover:bg-[#F5F5F5]">
                        <td class="px-4 py-3 font-mono text-xs text-[#111111]">{{ $rental->rental_code }}</td>
                        <td class="px-4 py-3 text-sm text-[#111111]">{{ $rental->customer->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 font-light">{{ $rental->agency->agency_name ?? '-' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $rental->vehicle->plate_number ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                @if($rental->status == 'active') bg-indigo-50 text-indigo-700 border-indigo-200
                                @elseif($rental->status == 'paid') bg-blue-50 text-blue-700 border-blue-200
                                @elseif($rental->status == 'completed') bg-green-50 text-green-700 border-green-200
                                @elseif($rental->status == 'cancelled') bg-red-50 text-red-700 border-red-200
                                @else bg-yellow-50 text-yellow-700 border-yellow-200 @endif">
                                {{ $rental->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-[#C1121F]">Rp {{ number_format($rental->total_price, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.rental.show', $rental) }}" class="text-[#C1121F] hover:underline text-xs font-medium">Detail</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection