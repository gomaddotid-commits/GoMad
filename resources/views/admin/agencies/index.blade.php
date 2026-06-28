@extends('layouts.admin')

@section('title', 'Agency')
@section('content')

@php
    $agencies = \App\Models\Agency::with('user')->latest()->paginate(15);
@endphp

<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Daftar Agency</h1>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">Nama Agency</th>
                    <th class="px-4 py-3 text-left">Pemilik</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Rating</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($agencies as $agency)
                <tr class="border-t">
                    <td class="px-4 py-3 font-medium">{{ $agency->agency_name }}</td>
                    <td class="px-4 py-3">{{ $agency->user->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded text-xs {{ $agency->is_verified ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $agency->is_verified ? 'Verified' : 'Pending' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">⭐ {{ number_format($agency->rating, 1) }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.agencies.show', $agency) }}" class="text-primary hover:underline text-sm">Detail</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $agencies->links() }}</div>
</div>
@endsection