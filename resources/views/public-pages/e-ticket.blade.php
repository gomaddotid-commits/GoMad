@extends('layouts.public')

@section('title', 'Cek E-Ticket')
@section('meta_description', 'Cek dan download E-Ticket booking GoMad Anda. Masukkan kode booking untuk melihat tiket.')
@section('og_image', asset('images/og-eticket.jpg'))

@section('content')
<div class="section mt-10 mb-20">
    <div class="container-custom">
        @if(!isset($booking))
        <div class="max-w-lg mx-auto">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-primary-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                </div>
                <h1 class="text-2xl font-bold text-secondary mb-2">Cek E-Ticket</h1>
                <p class="text-gray-600">Masukkan kode booking Anda</p>
            </div>
            <div class="card p-6 md:p-8">
                <form action="{{ route('eticket.check') }}" method="POST" class="mb-6">
                    @csrf
                    <label class="block text-sm font-medium text-secondary mb-2">Kode Booking</label>
                    <div class="flex gap-3">
                        <input type="text" name="booking_code" class="flex-1 px-4 py-3 border border-gray-200 rounded-xl font-mono uppercase tracking-wider focus:ring-2 focus:ring-primary-600 bg-gray-50" placeholder="GM-YYYYMMDD-XXXX" required>
                        <button type="submit" class="btn-primary">Cek</button>
                    </div>
                </form>
                <div class="border-t border-gray-100 pt-6">
                    <p class="text-sm text-gray-600 mb-4">Atau kirim ke email</p>
                    <form action="{{ route('eticket.send') }}" method="POST" class="space-y-3">
                        @csrf
                        <input type="text" name="booking_code" class="w-full px-4 py-3 border border-gray-200 rounded-xl font-mono uppercase bg-gray-50" placeholder="Kode Booking" required>
                        <input type="email" name="email" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50" placeholder="nama@email.com" required>
                        <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 transition">Kirim ke Email</button>
                    </form>
                </div>
                <div class="mt-6 bg-blue-50 rounded-xl p-4 text-sm text-blue-800">
                    <p class="font-medium mb-1">Informasi</p>
                    <ul class="list-disc list-inside space-y-1 text-blue-700"><li>Kode booking ada di email konfirmasi</li><li>Format: <strong>GM-YYYYMMDD-XXXX</strong></li></ul>
                </div>
            </div>
        </div>
        @else
        <div class="max-w-2xl mx-auto">
            <a href="{{ route('eticket.public') }}" class="text-primary-600 text-sm mb-4 inline-block">← Cek Lagi</a>
            <div class="card border-2 border-primary-600 p-6 md:p-8">
                <div class="text-center border-b-2 border-dashed border-gray-200 pb-4 mb-4">
                    <img src="{{ asset('images/logo.svg') }}" alt="GoMad" class="h-8 mx-auto mb-2">
                    <p class="text-sm text-gray-500">{{ $booking->booking_code }}</p>
                </div>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Rute</span><span class="font-semibold">{{ $booking->originStop->city_name ?? '?' }} → {{ $booking->destinationStop->city_name ?? '?' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Tanggal</span><span class="font-semibold">{{ $booking->schedule->departure_date->format('d M Y') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Jam</span><span class="font-semibold">{{ $booking->schedule->departure_time }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Agency</span><span class="font-semibold">{{ $booking->schedule->agency->agency_name ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Kendaraan</span><span class="font-semibold">{{ $booking->schedule->vehicle->plate_number ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Total</span><span class="font-bold text-primary-600">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span></div>
                </div>
                <div class="border-t border-dashed border-gray-200 pt-4 mt-4">
                    <h4 class="font-semibold mb-2">Penumpang ({{ $booking->total_passengers }})</h4>
                    @foreach($booking->passengers as $p)<div class="flex justify-between text-sm py-1"><span>{{ $p->passenger_name }}</span><span class="text-gray-500">Seat {{ $p->seat_number }}</span></div>@endforeach
                </div>
                <div class="border-t border-dashed border-gray-200 pt-4 mt-4"><div class="grid grid-cols-2 gap-4 text-sm"><div><span class="text-gray-500 text-xs">Jemput</span><p class="font-medium">{{ $booking->pickup_address }}</p></div><div><span class="text-gray-500 text-xs">Tujuan</span><p class="font-medium">{{ $booking->destination_address }}</p></div></div></div>
                <div class="border-t-2 border-dashed border-gray-200 pt-4 mt-4 text-center"><p class="text-xs text-gray-400">E-Ticket resmi GoMad</p><p class="text-xs text-gray-400">{{ now()->format('d M Y H:i') }}</p></div>
            </div>
            <div class="flex gap-4 mt-6 justify-center"><button onclick="window.print()" class="btn-primary">Cetak</button><a href="{{ route('eticket.public') }}" class="btn-outline">Cek Lagi</a></div>
            @guest
            <div class="card p-6 mt-6 max-w-lg mx-auto"><h3 class="font-bold text-secondary mb-3">Kirim ke Email</h3>
                <form action="{{ route('eticket.send') }}" method="POST" class="flex gap-3">@csrf
                    <input type="hidden" name="booking_code" value="{{ $booking->booking_code }}">
                    <input type="email" name="email" class="flex-1 px-4 py-3 border border-gray-200 rounded-xl bg-gray-50" placeholder="nama@email.com" required>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-700">Kirim</button>
                </form>
            </div>
            @endguest
        </div>
        @endif
    </div>
</div>
@endsection