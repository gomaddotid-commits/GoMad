@props([
    'type' => 'booking', // booking, rental, promo, schedule, vehicle
    'count' => 1,
])

@php
    $items = max(1, min((int)$count, 20));
@endphp

<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @for($i = 0; $i < $items; $i++)
        @if($type === 'booking')
            {{-- Skeleton Card Booking --}}
            <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-4 animate-pulse">
                <div class="flex items-center gap-3">
                    {{-- Status circle --}}
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex-shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        {{-- Kode booking + status --}}
                        <div class="flex items-center gap-2">
                            <div class="h-4 bg-gray-200 rounded w-24"></div>
                            <div class="h-5 bg-gray-200 rounded-full w-16"></div>
                        </div>
                        {{-- Rute --}}
                        <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                        {{-- Tanggal --}}
                        <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                    </div>
                    {{-- Harga --}}
                    <div class="text-right flex-shrink-0 space-y-1">
                        <div class="h-5 bg-gray-200 rounded w-20"></div>
                        <div class="h-3 bg-gray-200 rounded w-10 ml-auto"></div>
                    </div>
                </div>
            </div>

        @elseif($type === 'rental')
            {{-- Skeleton Card Rental --}}
            <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-4 animate-pulse">
                <div class="flex items-center gap-3">
                    {{-- Foto mobil --}}
                    <div class="w-12 h-12 rounded-lg bg-gray-200 flex-shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        {{-- Kode + status --}}
                        <div class="flex items-center gap-2">
                            <div class="h-4 bg-gray-200 rounded w-20"></div>
                            <div class="h-5 bg-gray-200 rounded-full w-16"></div>
                        </div>
                        {{-- Nama mobil --}}
                        <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                        {{-- Tanggal + durasi --}}
                        <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                    </div>
                    {{-- Harga --}}
                    <div class="text-right flex-shrink-0 space-y-1">
                        <div class="h-5 bg-gray-200 rounded w-20"></div>
                        <div class="h-3 bg-gray-200 rounded w-14 ml-auto"></div>
                    </div>
                </div>
            </div>

        @elseif($type === 'promo')
            {{-- Skeleton Card Promo --}}
            <div class="bg-white border border-[#E5E7EB] rounded-[12px] overflow-hidden animate-pulse">
                {{-- Gambar --}}
                <div class="h-32 bg-gray-200"></div>
                <div class="p-4 space-y-3">
                    {{-- Badge modul --}}
                    <div class="h-5 bg-gray-200 rounded-full w-16"></div>
                    {{-- Nama promo --}}
                    <div class="h-5 bg-gray-200 rounded w-3/4"></div>
                    {{-- Deskripsi --}}
                    <div class="h-3 bg-gray-200 rounded w-full"></div>
                    <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                    {{-- Diskon + periode --}}
                    <div class="flex justify-between items-center pt-3 border-t border-[#E5E7EB]">
                        <div class="h-6 bg-gray-200 rounded w-24"></div>
                        <div class="h-4 bg-gray-200 rounded w-28"></div>
                    </div>
                </div>
            </div>

        @elseif($type === 'schedule')
            {{-- Skeleton Card Jadwal --}}
            <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-4 animate-pulse">
                <div class="flex items-center gap-3 mb-3">
                    {{-- Logo agency --}}
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex-shrink-0"></div>
                    <div class="flex-1 space-y-1">
                        <div class="h-4 bg-gray-200 rounded w-28"></div>
                        <div class="h-3 bg-gray-200 rounded w-20"></div>
                    </div>
                </div>
                {{-- Nama rute --}}
                <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                {{-- Kota asal-tujuan --}}
                <div class="h-3 bg-gray-200 rounded w-1/2 mb-3"></div>
                {{-- Box info --}}
                <div class="bg-[#F9FAFB] rounded-[10px] p-3 mb-3 space-y-1">
                    <div class="flex justify-between">
                        <div class="h-4 bg-gray-200 rounded w-20"></div>
                        <div class="h-4 bg-gray-200 rounded w-14"></div>
                    </div>
                    <div class="flex justify-between">
                        <div class="h-3 bg-gray-200 rounded w-16"></div>
                        <div class="h-3 bg-gray-200 rounded w-12"></div>
                    </div>
                </div>
                {{-- Harga + button --}}
                <div class="flex justify-between items-center pt-3 border-t border-[#E5E7EB]">
                    <div class="space-y-1">
                        <div class="h-5 bg-gray-200 rounded w-24"></div>
                        <div class="h-3 bg-gray-200 rounded w-14"></div>
                    </div>
                    <div class="h-9 bg-gray-200 rounded-[10px] w-20"></div>
                </div>
            </div>

        @elseif($type === 'vehicle')
            {{-- Skeleton Card Kendaraan --}}
            <div class="bg-white border border-[#E5E7EB] rounded-[12px] overflow-hidden animate-pulse">
                {{-- Foto --}}
                <div class="h-40 bg-gray-200"></div>
                <div class="p-4 space-y-3">
                    {{-- Logo agency + rating --}}
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-gray-200"></div>
                        <div class="space-y-1 flex-1">
                            <div class="h-3 bg-gray-200 rounded w-24"></div>
                            <div class="h-3 bg-gray-200 rounded w-16"></div>
                        </div>
                    </div>
                    {{-- Nama mobil --}}
                    <div class="h-4 bg-gray-200 rounded w-2/3"></div>
                    {{-- Plat --}}
                    <div class="h-3 bg-gray-200 rounded w-20"></div>
                    {{-- Harga --}}
                    <div class="bg-[#F9FAFB] rounded-[12px] p-2.5">
                        <div class="flex justify-between">
                            <div class="h-3 bg-gray-200 rounded w-12"></div>
                            <div class="h-4 bg-gray-200 rounded w-20"></div>
                        </div>
                    </div>
                    {{-- Mini kalender --}}
                    <div class="flex gap-0.5">
                        @for($j = 0; $j < 30; $j++)
                            <div class="w-3 h-3 rounded-sm bg-gray-200"></div>
                        @endfor
                    </div>
                    {{-- Button --}}
                    <div class="h-9 bg-gray-200 rounded-[10px] w-full mt-3"></div>
                </div>
            </div>

        @else
            {{-- Generic Skeleton Card --}}
            <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-4 animate-pulse space-y-3">
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                <div class="h-3 bg-gray-200 rounded w-full"></div>
                <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                <div class="h-10 bg-gray-200 rounded-[10px] w-full mt-2"></div>
            </div>
        @endif
    @endfor
</div>