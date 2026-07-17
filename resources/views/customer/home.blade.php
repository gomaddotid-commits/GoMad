@extends('layouts.customer')

@section('title', 'Home')
@section('content')

<div class="container-magazine py-8">
    
    {{-- Welcome --}}
    <div class="mb-10 text-center">
        <h1 class="text-2xl md:text-3xl font-bold text-[#111827]">Halo, {{ auth()->user()->name }}! 👋</h1>
        <p class="text-gray-500 font-light mt-1">Mau kemana atau butuh kendaraan hari ini?</p>
    </div>

    {{-- CTA CARDS --}}
    <div class="grid md:grid-cols-2 gap-6 max-w-2xl mx-auto">
        
        {{-- Travel Card --}}
        <a href="{{ route('customer.search') }}" 
           class="bg-white border border-[#E5E7EB] rounded-[12px] p-8 text-center shadow-gomad hover:border-[#BA1826] hover:shadow-gomad-lg transition-all group">
            <div class="w-20 h-20 bg-[#BA1826]/5 rounded-[12px] flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform border border-[#E5E7EB]">
                <span class="text-4xl">🚐</span>
            </div>
            <h2 class="text-xl font-bold text-[#111827] mb-2">Travel Door to Door</h2>
            <p class="text-sm text-gray-500 font-light mb-4">Booking tiket travel antar kota. Dijemput di rumah, diantar ke tujuan.</p>
            <div class="flex flex-wrap gap-2 justify-center mb-4">
                <span class="px-2 py-1 bg-[#F9FAFB] text-[10px] font-mono uppercase tracking-wider rounded-full text-gray-600 border border-[#E5E7EB]">Ekonomi</span>
                <span class="px-2 py-1 bg-[#F9FAFB] text-[10px] font-mono uppercase tracking-wider rounded-full text-gray-600 border border-[#E5E7EB]">Premium</span>
                <span class="px-2 py-1 bg-[#F9FAFB] text-[10px] font-mono uppercase tracking-wider rounded-full text-gray-600 border border-[#E5E7EB]">Charter</span>
            </div>
            <span class="inline-flex items-center gap-2 text-[#BA1826] font-semibold text-sm group-hover:gap-3 transition-all">
                Cari Jadwal Travel
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </span>
        </a>

        {{-- Rental Card --}}
        <a href="{{ route('customer.rental.browse') }}" 
           class="bg-white border border-[#E5E7EB] rounded-[12px] p-8 text-center shadow-gomad hover:border-[#BA1826] hover:shadow-gomad-lg transition-all group">
            <div class="w-20 h-20 bg-[#BA1826]/5 rounded-[12px] flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform border border-[#E5E7EB]">
                <span class="text-4xl">🚗</span>
            </div>
            <h2 class="text-xl font-bold text-[#111827] mb-2">Rental Mobil</h2>
            <p class="text-sm text-gray-500 font-light mb-4">Sewa mobil lepas kunci atau dengan supir. Bebas eksplorasi!</p>
            <div class="flex flex-wrap gap-2 justify-center mb-4">
                <span class="px-2 py-1 bg-blue-50 text-[10px] font-mono uppercase tracking-wider rounded-full text-blue-700 border border-blue-200">Lepas Kunci</span>
                <span class="px-2 py-1 bg-green-50 text-[10px] font-mono uppercase tracking-wider rounded-full text-green-700 border border-green-200">+Supir</span>
            </div>
            <span class="inline-flex items-center gap-2 text-[#BA1826] font-semibold text-sm group-hover:gap-3 transition-all">
                Cari Mobil Rental
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </span>
        </a>

    </div>

    {{-- Quick Links --}}
    <div class="mt-10 max-w-2xl mx-auto">
        <p class="text-xs font-mono uppercase tracking-wider text-gray-400 text-center mb-4">Akses Cepat</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="{{ route('customer.search') }}" class="bg-white border border-[#E5E7EB] rounded-[12px] p-3 text-center hover:border-[#BA1826] transition-colors">
                <span class="text-lg block mb-1">🔍</span>
                <span class="text-xs font-medium text-[#111827]">Cari Travel</span>
            </a>
            <a href="{{ route('customer.bookings') }}" class="bg-white border border-[#E5E7EB] rounded-[12px] p-3 text-center hover:border-[#BA1826] transition-colors">
                <span class="text-lg block mb-1">🎫</span>
                <span class="text-xs font-medium text-[#111827]">Booking Saya</span>
            </a>
            <a href="{{ route('customer.rental.browse') }}" class="bg-white border border-[#E5E7EB] rounded-[12px] p-3 text-center hover:border-[#BA1826] transition-colors">
                <span class="text-lg block mb-1">🚗</span>
                <span class="text-xs font-medium text-[#111827]">Cari Rental</span>
            </a>
            <a href="{{ route('customer.profile') }}" class="bg-white border border-[#E5E7EB] rounded-[12px] p-3 text-center hover:border-[#BA1826] transition-colors">
                <span class="text-lg block mb-1">👤</span>
                <span class="text-xs font-medium text-[#111827]">Profil</span>
            </a>
        </div>
    </div>

</div>
@endsection