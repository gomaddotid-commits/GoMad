@extends('layouts.agency')

@section('title', 'Review')
@section('content')
<div>
    <h1 class="text-2xl font-bold text-[#111827] mb-6">Review Customer</h1>

    @if($reviews->isEmpty())
    <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-8 text-center text-gray-500 shadow-gomad font-light">Belum ada review.</div>
    @else
    <div class="space-y-4">
        @foreach($reviews as $review)
        <div class="bg-white border border-[#E5E7EB] rounded-[12px] p-5 shadow-gomad hover:border-[#BA1826] transition-colors">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-semibold text-[#111827]">{{ $review->customer->name ?? 'Customer' }}</p>
                    <div class="text-yellow-400 text-sm">
                        @for($i = 1; $i <= 5; $i++){{ $i <= $review->rating ? '⭐' : '☆' }}@endfor
                    </div>
                </div>
                <span class="text-xs text-gray-400 font-light">{{ $review->created_at->format('d M Y') }}</span>
            </div>
            @if($review->review)
            <p class="mt-2 text-gray-600 text-sm font-light">{{ $review->review }}</p>
            @endif
        </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $reviews->links() }}</div>
    @endif
</div>
@endsection