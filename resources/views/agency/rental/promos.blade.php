@extends('layouts.agency')

@section('title', 'Promo Rental')
@section('content')

@php
    $agency = auth()->user()->agency;
@endphp

<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 border-b border-[#E5E5E5] pb-3">
        <div>
            <h1 class="text-2xl font-bold text-[#111111]">Promo Rental</h1>
            <p class="text-sm text-gray-500 font-light mt-1">Pasang promo ke kendaraan rental Anda</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Kolom Kiri: Daftar Promo Tersedia --}}
        <div class="lg:col-span-1">
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-5 shadow-sm sticky top-24">
                <h3 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-4">
                    🎫 Promo Tersedia 
                    <span class="text-gray-400 font-light">({{ $promos->count() }})</span>
                </h3>
                
                @if($promos->isEmpty())
                <div class="text-center py-8">
                    <span class="text-3xl block mb-2">🎫</span>
                    <p class="text-gray-500 text-sm font-light">Belum ada promo rental.</p>
                    <p class="text-gray-400 text-xs mt-1 font-light">Admin akan membuat promo untuk modul Rental.</p>
                </div>
                @else
                <div class="space-y-3">
                    @foreach($promos as $promo)
                    <div class="border border-[#E5E5E5] rounded-[12px] p-4 border-l-4 border-[#C1121F] hover:shadow-sm transition">
                        <h4 class="font-bold text-[#111111] text-sm">{{ $promo->name }}</h4>
                        <p class="text-xs text-gray-500 mt-1 font-light">{{ $promo->description ?? 'Tidak ada deskripsi' }}</p>
                        
                        {{-- Diskon --}}
                        <div class="mt-2 bg-[#C1121F]/5 border border-[#C1121F] rounded-lg p-2 text-center">
                            <span class="text-[#C1121F] font-bold">
                                @if(($promo->rental_discount_type ?? 'percent') == 'fixed')
                                    Potongan Rp {{ number_format($promo->rental_discount_amount, 0, ',', '.') }}
                                @else
                                    Diskon {{ $promo->rental_discount_amount ?? $promo->discount_percent }}%
                                    @if($promo->rental_max_discount > 0)
                                        <span class="text-xs font-light">(Maks Rp {{ number_format($promo->rental_max_discount, 0, ',', '.') }})</span>
                                    @endif
                                @endif
                            </span>
                        </div>
                        
                        <p class="text-[10px] text-gray-400 mt-2 font-mono tracking-wider">
                            📅 {{ $promo->start_date->format('d M') }} - {{ $promo->end_date->format('d M Y') }}
                        </p>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Kolom Kanan: Daftar Kendaraan + Pasang Promo --}}
        <div class="lg:col-span-2">
            <h3 class="font-bold text-lg text-[#111111] mb-4">🚗 Kendaraan Rental Anda</h3>
            
            @if($vehicles->isEmpty())
            <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-8 text-center shadow-sm">
                <span class="text-3xl block mb-3">🚗</span>
                <p class="text-gray-500 font-light">Belum ada kendaraan yang disetup untuk rental.</p>
                <a href="{{ route('agency.rental.vehicles') }}" class="text-[#C1121F] hover:underline mt-2 inline-block font-medium text-sm">Setup Kendaraan →</a>
            </div>
            @else
            <div class="space-y-4">
                @foreach($vehicles as $vehicle)
                @php
                    $setting = $vehicle->rentalSetting;
                    $attachedPromos = $vehicle->rentalPromos;
                @endphp
                <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-5 shadow-sm hover:border-[#C1121F] transition-colors">
                    <div class="flex items-center gap-4 mb-4">
                        {{-- Foto --}}
                        <div class="w-20 h-16 bg-[#F5F5F5] rounded-[12px] overflow-hidden flex-shrink-0 border border-[#E5E5E5]">
                            @if($vehicle->vehicle_image)
                            <img src="{{ $vehicle->vehicle_image }}" class="w-full h-full object-cover">
                            @else
                            <div class="w-full h-full flex items-center justify-center text-xl">🚗</div>
                            @endif
                        </div>
                        
                        <div class="flex-1">
                            <h4 class="font-bold text-[#111111]">{{ $vehicle->brand }} {{ $vehicle->model }}</h4>
                            <p class="text-sm text-gray-500 font-mono">{{ $vehicle->plate_number }}</p>
                            
                            {{-- Harga --}}
                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-500 font-light">
                                @if($setting && $setting->price_per_day)
                                <span>Harian: Rp {{ number_format($setting->price_per_day, 0, ',', '.') }}</span>
                                @endif
                                @if($setting && $setting->allow_self_drive)
                                <span class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full text-[10px] font-mono">Lepas Kunci</span>
                                @endif
                                @if($setting && $setting->allow_with_driver)
                                <span class="bg-green-50 text-green-700 px-2 py-0.5 rounded-full text-[10px] font-mono">+Supir</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Promo yang sudah terpasang --}}
                    @if($attachedPromos->isNotEmpty())
                    <div class="mb-3">
                        <p class="text-[10px] font-mono uppercase tracking-wider text-gray-500 mb-2">Promo Terpasang:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($attachedPromos as $attached)
                            <div class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-lg px-3 py-1.5">
                                <span class="text-xs font-medium text-green-700">🎫 {{ $attached->name }}</span>
                                <form action="{{ route('agency.rental.promos.detach', [$vehicle, $attached]) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs" title="Lepas promo">✕</button>
                                </form>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Form Pasang Promo --}}
                    @if($promos->isNotEmpty())
                    <div class="border-t border-[#E5E5E5] pt-3">
                        <form action="{{ route('agency.rental.promos.attach') }}" method="POST" class="flex gap-2">
                            @csrf
                            <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
                            <select name="promo_id" class="flex-1 text-xs border border-[#E5E5E5] rounded-[12px] px-3 py-2 bg-[#F5F5F5] text-[#111111] focus:border-[#C1121F] outline-none">
                                <option value="">Pilih Promo...</option>
                                @foreach($promos as $promo)
                                    @if(!$attachedPromos->contains('id', $promo->id))
                                    <option value="{{ $promo->id }}">{{ $promo->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <button type="submit" class="bg-[#C1121F] text-white px-4 py-2 rounded-[12px] text-xs font-medium hover:bg-[#8A0F18] transition whitespace-nowrap">
                                Pasang
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
@endsection