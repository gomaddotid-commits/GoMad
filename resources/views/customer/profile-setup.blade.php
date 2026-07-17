@extends('layouts.customer')

@section('title', 'Lengkapi Profil')
@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white border border-[#E5E5E5] rounded-[12px] shadow-sm p-8">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-[#F5F5F5] rounded-[12px] flex items-center justify-center text-3xl mx-auto mb-4 border border-[#E5E5E5]">🧑</div>
            <h1 class="text-2xl font-bold text-[#111111] mb-2">Lengkapi Profil</h1>
            <p class="text-gray-500 font-light">Isi data diri Anda untuk pengalaman yang lebih baik</p>
        </div>

        @if(session('warning'))
        <div class="bg-[#F5F5F5] border border-yellow-200 rounded-[12px] p-4 mb-4 text-sm text-yellow-800">
            {{ session('warning') }}
        </div>
        @endif

        <form action="{{ route('customer.setup.save') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Nama Lengkap <span class="text-[#C1121F]">*</span></label>
                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" 
                       class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" required>
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Alamat Email</label>
                <input type="email" value="{{ auth()->user()->email }}" disabled
                       class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] bg-transparent text-gray-400 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">
                    Nomor WhatsApp <span class="text-[#C1121F]">*</span>
                </label>
                <input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}" 
                       class="w-full px-0 py-2 border-b-2 border-[#E5E5E5] focus:border-[#C1121F] outline-none bg-transparent text-[#111111] transition" 
                       placeholder="081234567890" required>
            </div>

            <button type="submit" class="w-full btn-gomad-primary mt-2 text-base py-3 rounded-[12px]">
                💾 SIMPAN PROFIL
            </button>
        </form>

        <form action="{{ route('customer.setup.save') }}" method="POST" class="mt-3">
            @csrf
            <input type="hidden" name="name" value="{{ auth()->user()->name }}">
            <input type="hidden" name="skip" value="1">
            <button type="submit" class="w-full border border-[#E5E5E5] text-gray-600 py-3 rounded-[12px] font-medium hover:bg-[#F5F5F5] transition">
                ⏭️ LEWATI (ISI NANTI)
            </button>
        </form>
    </div>
</div>
@endsection