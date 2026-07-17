@extends('layouts.customer')

@section('title', 'Lengkapi Profil')
@section('content')
<div class="max-w-md mx-auto px-4 py-12">
    <div class="bg-white border border-[#E5E7EB] rounded-[12px] shadow-gomad p-8">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-[#F9FAFB] rounded-[10px] flex items-center justify-center text-3xl mx-auto mb-4 border border-[#E5E7EB]">🧑</div>
            <h1 class="text-2xl font-bold text-[#111827] mb-2">Lengkapi Profil</h1>
            <p class="text-gray-500 font-light">Isi data diri Anda untuk pengalaman yang lebih baik</p>
        </div>

        @if(session('warning'))
        <div class="bg-[#F9FAFB] border border-yellow-200 rounded-[12px] p-4 mb-4 text-sm text-yellow-800">
            {{ session('warning') }}
        </div>
        @endif

        <form action="{{ route('customer.setup.save') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Nama Lengkap <span class="text-[#BA1826]">*</span></label>
                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" 
                       class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition" required>
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">Alamat Email</label>
                <input type="email" value="{{ auth()->user()->email }}" disabled
                       class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] bg-transparent text-gray-400 cursor-not-allowed">
            </div>
            <div>
                <label class="block text-xs font-mono uppercase tracking-wider text-gray-500 mb-1">
                    Nomor WhatsApp <span class="text-[#BA1826]">*</span>
                </label>
                <input type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}" 
                       class="w-full px-0 py-2 border-b-2 border-[#E5E7EB] focus:border-[#BA1826] outline-none bg-transparent text-[#111827] transition" 
                       placeholder="081234567890" required>
            </div>

            <button type="submit" class="w-full btn-gomad-primary mt-2 text-base py-3 rounded-[10px]">
                💾 SIMPAN PROFIL
            </button>
        </form>

        <form action="{{ route('customer.setup.save') }}" method="POST" class="mt-3">
            @csrf
            <input type="hidden" name="name" value="{{ auth()->user()->name }}">
            <input type="hidden" name="skip" value="1">
            <button type="submit" class="w-full border border-[#E5E7EB] text-gray-600 py-3 rounded-[10px] font-medium hover:bg-[#F9FAFB] transition">
                ⏭️ LEWATI (ISI NANTI)
            </button>
        </form>
    </div>
</div>
@endsection