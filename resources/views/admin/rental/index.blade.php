@extends('layouts.admin')

@section('title', 'Daftar Rental')
@section('content')

@php
    $statusFilter = request('status');
    $agencyFilter = request('agency_id');
    
    $query = \App\Models\Rental::with(['vehicle', 'agency', 'customer', 'payment']);
    
    if ($statusFilter) {
        $query->where('status', $statusFilter);
    }
    
    if ($agencyFilter) {
        $query->where('agency_id', $agencyFilter);
    }
    
    $rentals = $query->orderBy('created_at', 'desc')->paginate(20);
    $agencies = \App\Models\Agency::where('is_verified', true)->orderBy('agency_name')->get();
@endphp

<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 border-b border-[#E5E5E5] pb-3">
        <h1 class="text-2xl font-bold text-[#111111]">Daftar Rental</h1>
        <a href="{{ route('admin.rental.dashboard') }}" class="text-[#C1121F] text-sm hover:underline font-medium">
            ← Dashboard Rental
        </a>
    </div>

    {{-- Filter --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-4 mb-6 shadow-sm">
        <form action="{{ route('admin.rental.index') }}" method="GET" class="flex flex-wrap gap-3 items-end">
            {{-- Status Filter --}}
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Status</label>
                <select name="status" class="px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] text-sm">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ $statusFilter == 'pending' ? 'selected' : '' }}>⏳ Pending</option>
                    <option value="paid" {{ $statusFilter == 'paid' ? 'selected' : '' }}>🚗 Siap Diambil</option>
                    <option value="active" {{ $statusFilter == 'active' ? 'selected' : '' }}>🏃 Sedang Disewa</option>
                    <option value="returned" {{ $statusFilter == 'returned' ? 'selected' : '' }}>🔄 Dikembalikan</option>
                    <option value="completed" {{ $statusFilter == 'completed' ? 'selected' : '' }}>✅ Selesai</option>
                    <option value="cancelled" {{ $statusFilter == 'cancelled' ? 'selected' : '' }}>❌ Dibatalkan</option>
                </select>
            </div>
            
            {{-- Agency Filter --}}
            <div>
                <label class="block text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-1">Agency</label>
                <select name="agency_id" class="px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] text-sm">
                    <option value="">Semua Agency</option>
                    @foreach($agencies as $agency)
                    <option value="{{ $agency->id }}" {{ $agencyFilter == $agency->id ? 'selected' : '' }}>{{ $agency->agency_name }}</option>
                    @endforeach
                </select>
            </div>
            
            <button type="submit" class="bg-[#C1121F] text-white px-4 py-2 rounded-[12px] text-sm font-medium hover:bg-[#8A0F18] transition">
                Filter
            </button>
            
            @if($statusFilter || $agencyFilter)
            <a href="{{ route('admin.rental.index') }}" class="border border-[#E5E5E5] text-gray-600 px-4 py-2 rounded-[12px] text-sm hover:bg-[#F5F5F5] transition">
                Reset
            </a>
            @endif
        </form>
    </div>

    {{-- Tabel --}}
    @if($rentals->isEmpty())
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-12 text-center shadow-sm">
        <div class="w-16 h-16 bg-[#C1121F]/5 rounded-[12px] flex items-center justify-center mx-auto mb-4 border border-[#E5E5E5]">
            <span class="text-2xl">🚗</span>
        </div>
        <p class="text-gray-500 text-lg font-light">Tidak ada data rental.</p>
    </div>
    @else
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F5F5F5] border-b border-[#E5E5E5]">
                    <tr>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Kode</th>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Customer</th>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Agency</th>
                        <th class="px-4 py-3 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Mobil</th>
                        <th class="px-4 py-3 text-center font-mono uppercase tracking-wider text-xs text-gray-500">Tipe</th>
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
                        <td class="px-4 py-3 text-sm text-gray-500 font-light">{{ $rental->agency->agency_name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs text-[#111111]">{{ $rental->vehicle->plate_number ?? '-' }}</span>
                            <br><span class="text-[10px] text-gray-500 font-light">{{ $rental->vehicle->brand ?? '' }} {{ $rental->vehicle->model ?? '' }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                                @if($rental->type == 'self_drive') bg-blue-50 text-blue-700 border-blue-200
                                @else bg-green-50 text-green-700 border-green-200 @endif">
                                {{ $rental->type == 'self_drive' ? 'Lepas Kunci' : '+Supir' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 font-light">
                            {{ $rental->duration }} {{ $rental->duration_unit == 'hour' ? 'Jam' : 'Hari' }}
                            <br><span class="text-[10px]">{{ $rental->start_datetime->format('d/m/Y H:i') }}</span>
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
                            <a href="{{ route('admin.rental.show', $rental) }}" class="text-[#C1121F] hover:underline text-xs font-medium">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="mt-4">
        {{ $rentals->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection