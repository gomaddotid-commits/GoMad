@props(['rentalByCity' => []])

@if($rentalByCity->isNotEmpty())
<section class="section container-magazine border-b border-[#E5E7EB]" x-data="{ loading: true }" x-init="setTimeout(() => loading = false, 800)">
    
    {{-- ✅ SKELETON LOADING --}}
    <div x-show="loading" x-cloak>
        <div class="flex items-center gap-4 mb-12">
            <div class="h-px w-12 bg-gray-200"></div>
            <div class="h-8 bg-gray-200 rounded w-64 animate-pulse"></div>
        </div>
        <div class="grid md:grid-cols-4 gap-4">
            <x-skeleton-card type="vehicle" :count="8" />
        </div>
    </div>
    
    {{-- ✅ KONTEN ASLI --}}
    <div x-show="!loading" x-cloak>
        <div class="flex items-center gap-4 mb-12">
            <div class="h-px w-12 bg-[#BA1826]"></div>
            <h2 class="text-3xl font-bold tracking-tight text-[#111827]">Temukan Rental di Kotamu</h2>
        </div>
        <div class="space-y-10">
            @foreach($rentalByCity as $cityName => $cityRentals)
            @php
                $cityCode = $cityRentals->first()->vehicle->agency->city_code ?? null;
                $cityDisplay = $cityName !== 'Unknown' ? $cityName : 'Kota Lainnya';
                $displayRentals = $cityRentals->take(4);
            @endphp
            @if($displayRentals->isNotEmpty())
            <div>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-[#111827] flex items-center gap-2">
                        <span>📍</span> {{ $cityDisplay }}
                    </h3>
                    <a href="{{ route('rental.public', ['city_code' => $cityCode]) }}" 
                       class="text-sm text-[#BA1826] hover:underline font-medium flex items-center gap-1">
                        Lihat Semua Rental di {{ $cityDisplay }}
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach($displayRentals as $setting)
                    @php $vehicle = $setting->vehicle; $agency = $vehicle->agency; @endphp
                    <a href="{{ route('rental.public.show', $setting) }}" class="card-gomad overflow-hidden group/card p-0">
                        <div class="h-36 bg-[#F9FAFB] flex items-center justify-center overflow-hidden border-b border-[#E5E7EB] relative">
                            @if($vehicle->vehicle_image)
                            <img src="{{ $vehicle->vehicle_image }}" alt="{{ $vehicle->plate_number }}" class="w-full h-full object-cover group-hover/card:scale-105 transition-transform duration-500">
                            @else
                            <span class="text-4xl text-gray-300">🚗</span>
                            @endif
                            <div class="absolute top-2 left-2 flex gap-1">
                                @if($setting->allow_self_drive)
                                <span class="px-1.5 py-0.5 bg-blue-500 text-white text-[9px] font-mono uppercase rounded-full">Lepas Kunci</span>
                                @endif
                                @if($setting->allow_with_driver)
                                <span class="px-1.5 py-0.5 bg-green-500 text-white text-[9px] font-mono uppercase rounded-full">+Supir</span>
                                @endif
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-7 h-7 rounded-full bg-[#F9FAFB] flex items-center justify-center overflow-hidden flex-shrink-0 border border-[#E5E7EB]">
                                    @if($agency->logo)<img src="{{ $agency->logo }}" class="w-full h-full object-cover">@else<span class="text-xs">🏢</span>@endif
                                </div>
                                <span class="text-xs text-gray-500 font-light truncate">{{ $agency->agency_name }}</span>
                            </div>
                            <p class="font-bold text-sm text-[#111827]">{{ $vehicle->brand }} {{ $vehicle->model }}</p>
                            <p class="text-xs text-gray-500 font-mono">{{ $vehicle->plate_number }}</p>
                            <div class="mt-2 flex justify-between items-center">
                                <span class="font-bold text-[#BA1826] font-mono text-sm">
                                    @if($setting->price_per_day) Rp {{ number_format($setting->price_per_day, 0, ',', '.') }}/hari
                                    @elseif($setting->price_per_hour) Rp {{ number_format($setting->price_per_hour, 0, ',', '.') }}/jam
                                    @else Hubungi agency @endif
                                </span>
                                <span class="text-[#BA1826] group-hover/card:translate-x-1 transition-transform">→</span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</section>
@endif