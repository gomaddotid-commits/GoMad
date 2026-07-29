@props([
    'size' => 'md',
    'text' => null,
    'color' => '#BA1826',
    'bgColor' => 'transparent',
])

@php
    $sizes = [
        'sm' => 'w-4 h-4',
        'md' => 'w-8 h-8',
        'lg' => 'w-12 h-12',
        'xl' => 'w-16 h-16',
    ];
    
    $textSizes = [
        'sm' => 'text-xs',
        'md' => 'text-sm',
        'lg' => 'text-base',
        'xl' => 'text-lg',
    ];
    
    $spinnerSize = $sizes[$size] ?? $sizes['md'];
    $textSize = $textSizes[$size] ?? $textSizes['md'];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-2']) }}>
    <svg class="animate-spin {{ $spinnerSize }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="color: {{ $color }}">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    
    @if($text)
    <span class="{{ $textSize }} text-gray-500 font-light">{{ $text }}</span>
    @endif
</div>