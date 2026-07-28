@extends('layouts.driver')

@section('title', 'Detail Tugas Rental')
@section('content')

<div class="max-w-2xl mx-auto">
    <a href="{{ route('driver.rentals') }}" class="text-[#C1121F] text-sm mb-4 inline-block hover:underline">
        ← Kembali ke Daftar Rental
    </a>

    {{-- Status Banner --}}
    <div class="rounded-[12px] p-4 mb-6 text-center border
        @if($rental->status == 'active') bg-indigo-50 border-indigo-200
        @elseif($rental->status == 'paid') bg-blue-50 border-blue-200
        @elseif($rental->status == 'returned') bg-orange-50 border-orange-200
        @elseif($rental->status == 'completed') bg-green-50 border-green-200
        @else bg-yellow-50 border-yellow-200 @endif">
        <div class="text-3xl mb-2">
            @if($rental->status == 'active') 🏃
            @elseif($rental->status == 'paid') 🚗
            @elseif($rental->status == 'returned') ✅
            @elseif($rental->status == 'completed') 🎉
            @else ⏳
            @endif
        </div>
        <p class="font-bold text-lg">{{ $rental->status_label }}</p>
        <p class="text-sm mt-1 font-light text-gray-600">
            @if($rental->status == 'paid')
                Customer siap dijemput. Klik <strong>Verifikasi Pengambilan</strong> setelah menjemput.
            @elseif($rental->status == 'active')
                Dalam perjalanan. Klik <strong>Verifikasi Pengembalian</strong> setelah customer selesai.
            @elseif($rental->status == 'returned')
                Menunggu verifikasi agency.
            @elseif($rental->status == 'completed')
                Rental selesai.
            @endif
        </p>
    </div>

    {{-- Info Rental --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-5 mb-6 shadow-sm">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-xl font-bold font-mono text-[#111111]">{{ $rental->rental_code }}</h1>
                <p class="text-sm text-gray-500 font-light">👨‍✈️ Dengan Supir</p>
            </div>
            <p class="text-xl font-bold text-[#C1121F] font-mono">
                Rp {{ number_format($rental->total_price, 0, ',', '.') }}
            </p>
        </div>

        {{-- Info Kendaraan --}}
        <div class="flex items-center gap-4 mb-4 p-4 bg-[#F5F5F5] rounded-[12px] border border-[#E5E5E5]">
            <div class="w-20 h-16 bg-white rounded-[12px] overflow-hidden flex-shrink-0 border border-[#E5E5E5]">
                @if($rental->vehicle->vehicle_image)
                <img src="{{ $rental->vehicle->vehicle_image }}" class="w-full h-full object-cover">
                @else
                <div class="w-full h-full flex items-center justify-center text-2xl">🚗</div>
                @endif
            </div>
            <div>
                <p class="font-bold text-[#111111]">{{ $rental->vehicle->brand }} {{ $rental->vehicle->model }}</p>
                <p class="text-sm text-gray-500 font-mono">{{ $rental->vehicle->plate_number }}</p>
                <p class="text-xs text-gray-400 font-light">{{ $rental->vehicle->year }}</p>
            </div>
        </div>

        {{-- Detail Sewa --}}
        <div class="grid grid-cols-2 gap-3 text-sm mb-4">
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Mulai</span>
                <p class="font-semibold text-[#111111]">{{ $rental->start_datetime->format('d M Y H:i') }}</p>
            </div>
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Selesai</span>
                <p class="font-semibold text-[#111111]">{{ $rental->end_datetime->format('d M Y H:i') }}</p>
            </div>
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Durasi</span>
                <p class="font-semibold text-[#111111]">{{ $rental->duration }} {{ $rental->duration_unit == 'hour' ? 'Jam' : 'Hari' }}</p>
            </div>
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Agency</span>
                <p class="font-semibold text-[#111111]">{{ $rental->agency->agency_name ?? '-' }}</p>
            </div>
        </div>

        {{-- Status Timestamps --}}
        @if($rental->started_at)
        <div class="bg-green-50 border border-green-200 rounded-[12px] p-3 mb-3">
            <span class="text-[10px] font-mono uppercase tracking-wider text-green-700">✅ Diambil</span>
            <span class="text-sm text-green-700 ml-2 font-light">{{ $rental->started_at->format('d M Y H:i') }}</span>
        </div>
        @endif
        @if($rental->returned_at)
        <div class="bg-blue-50 border border-blue-200 rounded-[12px] p-3 mb-3">
            <span class="text-[10px] font-mono uppercase tracking-wider text-blue-700">🔄 Dikembalikan</span>
            <span class="text-sm text-blue-700 ml-2 font-light">{{ $rental->returned_at->format('d M Y H:i') }}</span>
        </div>
        @endif

        {{-- Info Customer --}}
        <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3 mb-4">
            <p class="text-sm font-medium text-[#111111]">👤 {{ $rental->customer->name ?? '-' }}</p>
            <p class="text-sm text-gray-600 font-light">📞 {{ $rental->customer->phone ?? '-' }}</p>
        </div>

        {{-- Alamat Penjemputan --}}
        <div class="bg-[#F5F5F5] rounded-[12px] p-3 mb-3 border border-[#E5E5E5]">
            <div class="flex justify-between items-start mb-1">
                <span class="text-[10px] font-mono uppercase tracking-wider text-blue-700">📍 ALAMAT PENJEMPUTAN</span>
            </div>
            <p class="text-sm font-medium text-[#111111]">{{ $pickupAddress }}</p>
            <div class="flex flex-wrap gap-2 mt-2">
                <a href="{{ $pickupMapsUrl }}" target="_blank" 
                   class="inline-flex items-center gap-1.5 text-xs bg-[#C1121F] text-white px-3 py-2 rounded-[12px] hover:bg-[#8A0F18] transition font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    BUKA MAPS
                </a>
                @if($rental->can_verify_pickup)
                <a href="{{ $pickupNavUrl }}" target="_blank" 
                   class="inline-flex items-center gap-1.5 text-xs bg-green-500 text-white px-3 py-2 rounded-[12px] hover:bg-green-600 transition font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    NAVIGASI
                </a>
                @endif
            </div>
        </div>

        {{-- Alamat Tujuan --}}
        @if($destinationAddress)
        <div class="bg-[#F5F5F5] rounded-[12px] p-3 mb-4 border border-[#E5E5E5]">
            <div class="flex justify-between items-start mb-1">
                <span class="text-[10px] font-mono uppercase tracking-wider text-red-700">🎯 ALAMAT TUJUAN</span>
            </div>
            <p class="text-sm font-medium text-[#111111]">{{ $destinationAddress }}</p>
            <div class="flex flex-wrap gap-2 mt-2">
                @if($destMapsUrl)
                <a href="{{ $destMapsUrl }}" target="_blank" 
                   class="inline-flex items-center gap-1.5 text-xs bg-[#C1121F] text-white px-3 py-2 rounded-[12px] hover:bg-[#8A0F18] transition font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    BUKA MAPS
                </a>
                @endif
                @if($rental->status == 'active' && $destNavUrl)
                <a href="{{ $destNavUrl }}" target="_blank" 
                   class="inline-flex items-center gap-1.5 text-xs bg-green-500 text-white px-3 py-2 rounded-[12px] hover:bg-green-600 transition font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    NAVIGASI
                </a>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- Tombol Aksi --}}
    @if($rental->status == 'paid' || $rental->status == 'active')
    <div class="space-y-3">
        @if($rental->status == 'paid')
        <form action="{{ route('driver.rental.verify-pickup', $rental) }}" method="POST">
            @csrf
            <button type="submit" 
                    class="w-full bg-[#C1121F] text-white py-4 rounded-[12px] font-bold text-lg hover:bg-[#8A0F18] transition shadow-sm"
                    onclick="return confirm('Verifikasi pengambilan mobil?\n\nPastikan customer sudah Anda jemput.')">
                ✅ VERIFIKASI PENGAMBILAN
            </button>
        </form>
        @endif

        @if($rental->status == 'active')
        <form action="{{ route('driver.rental.verify-return', $rental) }}" method="POST">
            @csrf
            <button type="submit" 
                    class="w-full bg-blue-600 text-white py-4 rounded-[12px] font-bold text-lg hover:bg-blue-700 transition shadow-sm"
                    onclick="return confirm('Verifikasi pengembalian mobil?\n\nPastikan customer sudah selesai menggunakan mobil.')">
                🔄 VERIFIKASI PENGEMBALIAN
            </button>
        </form>
        @endif
    </div>
    @endif

    {{-- Info jika sudah selesai --}}
    @if(in_array($rental->status, ['returned', 'completed']))
    <div class="bg-green-50 border border-green-200 rounded-[12px] p-4 text-center">
        <p class="text-green-800 font-bold text-lg">
            @if($rental->status == 'returned')
                ✅ Menunggu Verifikasi Agency
            @else
                ✅ Rental Selesai
            @endif
        </p>
        @if($rental->returned_at)
        <p class="text-sm text-green-600 mt-1 font-light">
            Dikembalikan: {{ $rental->returned_at->format('d M Y H:i') }}
        </p>
        @endif
    </div>
    @endif

    {{-- Catatan --}}
    @if($rental->notes)
    <div class="mt-4 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
        <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Catatan Customer</span>
        <p class="text-sm text-[#111111] mt-1 font-light">{{ $rental->notes }}</p>
    </div>
    @endif
</div>
@endsection