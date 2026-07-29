@props([
    'cols' => 5,
    'rows' => 5,
])

@php
    $totalRows = max(1, min((int)$rows, 20));
    $totalCols = max(1, min((int)$cols, 10));
@endphp

<div {{ $attributes->merge(['class' => 'bg-white border border-[#E5E7EB] rounded-[12px] overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-[#F5F5F5] border-b border-[#E5E5E5]">
                <tr>
                    @for($c = 0; $c < $totalCols; $c++)
                    <th class="px-4 py-3">
                        <div class="h-3 bg-gray-200 rounded w-16 animate-pulse"></div>
                    </th>
                    @endfor
                </tr>
            </thead>
            <tbody class="divide-y divide-[#E5E5E5]">
                @for($r = 0; $r < $totalRows; $r++)
                <tr>
                    @for($c = 0; $c < $totalCols; $c++)
                    <td class="px-4 py-3">
                        <div class="h-4 bg-gray-200 rounded animate-pulse {{ $c === 0 ? 'w-24' : ($c === $totalCols - 1 ? 'w-12 ml-auto' : 'w-20') }}"></div>
                    </td>
                    @endfor
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>