@extends('layouts.admin')

@section('title', 'Detail Rental')
@section('content')

@php
    $vehicle = $rental->vehicle;
    $setting = $vehicle->rentalSetting ?? null;
    $customer = $rental->customer;
    $agency = $rental->agency;
@endphp

<div class="max-w-5xl mx-auto">
    <a href="{{ route('admin.rental.index') }}" class="text-[#C1121F] text-sm mb-4 inline-block hover:underline">
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
                <p class="text-sm text-gray-500 mt-2 font-light">
                    Dibuat: {{ $rental->created_at->format('d M Y H:i') }}
                </p>
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
                    <p class="text-xs text-blue-700 font-light">
                        KTP: {{ $customer->customerDocuments->ktp_number ?? '-' }} 
                        @if($customer->customerDocuments->ktp_verified) ✅ @else ❌ @endif
                    </p>
                    <p class="text-xs text-blue-700 font-light">
                        SIM: {{ $customer->customerDocuments->sim_number ?? '-' }} 
                        @if($customer->customerDocuments->sim_verified) ✅ @else ❌ @endif
                    </p>
                </div>
                @endif
            </div>
        </div>

        {{-- Info Kendaraan & Agency --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <h2 class="font-bold text-lg text-[#111111] mb-4">Informasi Kendaraan</h2>
            <div class="flex items-center gap-4 mb-4">
                <div class="w-24 h-20 bg-[#F5F5F5] rounded-[12px] overflow-hidden flex-shrink-0 border border-[#E5E5E5]">
                    @if($vehicle->vehicle_image)
                    <img src="{{ $vehicle->vehicle_image }}" class="w-full h-full object-cover">
                    @else
                    <div class="w-full h-full flex items-center justify-center text-2xl">🚗</div>
                    @endif
                </div>
                <div>
                    <p class="font-bold text-[#111111]">{{ $vehicle->brand }} {{ $vehicle->model }}</p>
                    <p class="text-sm text-gray-500 font-mono">{{ $vehicle->plate_number }}</p>
                    <p class="text-xs text-gray-400 font-light">{{ $vehicle->year }} • {{ $vehicle->capacity }} seat</p>
                </div>
            </div>
            
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Agency</span>
                <p class="font-semibold text-[#111111]">{{ $agency->agency_name ?? '-' }}</p>
                <p class="text-xs text-gray-500 font-light">{{ $agency->address ?? '-' }}</p>
                @if($agency->contact_alternate)
                <p class="text-xs text-gray-500 font-light">📞 {{ $agency->contact_alternate }}</p>
                @endif
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
                <div class="flex justify-between bg-green-50 border border-green-200 rounded-[12px] p-3">
                    <span class="text-gray-500 font-light">Diambil</span>
                    <span class="font-semibold text-green-700">{{ $rental->started_at->format('d M Y H:i') }}</span>
                </div>
                @endif

                @if($rental->returned_at)
                <div class="flex justify-between bg-blue-50 border border-blue-200 rounded-[12px] p-3">
                    <span class="text-gray-500 font-light">Dikembalikan</span>
                    <span class="font-semibold text-blue-700">{{ $rental->returned_at->format('d M Y H:i') }}</span>
                </div>
                @endif

                @if($rental->cancelled_at)
                <div class="flex justify-between bg-red-50 border border-red-200 rounded-[12px] p-3">
                    <span class="text-gray-500 font-light">Dibatalkan</span>
                    <span class="font-semibold text-red-700">{{ $rental->cancelled_at->format('d M Y H:i') }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Rincian Harga --}}
        <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
            <h2 class="font-bold text-lg text-[#111111] mb-4">Rincian Pembayaran</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500 font-light">Harga sewa ({{ $rental->duration }} {{ $rental->duration_unit }})</span>
                    <span class="text-[#111111]">Rp {{ number_format($rental->price_per_unit * $rental->duration, 0, ',', '.') }}</span>
                </div>
                @if($rental->driver_fee_per_unit > 0)
                <div class="flex justify-between">
                    <span class="text-gray-500 font-light">Biaya Supir</span>
                    <span class="text-[#111111]">Rp {{ number_format($rental->driver_fee_per_unit * $rental->duration, 0, ',', '.') }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-gray-500 font-light">Subtotal</span>
                    <span class="font-semibold text-[#111111]">Rp {{ number_format($rental->subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 font-light">Biaya Platform</span>
                    <span class="text-[#111111]">Rp {{ number_format($rental->platform_fee, 0, ',', '.') }}</span>
                </div>
                @if($rental->deposit_amount > 0)
                <div class="flex justify-between">
                    <span class="text-gray-500 font-light">Deposit</span>
                    <span class="text-[#111111]">Rp {{ number_format($rental->deposit_amount, 0, ',', '.') }}</span>
                </div>
                @endif
                <hr class="border-[#E5E5E5]">
                <div class="flex justify-between text-base font-bold">
                    <span>Total</span>
                    <span class="text-[#C1121F] font-mono">Rp {{ number_format($rental->total_price, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Status Pembayaran --}}
            @if($rental->payment)
            <div class="mt-4 p-3 rounded-[12px] text-sm border
                @if($rental->payment->status == 'paid') bg-green-50 border-green-200 text-green-700
                @elseif($rental->payment->status == 'pending') bg-yellow-50 border-yellow-200 text-yellow-700
                @else bg-[#F5F5F5] border-[#E5E5E5] text-gray-600 @endif">
                <span class="font-medium">Status Pembayaran:</span> {{ $rental->payment->status_label ?? $rental->payment->status }}
                <br><span class="text-xs">Metode: {{ $rental->payment->payment_type == 'midtrans' ? 'Online (Midtrans)' : 'Warung GoMad' }}</span>
                @if($rental->payment->transaction_id)
                <br><span class="text-[10px] font-mono">ID: {{ $rental->payment->transaction_id }}</span>
                @endif
                @if($rental->payment->paid_at)
                <br><span class="text-xs">Dibayar: {{ \Carbon\Carbon::parse($rental->payment->paid_at)->format('d M Y H:i') }}</span>
                @endif
            </div>
            @endif
        </div>
    </div>

    {{-- Catatan --}}
    @if($rental->notes)
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mt-6 shadow-sm">
        <h2 class="font-bold text-lg text-[#111111] mb-3">Catatan Customer</h2>
        <p class="text-sm text-[#111111] font-light">{{ $rental->notes }}</p>
    </div>
    @endif
</div>
@endsection