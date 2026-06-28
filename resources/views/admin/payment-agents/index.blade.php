@extends('layouts.admin')

@section('title', 'Warung GoMad')
@section('content')
<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Daftar Warung GoMad</h1>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-md p-4 mb-6">
        <form action="{{ route('admin.payment-agents.index') }}" method="GET" class="flex flex-wrap gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama warung, pemilik, alamat..." 
                   class="flex-1 min-w-[200px] px-4 py-2 border rounded-lg text-sm">
            <select name="status" class="px-4 py-2 border rounded-lg text-sm">
                <option value="">Semua Status</option>
                <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>✅ Verified</option>
                <option value="unverified" {{ request('status') == 'unverified' ? 'selected' : '' }}>⏳ Unverified</option>
            </select>
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-sm hover:bg-primary-dark">Filter</button>
            @if(request()->anyFilled(['search', 'status']))
            <a href="{{ route('admin.payment-agents.index') }}" class="border px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50">Reset</a>
            @endif
        </form>
    </div>

    <!-- Statistik -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @php
            $totalAgents = \App\Models\PaymentAgent::count();
            $verifiedAgents = \App\Models\PaymentAgent::where('is_verified', true)->count();
            $pendingAgents = \App\Models\PaymentAgent::where('is_verified', false)->whereNotNull('agent_name')->count();
            $totalTransactions = \App\Models\CashPayment::where('status', 'confirmed')->count();
        @endphp
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-sm text-gray-500">Total Warung</p>
            <p class="text-2xl font-bold">{{ $totalAgents }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-sm text-gray-500">Terverifikasi</p>
            <p class="text-2xl font-bold text-green-600">{{ $verifiedAgents }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-sm text-gray-500">Menunggu Verifikasi</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $pendingAgents }}</p>
        </div>
        <div class="bg-white rounded-xl shadow p-4 text-center">
            <p class="text-sm text-gray-500">Total Transaksi Cash</p>
            <p class="text-2xl font-bold text-blue-600">{{ $totalTransactions }}</p>
        </div>
    </div>

    <!-- Tabel -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">Nama Warung</th>
                    <th class="px-4 py-3 text-left">Pemilik</th>
                    <th class="px-4 py-3 text-left">Alamat</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Transaksi</th>
                    <th class="px-4 py-3 text-right">Komisi</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agents as $agent)
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium">
                        {{ $agent->agent_name }}
                        @if($agent->rejection_reason)
                        <span class="block text-xs text-red-500 mt-0.5" title="Alasan penolakan: {{ $agent->rejection_reason }}">❌ Ditolak</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $agent->owner_name }}<br><span class="text-xs">{{ $agent->owner_phone }}</span></td>
                    <td class="px-4 py-3 text-gray-600 text-xs max-w-[200px] truncate">{{ $agent->address }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-medium 
                            @if($agent->is_verified) bg-green-100 text-green-800
                            @elseif($agent->agent_name) bg-yellow-100 text-yellow-800
                            @else bg-gray-100 text-gray-800 @endif">
                            @if($agent->is_verified) Verified
                            @elseif($agent->agent_name) Pending
                            @else Belum Setup
                            @endif
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">{{ $agent->total_transactions }}</td>
                    <td class="px-4 py-3 text-right text-green-600 font-medium">Rp {{ number_format($agent->total_commission, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.payment-agents.show', $agent) }}" class="text-primary hover:underline text-sm">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Tidak ada data warung.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $agents->links() }}</div>
</div>
@endsection