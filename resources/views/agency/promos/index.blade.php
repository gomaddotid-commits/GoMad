@extends('layouts.agency')

@section('title', 'Promo')
@section('content')
@php
    $promos = \App\Models\Promo::active()->selective()->latest()->get();
    $schedules = auth()->user()->agency->schedules()->where('departure_date', '>=', now()->toDateString())->where('is_active', true)->with(['route', 'promos'])->latest()->limit(20)->get();
@endphp

<div>
    <h1 class="text-2xl font-bold text-secondary mb-6">Promo Tersedia</h1>

    {{-- Promo Selektif --}}
    <div class="mb-8">
        <h2 class="font-bold text-lg text-secondary mb-4">Promo Selektif</h2>
        @if($promos->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">Belum ada promo selektif.</div>
        @else
        <div class="grid md:grid-cols-2 gap-4">
            @foreach($promos as $promo)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 border-l-4 border-purple-500">
                <h3 class="font-bold text-lg">{{ $promo->name }}</h3>
                <p class="text-sm text-gray-500 mb-2">{{ $promo->description }}</p>
                <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                    <div class="bg-purple-50 rounded-xl p-2 text-center"><span class="text-purple-700 font-bold text-lg">{{ $promo->discount_percent }}%</span><span class="text-xs block">Diskon</span></div>
                    <div class="bg-purple-50 rounded-xl p-2 text-center"><span class="text-purple-700 font-bold">Rp {{ number_format($promo->max_discount, 0, ',', '.') }}</span><span class="text-xs block">Maks</span></div>
                </div>
                <p class="text-xs text-gray-500">📅 {{ $promo->start_date->format('d M') }} - {{ $promo->end_date->format('d M Y') }}</p>
                <p class="text-xs text-gray-500">💰 {{ $promo->cost_bearer_label }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Pasang Promo ke Jadwal --}}
    <div>
        <h2 class="font-bold text-lg text-secondary mb-4">Pasang Promo ke Jadwal</h2>
        @if($schedules->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">Belum ada jadwal.</div>
        @else
        <div class="space-y-3">
            @foreach($schedules as $schedule)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                <div>
                    <span class="font-semibold">{{ $schedule->route->route_name }}</span>
                    <span class="text-sm text-gray-500 ml-2">{{ $schedule->departure_date->format('d M Y') }} {{ $schedule->departure_time }}</span>
                    @if($schedule->promos->isNotEmpty())
                    <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded ml-2">{{ $schedule->promos->first()->name }}</span>
                    @endif
                </div>
                @if($promos->isNotEmpty())
                <form action="{{ route('agency.promos.attach') }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="hidden" name="schedule_id" value="{{ $schedule->id }}">
                    <select name="promo_id" class="text-xs border rounded-lg px-2 py-1.5 bg-gray-50">
                        @foreach($promos as $promo)
                        <option value="{{ $promo->id }}">{{ $promo->name }} ({{ $promo->discount_percent }}%)</option>
                        @endforeach
                    </select>
                    <button type="submit" class="bg-primary-600 text-white px-3 py-1.5 rounded-lg text-xs font-medium hover:bg-primary-700">Pasang</button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection