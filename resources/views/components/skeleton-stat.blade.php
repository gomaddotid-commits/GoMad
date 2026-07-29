@props([
    'count' => 1,
    'cols' => 4,
])

@php
    $items = max(1, min((int)$count, 20));
    $gridCols = match((int)$cols) {
        2 => 'grid-cols-2',
        3 => 'grid-cols-3',
        5 => 'grid-cols-5',
        6 => 'grid-cols-6',
        default => 'grid-cols-4',
    };
@endphp

<div {{ $attributes->merge(['class' => "grid {$gridCols} gap-4"]) }}>
    @for($i = 0; $i < $items; $i++)
    <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-5 animate-pulse space-y-2">
        {{-- Label --}}
        <div class="h-3 bg-gray-200 rounded w-16"></div>
        {{-- Angka besar --}}
        <div class="h-8 bg-gray-200 rounded w-20 mt-2"></div>
        {{-- Sub-text --}}
        <div class="h-3 bg-gray-200 rounded w-24 mt-1"></div>
    </div>
    @endfor
</div>