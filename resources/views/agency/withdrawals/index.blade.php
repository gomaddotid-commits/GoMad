@extends('layouts.agency')

@section('title', 'Riwayat Penarikan')
@section('content')
<!-- File: resources/views/agency/withdrawals/index.blade.php -->

<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Riwayat Penarikan</h1>
        <a href="{{ route('agency.withdrawals.create') }}" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary-dark">+ Tarik Dana</a>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">ID</th>
                    <th class="px-4 py-3 text-right">Jumlah</th>
                    <th class="px-4 py-3 text-left">Bank</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right">Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($withdrawals as $w)
                <tr class="border-t">
                    <td class="px-4 py-3 font-mono text-xs">#{{ $w->id }}</td>
                    <td class="px-4 py-3 text-right font-medium">Rp {{ number_format($w->amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-xs">{{ $w->bank_name }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded text-xs 
                            @if(in_array($w->status, ['approved','completed'])) bg-green-100 text-green-800
                            @elseif($w->status == 'pending') bg-yellow-100 text-yellow-800
                            @elseif($w->status == 'rejected') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ $w->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right text-xs text-gray-500">{{ $w->created_at->format('d M Y H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada penarikan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection