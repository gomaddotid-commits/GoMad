@extends('layouts.agency')

@section('title', 'Review')
@section('content')
<!-- File: resources/views/agency/reviews.blade.php -->

<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Review Customer</h1>

    @if($reviews->isEmpty())
    <div class="bg-white rounded-xl shadow p-8 text-center text-gray-500">Belum ada review.</div>
    @else
    <div class="space-y-4">
        @foreach($reviews as $review)
        <div class="bg-white rounded-xl shadow p-5">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-semibold">{{ $review->customer->name ?? 'Customer' }}</p>
                    <div class="text-yellow-400">
                        @for($i = 1; $i <= 5; $i++)
                            {{ $i <= $review->rating ? '⭐' : '☆' }}
                        @endfor
                    </div>
                </div>
                <span class="text-xs text-gray-400">{{ $review->created_at->format('d M Y') }}</span>
            </div>
            @if($review->review)
            <p class="mt-2 text-gray-600 text-sm">{{ $review->review }}</p>
            @endif
        </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $reviews->links() }}</div>
    @endif
</div>
@endsection