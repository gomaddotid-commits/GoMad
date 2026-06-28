@extends('layouts.agency')

@section('title', 'Driver')
@section('content')
<div>
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <h1 class="text-2xl font-bold text-secondary">Driver</h1>
        <a href="{{ route('agency.drivers.create') }}" class="btn-primary text-sm inline-flex items-center gap-2 self-start">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Driver
        </a>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($drivers as $driver)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
            <div class="bg-gradient-to-b from-primary-50 to-white p-6 text-center">
                <div class="w-24 h-24 rounded-full mx-auto overflow-hidden border-4 border-white shadow-lg">
                    @if($driver->avatar_url)
                    <img src="{{ asset('storage/' . $driver->avatar_url) }}" alt="{{ $driver->name }}" class="w-full h-full object-cover">
                    @else
                    <div class="w-full h-full bg-gray-200 flex items-center justify-center text-3xl">👨‍✈️</div>
                    @endif
                </div>
                <h3 class="font-bold text-lg mt-3">{{ $driver->name }}</h3>
            </div>
            <div class="p-5">
                <div class="space-y-2 text-sm mb-4">
                    <div class="flex items-center gap-2"><span>📧</span><span class="text-gray-600">{{ $driver->email }}</span></div>
                    <div class="flex items-center gap-2"><span>📞</span><span class="text-gray-600">{{ $driver->phone ?? '-' }}</span></div>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $driver->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $driver->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('agency.drivers.edit', $driver) }}" class="flex-1 text-center bg-blue-50 text-blue-600 py-2 rounded-lg text-sm font-medium hover:bg-blue-100 transition">Edit</a>
                    <form action="{{ route('agency.drivers.destroy', $driver) }}" method="POST" class="flex-1" onsubmit="return confirm('Hapus?')">
                        @csrf @method('DELETE')
                        <button class="w-full bg-red-50 text-red-600 py-2 rounded-lg text-sm font-medium hover:bg-red-100 transition">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center text-gray-500">
            <p class="text-lg">Belum ada driver.</p>
            <a href="{{ route('agency.drivers.create') }}" class="text-primary-600 hover:underline mt-2 inline-block">+ Tambah Driver</a>
        </div>
        @endforelse
    </div>
</div>
@endsection