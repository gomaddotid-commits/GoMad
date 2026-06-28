@extends('layouts.admin')

@section('title', 'Dashboard')
@section('content')
@php
    $totalCustomers = \App\Models\User::where('role', 'customer')->count();
    $totalAgencies = \App\Models\Agency::count();
    $verifiedAgencies = \App\Models\Agency::where('is_verified', true)->count();
    $pendingAgencies = \App\Models\Agency::where('is_verified', false)->whereHas('verifications', fn($q) => $q->where('status', 'pending'))->count();
    $totalDrivers = \App\Models\User::where('role', 'driver')->count();
    $totalWarungs = \App\Models\PaymentAgent::where('is_verified', true)->count();
    $pendingWarungs = \App\Models\PaymentAgent::where('is_verified', false)->whereNotNull('agent_name')->count();
    $totalBookings = \App\Models\Booking::count();
    $monthRevenue = \App\Models\Booking::where('status', 'completed')->whereMonth('completed_at', now()->month)->sum('total_price');
    $pendingWithdrawals = \App\Models\Withdrawal::where('status', 'pending')->count();

    // Data untuk chart booking harian (7 hari terakhir)
    $dailyBookings = [];
    $dailyLabels = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = now()->subDays($i);
        $dailyLabels[] = $date->translatedFormat('d M');
        $dailyBookings[] = \App\Models\Booking::whereDate('created_at', $date)->count();
    }

    // Data untuk chart distribusi user
    $userDistribution = [$totalCustomers, $totalAgencies, $totalDrivers, $totalWarungs];
@endphp

<div>
    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Customer</p>
            <p class="text-2xl font-bold text-secondary mt-1">{{ $totalCustomers }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Agency</p>
            <p class="text-2xl font-bold text-secondary mt-1">{{ $totalAgencies }}</p>
            <p class="text-xs text-green-600 mt-1">{{ $verifiedAgencies }} verified</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Driver</p>
            <p class="text-2xl font-bold text-secondary mt-1">{{ $totalDrivers }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Warung</p>
            <p class="text-2xl font-bold text-secondary mt-1">{{ $totalWarungs }}</p>
            <p class="text-xs text-yellow-600 mt-1">{{ $pendingWarungs }} pending</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Revenue/Bulan</p>
            <p class="text-lg font-bold text-primary-600 mt-1">Rp {{ number_format($monthRevenue, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        {{-- Chart Booking Harian --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-secondary mb-4">📈 Booking 7 Hari Terakhir</h3>
            <div class="relative" style="height: 280px;">
                <canvas id="dailyBookingChart"></canvas>
            </div>
        </div>

        {{-- Chart Distribusi User --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-secondary mb-4">👥 Distribusi Pengguna</h3>
            <div class="relative flex justify-center" style="height: 280px;">
                <canvas id="userDistributionChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Bottom Cards --}}
    <div class="grid md:grid-cols-3 gap-6">
        {{-- Pending Verifikasi Agency --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-secondary mb-4">Verifikasi Agency</h3>
            @if($pendingAgencies > 0)
            <p class="text-sm text-yellow-600 mb-3">{{ $pendingAgencies }} menunggu verifikasi</p>
            <a href="{{ route('admin.agencies.index') }}" class="text-primary-600 text-sm font-medium hover:underline">Lihat Semua →</a>
            @else
            <p class="text-sm text-gray-500">Tidak ada yang pending.</p>
            @endif
        </div>

        {{-- Pending Withdrawal --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-secondary mb-4">Withdrawal Pending</h3>
            @if($pendingWithdrawals > 0)
            <p class="text-sm text-yellow-600 mb-3">{{ $pendingWithdrawals }} menunggu approval</p>
            <a href="{{ route('admin.withdrawals.index') }}" class="text-primary-600 text-sm font-medium hover:underline">Lihat Semua →</a>
            @else
            <p class="text-sm text-gray-500">Tidak ada yang pending.</p>
            @endif
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-secondary mb-4">Aksi Cepat</h3>
            <div class="space-y-2">
                <a href="{{ route('admin.routes.create') }}" class="block text-sm text-primary-600 font-medium hover:underline">+ Tambah Rute</a>
                <a href="{{ route('admin.promos.create') }}" class="block text-sm text-primary-600 font-medium hover:underline">+ Buat Promo</a>
                <a href="{{ route('admin.payment-agents.index') }}" class="block text-sm text-primary-600 font-medium hover:underline">Verifikasi Warung</a>
                <a href="{{ route('admin.agencies.index') }}" class="block text-sm text-primary-600 font-medium hover:underline">Verifikasi Agency</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart 1: Booking Harian (Line)
    const dailyCtx = document.getElementById('dailyBookingChart');
    if (dailyCtx) {
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: @json($dailyLabels),
                datasets: [{
                    label: 'Booking',
                    data: @json($dailyBookings),
                    borderColor: '#DC2626',
                    backgroundColor: 'rgba(220, 38, 38, 0.05)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#DC2626',
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: { family: 'League Spartan', size: 11 },
                            color: '#6B7280',
                        },
                        grid: { color: '#F3F4F6' },
                    },
                    x: {
                        ticks: {
                            font: { family: 'League Spartan', size: 11 },
                            color: '#6B7280',
                        },
                        grid: { display: false },
                    },
                },
            },
        });
    }

    // Chart 2: Distribusi User (Doughnut)
    const distCtx = document.getElementById('userDistributionChart');
    if (distCtx) {
        new Chart(distCtx, {
            type: 'doughnut',
            data: {
                labels: ['Customer', 'Agency', 'Driver', 'Warung'],
                datasets: [{
                    data: @json($userDistribution),
                    backgroundColor: ['#DC2626', '#F59E0B', '#3B82F6', '#10B981'],
                    borderColor: '#FFFFFF',
                    borderWidth: 3,
                    hoverBorderWidth: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyleWidth: 10,
                            font: { family: 'League Spartan', size: 12 },
                            color: '#4B5563',
                        },
                    },
                },
            },
        });
    }
});
</script>
@endpush