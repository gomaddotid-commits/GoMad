@extends('layouts.public')

@section('title', 'Cek E-Ticket')
@section('meta_description', 'Cek dan download E-Ticket booking GoMad Anda. Masukkan kode booking untuk melihat tiket.')
@section('og_image', asset('images/og-eticket.jpg'))

@section('content')
<div class="section mt-10 mb-20">
    <div class="container-magazine">
        @if(!isset($booking))
        <div class="max-w-lg mx-auto">
            <div class="text-center mb-8 mt-[-4rem] md:mt-[-6rem]">
                <h1 class="text-3xl font-bold text-[#111827] mb-2">Cek E-Ticket</h1>
                <p class="text-gray-500 font-light">Masukkan kode booking Anda</p>
            </div>
            
            <div class="card-gomad p-6 md:p-8 border-[#E5E7EB]">
                <form action="{{ route('eticket.check') }}" method="POST" class="mb-6">
                    @csrf
                    <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Kode Booking</label>
                    <div class="flex gap-3 items-end">
                        <input type="text" name="booking_code" class="flex-1 px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent font-mono text-lg uppercase tracking-wider transition-colors" placeholder="GM-YYYYMMDD-XXXX" required>
                        <button type="submit" class="btn-gomad-primary py-2.5 px-6 text-sm flex-shrink-0">Cek</button>
                    </div>
                </form>
                
                <div class="border-t border-[#E5E7EB] pt-6">
                    <p class="text-xs font-mono uppercase tracking-wider text-gray-500 mb-4">Atau kirim ke email</p>
                    <form action="{{ route('eticket.send') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <input type="text" name="booking_code" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent font-mono uppercase" placeholder="Kode Booking" required>
                        </div>
                        <div>
                            <input type="email" name="email" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent" placeholder="nama@email.com" required>
                        </div>
                        <button type="submit" class="w-full bg-[#BA1826] text-white py-3 rounded-[10px] font-medium hover:bg-[#8A0F18] transition">Kirim ke Email</button>
                    </form>
                </div>
                
                <div class="mt-6 bg-[#F9FAFB] rounded-[10px] p-4 text-sm text-[#111827] border border-[#E5E7EB]">
                    <p class="font-mono uppercase tracking-wider font-medium mb-1">Informasi</p>
                    <ul class="list-disc list-inside space-y-1 text-gray-500 font-light text-xs"><li>Kode booking ada di email konfirmasi</li><li>Format: <strong class="font-mono text-[#BA1826]">GM-YYYYMMDD-XXXX</strong></li></ul>
                </div>
            </div>
        </div>
        @else
        <div class="max-w-2xl mx-auto">
            <a href="{{ route('eticket.public') }}" class="text-[#BA1826] text-sm mb-4 inline-block hover:underline">← Cek Lagi</a>
            <div class="card-gomad border-2 border-[#BA1826] p-6 md:p-8 bg-white">
                <div class="text-center border-b-2 border-dashed border-[#E5E7EB] pb-4 mb-4">
                    <div class="flex items-center justify-center gap-2 mb-2">
                        <span class="text-2xl font-bold tracking-tighter">GO</span>
                        <span class="text-[#BA1826] text-2xl font-bold tracking-tighter">MAD</span>
                    </div>
                    <p class="text-xs font-mono text-gray-400 tracking-wider">{{ $booking->booking_code }}</p>
                </div>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between border-b border-[#F9FAFB] pb-1"><span class="text-gray-500 font-mono uppercase tracking-wider text-xs">Rute</span><span class="font-semibold text-[#111827]">{{ $booking->originStop->city_name ?? '?' }} → {{ $booking->destinationStop->city_name ?? '?' }}</span></div>
                    <div class="flex justify-between border-b border-[#F9FAFB] pb-1"><span class="text-gray-500 font-mono uppercase tracking-wider text-xs">Tanggal</span><span class="font-semibold text-[#111827]">{{ $booking->schedule->departure_date->format('d M Y') }}</span></div>
                    <div class="flex justify-between border-b border-[#F9FAFB] pb-1"><span class="text-gray-500 font-mono uppercase tracking-wider text-xs">Jam</span><span class="font-semibold text-[#111827]">{{ $booking->schedule->departure_time }}</span></div>
                    <div class="flex justify-between border-b border-[#F9FAFB] pb-1"><span class="text-gray-500 font-mono uppercase tracking-wider text-xs">Agency</span><span class="font-semibold text-[#111827]">{{ $booking->schedule->agency->agency_name ?? '-' }}</span></div>
                    <div class="flex justify-between border-b border-[#F9FAFB] pb-1"><span class="text-gray-500 font-mono uppercase tracking-wider text-xs">Kendaraan</span><span class="font-semibold text-[#111827]">{{ $booking->schedule->vehicle->plate_number ?? '-' }}</span></div>
                    <div class="flex justify-between pt-2"><span class="text-gray-500 font-mono uppercase tracking-wider text-xs">Total</span><span class="font-bold text-[#BA1826] font-mono text-lg">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span></div>
                </div>
                
                <div class="border-t border-dashed border-[#E5E7EB] pt-4 mt-4">
                    <h4 class="font-mono uppercase tracking-wider text-xs font-semibold mb-2">Penumpang ({{ $booking->total_passengers }})</h4>
                    @foreach($booking->passengers as $p)<div class="flex justify-between text-sm py-1 border-b border-[#F9FAFB] last:border-0"><span class="text-[#111827]">{{ $p->passenger_name }}</span><span class="text-gray-400 font-mono text-xs">Seat {{ $p->seat_number }}</span></div>@endforeach
                </div>
                
                <div class="border-t border-dashed border-[#E5E7EB] pt-4 mt-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div><span class="text-gray-400 font-mono uppercase tracking-wider text-[10px]">Jemput</span><p class="font-medium text-[#111827] mt-1">{{ $booking->pickup_address }}</p></div>
                        <div><span class="text-gray-400 font-mono uppercase tracking-wider text-[10px]">Tujuan</span><p class="font-medium text-[#111827] mt-1">{{ $booking->destination_address }}</p></div>
                    </div>
                </div>
                
                <div class="border-t-2 border-dashed border-[#E5E7EB] pt-4 mt-4 text-center">
                    <p class="text-[10px] font-mono uppercase tracking-widest text-gray-400">E-Ticket resmi GoMad</p>
                    <p class="text-[10px] font-mono text-gray-400">{{ now()->format('d M Y H:i') }}</p>
                </div>
            </div>
            
            <div class="flex gap-4 mt-6 justify-center">
                <button onclick="window.print()" class="btn-gomad-primary">Cetak</button>
                <a href="{{ route('eticket.public') }}" class="btn-gomad-outline border-[#BA1826] text-[#BA1826] hover:bg-[#BA1826] hover:text-white">Cek Lagi</a>
            </div>
            
            @guest
            <div class="card-gomad p-6 mt-6 max-w-lg mx-auto border-[#E5E7EB]">
                <h3 class="font-mono uppercase tracking-wider text-sm font-bold mb-4">Kirim ke Email</h3>
                <form action="{{ route('eticket.send') }}" method="POST" class="flex gap-3 items-end">@csrf
                    <input type="hidden" name="booking_code" value="{{ $booking->booking_code }}">
                    <div class="flex-1">
                        <input type="email" name="email" class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent" placeholder="nama@email.com" required>
                    </div>
                    <button type="submit" class="bg-[#BA1826] text-white px-6 py-2.5 rounded-[10px] font-medium hover:bg-[#8A0F18] flex-shrink-0">Kirim</button>
                </form>
            </div>
            @endguest
        </div>
        @endif
    </div>
</div>
@endsection