@extends('layouts.admin')

@section('title', 'Withdrawal')
@section('content')
<!-- File: resources/views/admin/withdrawals/index.blade.php -->
<!-- Deskripsi: Halaman daftar withdrawal admin -->

<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Daftar Withdrawal</h1>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">ID</th>
                    <th class="px-4 py-3 text-left">Agency</th>
                    <th class="px-4 py-3 text-right">Jumlah</th>
                    <th class="px-4 py-3 text-left">Bank</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($withdrawals as $w)
                <tr class="border-t">
                    <td class="px-4 py-3">#{{ $w->id }}</td>
                    <td class="px-4 py-3">{{ $w->agency->agency_name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right font-medium">Rp {{ number_format($w->amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-xs">{{ $w->bank_name }} - {{ substr($w->bank_account_number, -4) }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded text-xs 
                            @if($w->status == 'approved' || $w->status == 'completed') bg-green-100 text-green-800
                            @elseif($w->status == 'pending') bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800 @endif">
                            {{ $w->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($w->status == 'pending')
                        <form action="{{ route('admin.withdrawals.approve', $w) }}" method="POST" class="inline">
                            @csrf
                            <button class="text-green-600 hover:underline text-xs mr-2">Approve</button>
                        </form>
                        <form action="{{ route('admin.withdrawals.reject', $w) }}" method="POST" class="inline" onsubmit="return prompt('Alasan:') != null">
                            @csrf
                            <input type="hidden" name="reason" id="rejectReason{{ $w->id }}">
                            <button class="text-red-600 hover:underline text-xs" 
                                    onclick="document.getElementById('rejectReason{{ $w->id }}').value = prompt('Alasan:'); return document.getElementById('rejectReason{{ $w->id }}').value != null;">
                                Reject
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada withdrawal.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $withdrawals->links() }}</div>
</div>
@endsection