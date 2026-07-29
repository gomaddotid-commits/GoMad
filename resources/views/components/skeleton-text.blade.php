@props([
    'lines' => 3,
    'lastShorter' => true,
])

@php
    $totalLines = max(1, min((int)$lines, 20));
@endphp

<div {{ $attributes->merge(['class' => 'space-y-2 animate-pulse']) }}>
    @for($i = 0; $i < $totalLines; $i++)
        @php
            $isLast = $i === $totalLines - 1;
            $widthClass = 'w-full';
            
            if ($isLast && $lastShorter) {
                $widthClass = 'w-2/3';
            } elseif ($i === 0) {
                $widthClass = 'w-3/4';
            } elseif ($i === 1) {
                $widthClass = 'w-full';
            } else {
                $widthClass = 'w-5/6';
            }
        @endphp
        <div class="h-3 bg-gray-200 rounded {{ $widthClass }}"></div>
    @endfor
</div>