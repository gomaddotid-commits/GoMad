@extends('layouts.driver')

@section('title', 'Profil')
@section('content')
@php $user = auth()->user(); @endphp

<div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-bold text-secondary mb-6">Profil Driver</h1>

    {{-- Info Agency --}}
    @if($user->agency)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
        <h3 class="font-bold text-secondary mb-2">Agency</h3>
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full bg-primary-50 flex items-center justify-center overflow-hidden">
                @if($user->agency->logo)
                <img src="{{  $user->agency->logo }}" alt="" class="w-full h-full object-cover">
                @else
                <span class="text-xl">🏢</span>
                @endif
            </div>
            <div>
                <p class="font-semibold">{{ $user->agency->agency_name }}</p>
                <p class="text-xs text-gray-500">{{ $user->agency->contact_person ?? '-' }} • {{ $user->agency->contact_alternate ?? '-' }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Form Profil --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-bold text-lg text-secondary mb-4">Informasi Akun</h3>
        <form action="{{ route('driver.profile.update') }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Nama</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Email</label>
                <input type="email" value="{{ $user->email }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-100 text-gray-500" disabled>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Nomor HP</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50">
            </div>
            <button type="submit" class="btn-primary w-full">Simpan</button>
        </form>
    </div>
</div>
@endsection