@extends('layouts.agency')

@section('title', 'Jadwal')
@section('content')
<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <h1 class="text-2xl font-bold text-secondary">Daftar Jadwal</h1>
        <a href="{{ route('agency.schedules.create') }}" class="btn-primary text-sm inline-flex items-center gap-2 self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Jadwal
        </a>
    </div>

    @if($schedules->isEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
        <div class="w-16 h-16 bg-primary-50 rounded-xl flex items-center justify-center mx-auto mb-4">
            <span class="text-2xl">📅</span>
        </div>
        <p class="text-gray-500 text-lg mb-4">Belum ada jadwal.</p>
        <a href="{{ route('agency.schedules.create') }}" class="btn-primary">Buat Jadwal Pertama</a>
    </div>
    @else
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Rute</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Tanggal</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Kendaraan</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Driver</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Okupansi</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($schedules as $schedule)
                    @php
                        $rate = $schedule->occupancy_rate;
                        $colorClass = $rate >= 80 ? 'text-red-600 bg-red-100' : ($rate >= 50 ? 'text-yellow-600 bg-yellow-100' : 'text-green-600 bg-green-100');
                        $canTransfer = app(\App\Services\PassengerTransferService::class)->canTransfer($schedule);
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium">{{ $schedule->route->route_name }}</td>
                        <td class="px-4 py-3 text-xs">{{ $schedule->departure_date->format('d M Y') }} <span class="text-gray-500">{{ $schedule->departure_time }}</span></td>
                        <td class="px-4 py-3 text-xs">{{ $schedule->vehicle->plate_number ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $schedule->driver->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $colorClass }}">{{ $rate }}%</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('agency.schedules.show', $schedule) }}" class="text-primary-600 hover:underline text-xs">Detail</a>
                                @if($canTransfer && $schedule->departure_date->isFuture())
                                <a href="{{ route('agency.schedules.transfer', $schedule) }}" class="text-orange-500 hover:underline text-xs">Transfer</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $schedules->links() }}</div>
    @endif
</div>
@endsection