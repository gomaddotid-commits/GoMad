@extends('layouts.admin')

@section('title', 'Detail Rute')
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6 border-b border-[#E5E5E5] pb-3">
        <a href="{{ route('admin.routes.index') }}" class="text-[#C1121F] text-sm hover:underline">← Kembali</a>
        <div class="flex gap-2">
            <a href="{{ route('admin.routes.edit', $route) }}" class="bg-[#C1121F] text-white px-4 py-2 rounded-[12px] text-sm hover:bg-[#8A0F18]">Edit</a>
            <form action="{{ route('admin.routes.destroy', $route) }}" method="POST" onsubmit="return confirm('Nonaktifkan rute?')">
                @csrf @method('DELETE')
                <button class="bg-red-600 text-white px-4 py-2 rounded-[12px] text-sm hover:bg-red-700">Nonaktifkan</button>
            </form>
        </div>
    </div>

    {{-- Info Utama --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <div class="flex items-start gap-4">
            @if($route->photo)
            <div class="w-32 h-24 rounded-[12px] overflow-hidden flex-shrink-0 border border-[#E5E5E5]">
                <img src="{{ $route->photo }}" alt="{{ $route->route_name }}" class="w-full h-full object-cover">
            </div>
            @endif
            <div>
                <h1 class="text-2xl font-bold text-[#111111]">{{ $route->route_name }}</h1>
                <div class="flex items-center gap-2 mt-2">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border {{ $route->is_active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' }}">
                        {{ $route->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    @if($route->cod_available)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border bg-orange-50 text-orange-700 border-orange-200">
                        COD Available
                    </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Kota Asal</span>
                <p class="font-semibold text-[#111111]">{{ $route->origin_city_name }}</p>
            </div>
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Kota Tujuan</span>
                <p class="font-semibold text-[#111111]">{{ $route->destination_city_name }}</p>
            </div>
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Jarak</span>
                <p class="font-semibold text-[#111111]">{{ $route->distance_km ?? '-' }} km</p>
            </div>
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Estimasi</span>
                <p class="font-semibold text-[#111111]">{{ $route->estimated_duration ?? '-' }} menit</p>
            </div>
            @if($route->max_price)
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Maks Harga</span>
                <p class="font-semibold text-[#C1121F]">Rp {{ number_format($route->max_price, 0, ',', '.') }}</p>
            </div>
            @endif
            @if($route->cod_min_deposit && $route->cod_available)
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Min Deposit COD</span>
                <p class="font-semibold text-[#111111]">Rp {{ number_format($route->cod_min_deposit, 0, ',', '.') }}</p>
            </div>
            @endif
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3">
                <span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Metode Bayar</span>
                <p class="font-semibold text-[#111111] text-sm">
                    @foreach($route->payment_methods_array as $method)
                    <span class="inline-block px-2 py-0.5 bg-white rounded-full text-[10px] font-mono mr-1">
                        {{ $method === 'midtrans' ? '💳' : ($method === 'cash' ? '🏪' : '🚗') }}
                    </span>
                    @endforeach
                </p>
            </div>
        </div>
    </div>

    {{-- Stops --}}
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
        <div class="flex justify-between items-center mb-4 border-b border-[#E5E5E5] pb-3">
            <h2 class="text-lg font-bold text-[#111111]">🛑 Stops ({{ $route->stops->count() }})</h2>
            <button onclick="document.getElementById('addStopForm').classList.toggle('hidden')" 
                    class="bg-[#C1121F] text-white px-3 py-1 rounded-[12px] text-sm hover:bg-[#8A0F18]">
                + Tambah Stop
            </button>
        </div>

        {{-- Add Stop Form --}}
        <form id="addStopForm" action="{{ route('admin.routes.stops.add', $route) }}" method="POST" class="hidden bg-[#F5F5F5] border border-[#E5E5E5] p-4 rounded-[12px] mb-4">
            @csrf
            <div class="flex gap-3">
                <select name="city_code" class="flex-1 px-3 py-2 border border-[#E5E5E5] rounded-[12px] focus:border-[#C1121F] outline-none bg-white text-[#111111]" required>
                    <option value="">Pilih Kota</option>
                    @foreach(\App\Models\City::with('province')->orderBy('name')->get() as $city)
                    <option value="{{ $city->code }}">{{ $city->name }} ({{ $city->province->name }})</option>
                    @endforeach
                </select>
                <button type="submit" class="bg-[#C1121F] text-white px-4 py-2 rounded-[12px] text-sm">Simpan</button>
            </div>
        </form>

        {{-- Stops List dengan Visual --}}
        <div class="space-y-3">
            @foreach($route->stops as $stop)
            <div class="flex items-center gap-4 p-4 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px]">
                {{-- Nomor Stop --}}
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0
                    @if($stop->isFirst()) bg-green-500 text-white
                    @elseif($stop->isLast()) bg-red-500 text-white
                    @else bg-[#C1121F] text-white @endif">
                    {{ $stop->stop_order }}
                </div>

                {{-- Info Stop --}}
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-[#111111]">{{ $stop->city_name }}</span>
                        @if($stop->isFirst())
                        <span class="text-[10px] font-mono bg-green-50 text-green-700 px-2 py-0.5 rounded-full border border-green-200">📍 Titik Jemput Awal</span>
                        @elseif($stop->isLast())
                        <span class="text-[10px] font-mono bg-red-50 text-red-700 px-2 py-0.5 rounded-full border border-red-200">🎯 Titik Turun Akhir</span>
                        @else
                        <span class="text-[10px] font-mono bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full border border-blue-200">🔄 Transit</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-4 mt-1 text-xs text-gray-500 font-light">
                        @if($stop->latitude && $stop->longitude)
                        <span>📍 {{ $stop->latitude }}, {{ $stop->longitude }}</span>
                        @endif
                        @if($stop->distance_from_origin)
                        <span>📏 {{ $stop->distance_from_origin }} km dari asal</span>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                @if(!$stop->isFirst() && !$stop->isLast())
                <form action="{{ route('admin.routes.stops.remove', [$route, $stop]) }}" method="POST" onsubmit="return confirm('Hapus stop ini?')" class="flex-shrink-0">
                    @csrf @method('DELETE')
                    <button class="text-[#C1121F] hover:underline text-sm font-medium">🗑️ Hapus</button>
                </form>
                @else
                <span class="text-[10px] text-gray-400 font-mono flex-shrink-0">Wajib</span>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Visualisasi Rute --}}
        <div class="mt-6 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-4">
            <h4 class="font-mono uppercase tracking-wider text-xs font-bold text-[#111111] mb-3">Visualisasi Rute</h4>
            <div class="flex items-center flex-wrap gap-1 text-sm font-mono">
                @foreach($route->stops as $stop)
                    <span class="px-2 py-1 bg-white rounded-lg border border-[#E5E5E5] {{ $stop->isFirst() ? 'text-green-700 font-bold' : ($stop->isLast() ? 'text-red-700 font-bold' : 'text-[#111111]') }}">
                        {{ $stop->city_name }}
                    </span>
                    @if(!$stop->isLast())
                    <span class="text-gray-400">→</span>
                    @endif
                @endforeach
            </div>
            @if($route->distance_km)
            <p class="text-xs text-gray-500 mt-3 font-light">
                Total jarak: <strong>{{ $route->distance_km }} km</strong> | 
                Estimasi perjalanan: <strong>{{ floor($route->estimated_duration / 60) }} jam {{ $route->estimated_duration % 60 }} menit</strong>
            </p>
            @endif
        </div>
    </div>

    {{-- Jadwal Terkait --}}
    @php
        $relatedSchedules = $route->schedules()
            ->with(['agency', 'vehicle'])
            ->where('departure_date', '>=', now()->toDateString())
            ->where('is_active', true)
            ->orderBy('departure_date')
            ->limit(5)
            ->get();
    @endphp

    @if($relatedSchedules->isNotEmpty())
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mt-6 shadow-sm">
        <h2 class="text-lg font-bold text-[#111111] mb-4">📅 Jadwal Aktif di Rute Ini</h2>
        <div class="space-y-3">
            @foreach($relatedSchedules as $schedule)
            <div class="flex justify-between items-center p-3 bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px]">
                <div>
                    <span class="font-semibold text-[#111111]">{{ $schedule->agency->agency_name ?? '-' }}</span>
                    <span class="text-sm text-gray-500 font-light ml-2">
                        {{ $schedule->departure_date->format('d M Y') }} {{ $schedule->departure_time }}
                    </span>
                </div>
                <div class="text-sm text-gray-500 font-light">
                    🚐 {{ $schedule->vehicle->plate_number ?? '-' }} | 
                    💰 Rp {{ number_format($schedule->price_per_seat, 0, ',', '.') }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($route->description)
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mt-6 shadow-sm">
        <h2 class="text-lg font-bold text-[#111111] mb-3">📝 Deskripsi</h2>
        <p class="text-sm text-gray-600 font-light leading-relaxed">{{ $route->description }}</p>
    </div>
    @endif
</div>
@endsection