@extends('layouts.agency')

@section('title', 'Detail Rental')
@section('content')

@php
    $vehicle = $rental->vehicle;
    $setting = $vehicle->rentalSetting ?? null;
    $customer = $rental->customer;
    
    // Alamat pengambilan
    $pickupAddr = $setting?->pickup_address ?? $rental->agency->address;
    $pickupMaps = $setting?->pickup_maps_url ?? 'https://www.google.com/maps/search/?api=1&query=' . urlencode($pickupAddr);
@endphp

<div class="max-w-4xl mx-auto">
    <a href="{{ route('agency.rental.index') }}" class="text-[#C1121F] text-sm mb-4 inline-block hover:underline">
        ← Kembali ke Daftar Rental
    </a>

    {{-- Status Header --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
            <div>
                <h1 class="text-2xl font-bold font-mono text-[#111111]">{{ $rental->rental_code }}</h1>
                <div class="flex items-center gap-2 mt-2">
                    <span class="px-2 py-1 rounded-full text-[10px] font-mono uppercase tracking-wider border
                        @if($rental->status == 'active') bg-indigo-50 text-indigo-700 border-indigo-200
                        @elseif($rental->status == 'paid') bg-blue-50 text-blue-700 border-blue-200
                        @elseif($rental->status == 'returned') bg-orange-50 text-orange-700 border-orange-200
                        @elseif($rental->status == 'completed') bg-green-50 text-green-700 border-green-200
                        @elseif($rental->status == 'cancelled') bg-red-50 text-red-700 border-red-200
                        @else bg-yellow-50 text-yellow-700 border-yellow-200 @endif">
                        {{ $rental->status_label }}
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                        @if($rental->type == 'self_drive') bg-blue-50 text-blue-700 border-blue-200
                        @else bg-green-50 text-green-700 border-green-200 @endif">
                        {{ $rental->type == 'self_drive' ? '🚗 Lepas Kunci' : '👨‍✈️ Dengan Supir' }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-2 font-light">Dibuat: {{ $rental->created_at->format('d M Y H:i') }}</p>
            </div>
            <p class="text-2xl font-bold text-[#C1121F] font-mono">Rp {{ number_format($rental->total_price, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        {{-- Info Customer --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <h2 class="font-bold text-lg text-[#111111] mb-4">Informasi Customer</h2>
            <div class="space-y-3 text-sm">
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Nama</span>
                    <p class="font-semibold text-[#111111]">{{ $customer->name ?? '-' }}</p>
                </div>
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Email</span>
                    <p class="font-semibold text-[#111111]">{{ $customer->email ?? '-' }}</p>
                </div>
                <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Telepon</span>
                    <p class="font-semibold text-[#111111]">{{ $customer->phone ?? '-' }}</p>
                </div>
                @if($rental->type == 'self_drive' && $customer->customerDocuments)
                <div class="bg-blue-50 border border-blue-200 rounded-[12px] p-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Dokumen</span>
                    <p class="text-xs text-blue-700 font-light">KTP: {{ $customer->customerDocuments->ktp_number ?? '-' }} @if($customer->customerDocuments->ktp_verified)✅@else❌@endif</p>
                    <p class="text-xs text-blue-700 font-light">SIM: {{ $customer->customerDocuments->sim_number ?? '-' }} @if($customer->customerDocuments->sim_verified)✅@else❌@endif</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Info Kendaraan & Agency --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <h2 class="font-bold text-lg text-[#111111] mb-4">Informasi Kendaraan</h2>
            <div class="flex items-center gap-4 mb-4">
                <div class="w-24 h-20 bg-[#F5F5F5] rounded-[12px] overflow-hidden flex-shrink-0 border border-[#E5E5E5]">
                    @if($vehicle->vehicle_image)<img src="{{ $vehicle->vehicle_image }}" class="w-full h-full object-cover">@else<div class="w-full h-full flex items-center justify-center text-2xl">🚗</div>@endif
                </div>
                <div>
                    <p class="font-bold text-[#111111]">{{ $vehicle->brand }} {{ $vehicle->model }}</p>
                    <p class="text-sm text-gray-500 font-mono">{{ $vehicle->plate_number }}</p>
                    <p class="text-xs text-gray-400 font-light">{{ $vehicle->year }} • {{ $vehicle->capacity }} seat</p>
                </div>
            </div>
            
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Agency</span>
                <p class="font-semibold text-[#111111]">{{ $rental->agency->agency_name ?? '-' }}</p>
            </div>
        </div>

        {{-- Detail Sewa --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <h2 class="font-bold text-lg text-[#111111] mb-4">Detail Sewa</h2>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-gray-500 font-light">Durasi</span>
                    <span class="font-semibold text-[#111111]">{{ $rental->duration }} {{ $rental->duration_unit == 'hour' ? 'Jam' : 'Hari' }}</span>
                </div>
                <div class="flex justify-between bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-gray-500 font-light">Mulai</span>
                    <span class="font-semibold text-[#111111]">{{ $rental->start_datetime->format('d M Y H:i') }}</span>
                </div>
                <div class="flex justify-between bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                    <span class="text-gray-500 font-light">Selesai</span>
                    <span class="font-semibold text-[#111111]">{{ $rental->end_datetime->format('d M Y H:i') }}</span>
                </div>
                @if($rental->started_at)
                <div class="flex justify-between bg-green-50 border border-green-200 rounded-[12px] p-3"><span class="text-gray-500 font-light">Diambil</span><span class="font-semibold text-green-700">{{ $rental->started_at->format('d M Y H:i') }}</span></div>
                @endif
                @if($rental->returned_at)
                <div class="flex justify-between bg-blue-50 border border-blue-200 rounded-[12px] p-3"><span class="text-gray-500 font-light">Dikembalikan</span><span class="font-semibold text-blue-700">{{ $rental->returned_at->format('d M Y H:i') }}</span></div>
                @endif
                @if($rental->cancelled_at)
                <div class="flex justify-between bg-red-50 border border-red-200 rounded-[12px] p-3"><span class="text-gray-500 font-light">Dibatalkan</span><span class="font-semibold text-red-700">{{ $rental->cancelled_at->format('d M Y H:i') }}</span></div>
                @endif
            </div>
        </div>

        {{-- Rincian Harga --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <h2 class="font-bold text-lg text-[#111111] mb-4">Rincian Pembayaran</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500 font-light">Harga sewa ({{ $rental->duration }} {{ $rental->duration_unit }})</span><span class="text-[#111111]">Rp {{ number_format($rental->price_per_unit * $rental->duration, 0, ',', '.') }}</span></div>
                @if($rental->driver_fee_per_unit > 0)
                <div class="flex justify-between"><span class="text-gray-500 font-light">Biaya Supir</span><span class="text-[#111111]">Rp {{ number_format($rental->driver_fee_per_unit * $rental->duration, 0, ',', '.') }}</span></div>
                @endif
                <div class="flex justify-between"><span class="text-gray-500 font-light">Subtotal</span><span class="font-semibold text-[#111111]">Rp {{ number_format($rental->subtotal, 0, ',', '.') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-500 font-light">Biaya Platform</span><span class="text-[#111111]">Rp {{ number_format($rental->platform_fee, 0, ',', '.') }}</span></div>
                @if($rental->discount_amount > 0)
                <div class="flex justify-between text-[#C1121F] font-medium"><span>🎫 Diskon Promo</span><span>-Rp {{ number_format($rental->discount_amount, 0, ',', '.') }}</span></div>
                @endif
                <hr class="border-[#E5E5E5]">
                <div class="flex justify-between text-base font-bold"><span>Total</span><span class="text-[#C1121F] font-mono">Rp {{ number_format($rental->total_price, 0, ',', '.') }}</span></div>
            </div>
            @if($rental->payment)
            <div class="mt-4 p-3 rounded-[12px] text-sm border @if($rental->payment->status == 'paid') bg-green-50 border-green-200 text-green-700 @elseif($rental->payment->status == 'pending') bg-yellow-50 border-yellow-200 text-yellow-700 @else bg-[#F5F5F5] border-[#E5E5E5] text-gray-600 @endif">
                <span class="font-medium">Status:</span> {{ $rental->payment->status_label ?? $rental->payment->status }}
            </div>
            @endif
        </div>
    </div>

    {{-- Alamat Penjemputan (with_driver) --}}
    @if($rental->type == 'with_driver' && $rental->pickup_address)
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mt-6 shadow-sm">
        <h2 class="font-bold text-lg text-[#111111] mb-3">📍 Alamat Penjemputan Customer</h2>
        <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
            <p class="font-medium text-[#111111]">{{ $rental->pickup_address }}</p>
            @if($rental->pickup_maps_link)<a href="{{ $rental->pickup_maps_link }}" target="_blank" class="text-xs text-[#C1121F] hover:underline mt-1 inline-block">🗺️ Buka Google Maps</a>@endif
        </div>
        @if($rental->destination_address)
        <div class="mt-3 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
            <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">🎯 Alamat Tujuan</span>
            <p class="font-medium text-[#111111] mt-1">{{ $rental->destination_address }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- Lokasi Pengambilan (self_drive) --}}
    @if($rental->type == 'self_drive')
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mt-6 shadow-sm">
        <h2 class="font-bold text-lg text-[#111111] mb-3">📍 Lokasi Pengambilan Mobil</h2>
        <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
            <p class="font-medium text-[#111111]">{{ $pickupAddr }}</p>
            <a href="{{ $pickupMaps }}" target="_blank" class="text-xs text-[#C1121F] hover:underline mt-1 inline-block">🗺️ Buka Google Maps</a>
        </div>
    </div>
    @endif

    {{-- Assign Supir (hanya with_driver) --}}
    @if($rental->type == 'with_driver')
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mt-6 shadow-sm">
        <h2 class="font-bold text-lg text-[#111111] mb-4">👨‍✈️ Supir</h2>
        
        @if($rental->driver)
        <div class="flex items-center gap-4 p-4 bg-green-50 border border-green-200 rounded-[12px]">
            <div class="w-14 h-14 rounded-full bg-[#F5F5F5] flex items-center justify-center overflow-hidden border-2 border-green-300">
                @if($rental->driver->avatar_url)<img src="{{ $rental->driver->avatar_url }}" class="w-full h-full object-cover">@else<span class="text-2xl">👨‍✈️</span>@endif
            </div>
            <div class="flex-1">
                <p class="font-bold text-[#111111] text-lg">{{ $rental->driver->name }}</p>
                <p class="text-sm text-gray-500 font-light">📞 {{ $rental->driver->phone }}</p>
            </div>
            <form action="{{ route('agency.rental.assign-driver', $rental) }}" method="POST" class="flex gap-2 items-end">
                @csrf
                <select name="driver_id" class="text-xs border border-[#E5E5E5] rounded-[12px] px-3 py-2 bg-white text-[#111111]">
                    <option value="">Ganti Supir...</option>
                    @foreach(auth()->user()->agency->drivers()->where('is_active', true)->get() as $d)
                    <option value="{{ $d->id }}" {{ $rental->driver_id == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-[#C1121F] text-white px-4 py-2 rounded-[12px] text-xs font-medium hover:bg-[#8A0F18] transition">Update</button>
            </form>
        </div>
        @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-[12px] p-4 mb-4">
            <p class="text-sm text-yellow-700 font-light">Supir belum ditugaskan. Pilih supir di bawah.</p>
        </div>
        <form action="{{ route('agency.rental.assign-driver', $rental) }}" method="POST" class="flex gap-3 items-end">
            @csrf
            <div class="flex-1">
                <select name="driver_id" class="w-full px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111] text-sm" required>
                    <option value="">Pilih Supir...</option>
                    @foreach(auth()->user()->agency->drivers()->where('is_active', true)->get() as $d)
                    <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->phone }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-[#C1121F] text-white px-6 py-2.5 rounded-[12px] font-semibold hover:bg-[#8A0F18] transition">Tugaskan Supir</button>
        </form>
        @endif
    </div>
    @endif

    {{-- Tombol Aksi --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mt-6 shadow-sm">
        <h2 class="font-bold text-lg text-[#111111] mb-4">Aksi</h2>
        
        <div class="flex flex-wrap gap-3">
            @if($rental->status == 'paid')
            <form action="{{ route('agency.rental.verify-pickup', $rental) }}" method="POST" class="flex-1">
                @csrf
                <button type="submit" class="w-full bg-[#C1121F] text-white py-3 rounded-[12px] font-semibold hover:bg-[#8A0F18] transition" onclick="return confirm('Verifikasi pengambilan mobil?')">
                    ✅ Verifikasi Pengambilan
                </button>
            </form>
            @endif

            @if($rental->status == 'active')
            <form action="{{ route('agency.rental.verify-return', $rental) }}" method="POST" class="flex-1">
                @csrf
                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-[12px] font-semibold hover:bg-blue-700 transition" onclick="return confirm('Verifikasi pengembalian mobil?')">
                    🔄 Verifikasi Pengembalian
                </button>
            </form>
            @endif

            @if($rental->status == 'returned')
            <form action="{{ route('agency.rental.complete', $rental) }}" method="POST" class="flex-1">
                @csrf
                <button type="submit" class="w-full bg-[#C1121F] text-white py-3 rounded-[12px] font-semibold hover:bg-[#8A0F18] transition" onclick="return confirm('Selesaikan rental? Dana akan masuk ke saldo.')">
                    🎉 Selesaikan Rental
                </button>
            </form>
            @endif
        </div>

        @if($rental->notes)
        <div class="mt-4 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
            <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Catatan Customer</span>
            <p class="text-sm text-[#111111] mt-1 font-light">{{ $rental->notes }}</p>
        </div>
        @endif
    </div>
</div>
@endsection