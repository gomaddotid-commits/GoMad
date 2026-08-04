@extends('layouts.admin')

@section('title', 'Detail Customer')
@section('content')
<div class="max-w-4xl mx-auto">
    <a href="{{ route('admin.customers.index') }}" class="text-[#C1121F] text-sm mb-4 inline-block hover:underline">← Kembali</a>

    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 mb-6 shadow-sm">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-[#111111]">{{ $user->name }}</h1>
                <p class="text-gray-500 font-light">{{ $user->email }}</p>
            </div>
            <div class="flex gap-2">
                <form action="{{ route('admin.customers.toggle-active', $user) }}" method="POST">
                    @csrf @method('PUT')
                    <button class="px-3 py-1 rounded-[12px] text-sm border border-[#E5E5E5] font-medium {{ $user->is_active ? 'text-red-600 hover:bg-red-50' : 'text-green-600 hover:bg-green-50' }}">
                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
                @if(!$user->banned_at)
                <button type="button" onclick="document.getElementById('banModal').classList.remove('hidden')"
                        class="px-3 py-1 rounded-[12px] text-sm bg-[#C1121F] text-white hover:bg-[#8A0F18]">
                    Ban
                </button>
                @else
                <form action="{{ route('admin.customers.unban', $user) }}" method="POST">
                    @csrf
                    <button class="px-3 py-1 rounded-[12px] text-sm bg-green-600 text-white hover:bg-green-700">Unban</button>
                </form>
                @endif
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm mt-4">
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3"><span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">HP</span><span class="font-medium text-[#111111]">{{ $user->phone }}</span></div>
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3"><span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Total Booking</span><span class="font-medium text-[#111111]">{{ $user->customerBookings()->count() }}</span></div>
            <div class="bg-[#F5F5F5] border border-[#E5E5E5] rounded-[12px] p-3"><span class="text-[10px] font-mono uppercase tracking-wider text-gray-400">Bergabung</span><span class="font-medium text-[#111111]">{{ $user->created_at->format('d M Y') }}</span></div>
            @if($user->banned_at)
            <div class="bg-red-50 border border-red-200 rounded-[12px] p-3 text-red-700"><span class="text-[10px] font-mono uppercase tracking-wider text-gray-500">Banned</span><span class="font-medium">{{ $user->banned_at->format('d M Y') }} - {{ $user->banned_reason }}</span></div>
            @endif
        </div>
    </div>

    <div class="bg-white border border-[#E5E5E5] rounded-[12px] p-6 shadow-sm">
        <h2 class="font-bold text-lg text-[#111111] mb-4">Booking Terakhir</h2>
        @if($bookings->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#F5F5F5] border-b border-[#E5E5E5]">
                    <tr><th class="px-3 py-2 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Kode</th><th class="px-3 py-2 text-left font-mono uppercase tracking-wider text-xs text-gray-500">Rute</th><th class="px-3 py-2 text-center font-mono uppercase tracking-wider text-xs text-gray-500">Status</th><th class="px-3 py-2 text-right font-mono uppercase tracking-wider text-xs text-gray-500">Total</th></tr>
                </thead>
                <tbody>
                    @foreach($bookings as $b)
                    <tr class="border-t border-[#E5E5E5]">
                        <td class="px-3 py-2 font-mono text-[#111111]">{{ $b->booking_code }}</td>
                        <td class="px-3 py-2 text-gray-600 font-light">{{ $b->originStop->city_name }} → {{ $b->destinationStop->city_name }}</td>
                        <td class="px-3 py-2 text-center"><span class="px-2 py-0.5 rounded-full text-[10px] font-mono uppercase tracking-wider border
                            @if($b->status == 'paid') bg-green-50 text-green-700 border-green-200
                            @elseif($b->status == 'pending') bg-yellow-50 text-yellow-700 border-yellow-200
                            @elseif($b->status == 'cancelled') bg-red-50 text-red-700 border-red-200
                            @else bg-blue-50 text-blue-700 border-blue-200 @endif">{{ $b->status_label }}</span></td>
                        <td class="px-3 py-2 text-right font-mono text-[#C1121F]">Rp {{ number_format($b->total_price, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-gray-500 font-light">Belum ada booking.</p>
        @endif
    </div>
</div>

{{-- Modal Ban (menggantikan prompt() browser) --}}
<div id="banModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-[16px] p-6 max-w-md w-full shadow-xl">
        <h3 class="text-lg font-bold text-[#111111] mb-2">🚫 Ban Customer</h3>
        <p class="text-sm text-gray-500 mb-4 font-light">Apakah Anda yakin ingin mem-banned <strong>{{ $user->name }}</strong>? Customer tidak bisa login setelah di-ban.</p>
        <form action="{{ route('admin.customers.ban', $user) }}" method="POST">
            @csrf
            <label class="text-xs font-semibold text-[#111111] block mb-1">Alasan Ban *</label>
            <textarea name="reason" required rows="3" class="w-full border border-[#E5E5E5] rounded-[12px] p-3 text-sm mb-4 focus:ring-[#C1121F] focus:border-[#C1121F]" placeholder="Tuliskan alasan ban..."></textarea>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('banModal').classList.add('hidden')"
                        class="px-4 py-2 rounded-[12px] text-sm border border-[#E5E5E5] font-medium hover:bg-[#F5F5F5]">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-[12px] text-sm bg-[#C1121F] text-white font-semibold hover:bg-[#8A0F18]">Ya, Ban</button>
            </div>
        </form>
    </div>
</div>
@endsection