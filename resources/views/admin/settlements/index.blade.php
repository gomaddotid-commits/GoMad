@extends('layouts.admin')

@section('title', 'Settlement')
@section('content')
<!-- File: resources/views/admin/settlements/index.blade.php -->
<!-- Deskripsi: Halaman daftar settlement admin -->

<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Daftar Settlement</h1>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">ID</th>
                    <th class="px-4 py-3 text-left">Warung</th>
                    <th class="px-4 py-3 text-left">Periode</th>
                    <th class="px-4 py-3 text-right">Jumlah</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($settlements as $s)
                <tr class="border-t">
                    <td class="px-4 py-3">#{{ $s->id }}</td>
                    <td class="px-4 py-3">{{ $s->paymentAgent->agent_name ?? '-' }}</td>
                    <td class="px-4 py-3 text-xs">{{ $s->period_start->format('d M') }} - {{ $s->period_end->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-right font-medium">Rp {{ number_format($s->amount_to_settle, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded text-xs 
                            @if($s->status == 'verified') bg-green-100 text-green-800
                            @elseif($s->status == 'paid') bg-blue-100 text-blue-800
                            @else bg-yellow-100 text-yellow-800 @endif">
                            {{ $s->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($s->status == 'paid')
                        <form action="{{ route('admin.settlements.verify', $s) }}" method="POST">
                            @csrf
                            <button class="text-green-600 hover:underline text-xs">Verifikasi</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada settlement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $settlements->links() }}</div>
</div>
@endsection