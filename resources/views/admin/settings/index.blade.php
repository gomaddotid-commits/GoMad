@extends('layouts.admin')

@section('title', 'Pengaturan')
@section('content')

@php
    $settings = \App\Models\PlatformSetting::getAllSettings();
@endphp

<div>
    <h1 class="text-lg font-bold text-secondary mb-6">Pengaturan Platform</h1>

    <form action="{{ route('admin.settings.update') }}" method="POST" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        @csrf
        @method('PUT')

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Komisi Platform (%)</label>
                <input type="number" name="commission_rate" value="{{ $settings['commission_rate'] ?? '5' }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50"
                       step="0.01" min="0" max="100">
                <p class="text-xs text-gray-500 mt-1">Persentase komisi dari setiap transaksi</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Komisi Warung (%)</label>
                <input type="number" name="warung_commission_rate" value="{{ $settings['warung_commission_rate'] ?? '2' }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50"
                       step="0.01" min="0" max="100">
                <p class="text-xs text-gray-500 mt-1">Persentase komisi untuk warung pembayaran</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Timeout Pembayaran (menit)</label>
                <input type="number" name="payment_timeout" value="{{ $settings['payment_timeout'] ?? '30' }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50"
                       min="1">
                <p class="text-xs text-gray-500 mt-1">Batas waktu pembayaran sebelum booking expired</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Minimal Hari Jadwal</label>
                <input type="number" name="schedule_min_days" value="{{ $settings['schedule_min_days'] ?? '30' }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50"
                       min="1">
                <p class="text-xs text-gray-500 mt-1">Jumlah hari jadwal ditampilkan ke depan</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Minimal Withdrawal (Rp)</label>
                <input type="number" name="minimal_withdrawal" value="{{ $settings['minimal_withdrawal'] ?? '100000' }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50"
                       min="0">
                <p class="text-xs text-gray-500 mt-1">Saldo minimal yang bisa ditarik</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Biaya Admin Withdrawal (Rp)</label>
                <input type="number" name="withdrawal_admin_fee" value="{{ $settings['withdrawal_admin_fee'] ?? '5000' }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50"
                       min="0">
                <p class="text-xs text-gray-500 mt-1">Biaya potongan setiap withdrawal</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Auto-Approve Limit (Rp)</label>
                <input type="number" name="auto_approve_limit" value="{{ $settings['auto_approve_limit'] ?? '5000000' }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50"
                       min="0">
                <p class="text-xs text-gray-500 mt-1">Batas nominal withdrawal yang auto-approve</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-secondary mb-1">Nomor Support</label>
                <input type="text" name="support_phone" value="{{ $settings['support_phone'] ?? '081234567890' }}"
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-600 bg-gray-50"
                       placeholder="0812-3456-7890">
                <p class="text-xs text-gray-500 mt-1">Nomor yang ditampilkan di footer & kontak</p>
            </div>
        </div>

        <div class="mt-6 pt-6 border-t border-gray-100">
            <button type="submit" class="bg-primary-600 text-white px-8 py-3 rounded-xl font-semibold hover:bg-primary-700 transition active:scale-95">
                💾 SIMPAN PENGATURAN
            </button>
        </div>
    </form>
</div>
@endsection