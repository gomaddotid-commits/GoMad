@extends('layouts.agency')

@section('title', 'Booking Rental')
@section('content')

@php
    $statusFilter = request('status');
@endphp

<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 border-b border-[#E5E5E5] pb-3">
        <h1 class="text-2xl font-bold text-[#111111]">Booking Rental</h1>
        <a href="{{ route('agency.rental.dashboard') }}" class="text-[#C1121F] text-sm hover:underline font-medium">
            ← Kembali ke Dashboard Rental
        </a>
    </div>

    {{-- Filter Status --}}
    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        <a href="{{ route('agency.rental.index') }}" 
           class="px-4 py-2 rounded-full text-sm font-medium border whitespace-nowrap transition {{ !$statusFilter ? 'bg-[#C1121F] text-white border-[#C1121F]' : 'bg-white text-gray-600 border-[#E5E5E5] hover:border-[#C1121F]' }}">
            Semua
        </a>
        <a href="{{ route('agency.rental.index', ['status' => 'pending']) }}" 
           class="px-4 py-2 rounded-full text-sm font-medium border whitespace-nowrap transition {{ $statusFilter == 'pending' ? 'bg-yellow-500 text-white border-yellow-500' : 'bg-white text-gray-600 border-[#E5E5E5] hover:border-yellow-500' }}">
            ⏳ Pending
        </a>
        <a href="{{ route('agency.rental.index', ['status' => 'paid']) }}" 
           class="px-4 py-2 rounded-full text-sm font-medium border whitespace-nowrap transition {{ $statusFilter == 'paid' ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-600 border-[#E5E5E5] hover:border-blue-500' }}">
            🚗 Siap Diambil
        </a>
        <a href="{{ route('agency.rental.index', ['status' => 'active']) }}" 
           class="px-4 py-2 rounded-full text-sm font-medium border whitespace-nowrap transition {{ $statusFilter == 'active' ? 'bg-indigo-500 text-white border-indigo-500' : 'bg-white text-gray-600 border-[#E5E5E5] hover:border-indigo-500' }}">
            🏃 Sedang Disewa
        </a>
        <a href="{{ route('agency.rental.index', ['status' => 'returned']) }}" 
           class="px-4 py-2 rounded-full text-sm font-medium border whitespace-nowrap transition {{ $statusFilter == 'returned' ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-gray-600 border-[#E5E5E5] hover:border-orange-500' }}">
            🔄 Dikembalikan
        </a>
        <a href="{{ route('agency.rental.index', ['status' => 'completed']) }}" 
           class="px-4 py-2 rounded-full text-sm font-medium border whitespace-nowrap transition {{ $statusFilter == 'completed' ? 'bg-green-500 text-white border-green-500' : 'bg-white text-gray-600 border-[#E5E5E5] hover:border-green-500' }}">
            ✅ Selesai
        </a>
    </div>

    @if($rentals->isEmpty())
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-12 text-center shadow-sm">
        <div class="w-16 h-16 bg-[#C1121F]/5 rounded-[12px] flex items-center justify-center mx-auto mb-4 border border-[#E5E5E5]">
            <span class="text-2xl">🚗</span>
        </div>
        <p class="text-gray-500 text-lg font-light">Belum ada booking rental.</p>
        @if($statusFilter)
        <a href="{{ route('agency.rental.index') }}" class="text-[#C1121F] hover:underline mt-2 inline-block font-medium">Tampilkan semua</a>
        @endif
    </div>
    @else
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F5F5F5] border-b border-[#E5E5E5]">
                    <tr>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Kode</th>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Customer</th>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Mobil</th>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Tipe</th>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Durasi</th>
                        <th class="px-4 py-3 text-center font-mono uppercase tracking-wider text-xs text-gray-500">Status</th>
                        <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-xs text-gray-500">Total</th>
                        <th class="px-4 py-3 text-right font-mono uppercase tracking-wider text-xs text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5E5E5]">
                    @foreach($rentals as $rental)
                    <tr class="hover:bg-[#F5F5F5]">
                        <td class="px-4 py-3 font-mono text-xs text-[#111111]">{{ $rental->rental_code }}</td>
                        <td class="px-4 py-3">
                            <span class="font-medium text-[#111111] text-sm">{{ $rental->customer->name ?? '-' }}</span>
                            <br><span class="text-[10px] text-gray-500 font-light">{{ $rental->customer->phone ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs text-[#111111]">{{ $rental->vehicle->plate_number ?? '-' }}</span>
                            <br><span class="text-[10px] text-gray-500 font-light">{{ $rental->vehicle->brand ?? '' }} {{ $rental->vehicle->model ?? '' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                @if($rental->type == 'self_drive') bg-blue-50 text-blue-700 border-blue-200
                                @else bg-green-50 text-green-700 border-green-200 @endif">
                                {{ $rental->type == 'self_drive' ? 'Lepas Kunci' : '+Supir' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 font-light">
                            {{ $rental->duration }} {{ $rental->duration_unit == 'hour' ? 'Jam' : 'Hari' }}
                            <br>
                            <span class="text-[10px]">{{ $rental->start_datetime->format('d/m/Y H:i') }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                @if($rental->status == 'active') bg-indigo-50 text-indigo-700 border-indigo-200
                                @elseif($rental->status == 'paid') bg-blue-50 text-blue-700 border-blue-200
                                @elseif($rental->status == 'returned') bg-orange-50 text-orange-700 border-orange-200
                                @elseif($rental->status == 'completed') bg-green-50 text-green-700 border-green-200
                                @elseif($rental->status == 'cancelled') bg-red-50 text-red-700 border-red-200
                                @else bg-yellow-50 text-yellow-700 border-yellow-200 @endif">
                                {{ $rental->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-[#C1121F] font-medium">
                            Rp {{ number_format($rental->total_price, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('agency.rental.show', $rental) }}" class="text-[#C1121F] hover:underline text-xs font-medium">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection