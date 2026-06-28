@extends('layouts.admin')

@section('title', 'Driver')
@section('content')
<!-- File: resources/views/admin/drivers/index.blade.php -->
<!-- Deskripsi: Halaman daftar driver admin -->

<div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Daftar Driver</h1>

    <form action="{{ route('admin.drivers.index') }}" method="GET" class="mb-6">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari driver..." 
               class="px-4 py-2 border rounded-lg w-full max-w-md">
    </form>

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-left">HP</th>
                    <th class="px-4 py-3 text-left">Agency</th>
                    <th class="px-4 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($drivers as $driver)
                <tr class="border-t">
                    <td class="px-4 py-3 font-medium">{{ $driver->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $driver->email }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $driver->phone }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $driver->driverAgency->agency_name ?? '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded text-xs {{ $driver->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $driver->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $drivers->links() }}</div>
</div>
@endsection