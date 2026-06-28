@extends('layouts.admin')

@section('title', 'Detail Customer')
@section('content')
<!-- File: resources/views/admin/customers/show.blade.php -->
<!-- Deskripsi: Halaman detail customer admin -->

<div class="max-w-4xl mx-auto">
    <a href="{{ route('admin.customers.index') }}" class="text-primary text-sm mb-4 inline-block">← Kembali</a>

    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold">{{ $user->name }}</h1>
                <p class="text-gray-500">{{ $user->email }}</p>
            </div>
            <div class="flex gap-2">
                <form action="{{ route('admin.customers.toggle-active', $user) }}" method="POST">
                    @csrf @method('PUT')
                    <button class="px-3 py-1 rounded text-sm {{ $user->is_active ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
                @if(!$user->banned_at)
                <form action="{{ route('admin.customers.ban', $user) }}" method="POST" onsubmit="return prompt('Alasan ban:') != null">
                    @csrf
                    <input type="hidden" name="reason" id="banReason">
                    <button class="px-3 py-1 rounded text-sm bg-red-500 text-white" 
                            onclick="document.getElementById('banReason').value = prompt('Alasan ban:'); return document.getElementById('banReason').value != null;">
                        Ban
                    </button>
                </form>
                @else
                <form action="{{ route('admin.customers.unban', $user) }}" method="POST">
                    @csrf
                    <button class="px-3 py-1 rounded text-sm bg-green-500 text-white">Unban</button>
                </form>
                @endif
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm mt-4">
            <div><span class="font-medium">HP:</span> {{ $user->phone }}</div>
            <div><span class="font-medium">Total Booking:</span> {{ $user->customerBookings()->count() }}</div>
            <div><span class="font-medium">Bergabung:</span> {{ $user->created_at->format('d M Y') }}</div>
            @if($user->banned_at)
            <div class="text-red-600"><span class="font-medium">Banned:</span> {{ $user->banned_at->format('d M Y') }} - {{ $user->banned_reason }}</div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-lg font-bold mb-4">Booking Terakhir</h2>
        @if($bookings->isNotEmpty())
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr><th class="px-3 py-2 text-left">Kode</th><th class="px-3 py-2 text-left">Rute</th><th class="px-3 py-2 text-center">Status</th><th class="px-3 py-2 text-right">Total</th></tr></thead>
            <tbody>
                @foreach($bookings as $b)
                <tr class="border-t">
                    <td class="px-3 py-2">{{ $b->booking_code }}</td>
                    <td class="px-3 py-2">{{ $b->originStop->city_name }} → {{ $b->destinationStop->city_name }}</td>
                    <td class="px-3 py-2 text-center">{{ $b->status_label }}</td>
                    <td class="px-3 py-2 text-right">Rp {{ number_format($b->total_price, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-gray-500">Belum ada booking.</p>
        @endif
    </div>
</div>
@endsection