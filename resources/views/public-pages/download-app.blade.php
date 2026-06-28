@extends('layouts.public')

@section('title', 'Download App')
@section('meta_description', 'Download aplikasi GoMad di Play Store dan App Store. Booking travel Madura jadi lebih mudah.')
@section('og_image', asset('images/og-download.jpg'))

@section('content')
<div class="section mt-10 mb-20">
    <div class="container-custom">
        <div class="max-w-2xl mx-auto text-center">
            <div class="mb-8">
                <div class="w-24 h-24 bg-primary-50 rounded-3xl flex items-center justify-center mx-auto mb-6">
                    <img src="{{ asset('images/logo.svg') }}" alt="GoMad" class="h-12">
                </div>
                <h1 class="text-3xl md:text-4xl font-bold text-secondary mb-4">Download Aplikasi GoMad</h1>
                <p class="text-gray-600 text-lg">Dapatkan pengalaman terbaik dengan aplikasi GoMad.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="#" class="bg-black text-white px-8 py-4 rounded-2xl inline-flex items-center gap-4 hover:bg-gray-800 transition justify-center"><span class="text-3xl">▶</span><div class="text-left"><div class="text-xs opacity-80">GET IT ON</div><div class="text-lg font-bold">Google Play</div></div></a>
                <a href="#" class="bg-black text-white px-8 py-4 rounded-2xl inline-flex items-center gap-4 hover:bg-gray-800 transition justify-center"><span class="text-3xl">🍎</span><div class="text-left"><div class="text-xs opacity-80">DOWNLOAD ON</div><div class="text-lg font-bold">App Store</div></div></a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <div class="card p-6"><div class="w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center text-xl mx-auto mb-3">🔍</div><h4 class="font-semibold text-secondary text-sm">Cari Jadwal</h4></div>
                <div class="card p-6"><div class="w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center text-xl mx-auto mb-3">📅</div><h4 class="font-semibold text-secondary text-sm">Booking Mudah</h4></div>
                <div class="card p-6"><div class="w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center text-xl mx-auto mb-3">💳</div><h4 class="font-semibold text-secondary text-sm">Bayar Online</h4></div>
                <div class="card p-6"><div class="w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center text-xl mx-auto mb-3">🎫</div><h4 class="font-semibold text-secondary text-sm">E-Ticket</h4></div>
            </div>
        </div>
    </div>
</div>
@endsection