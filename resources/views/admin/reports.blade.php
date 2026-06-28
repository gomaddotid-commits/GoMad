@extends('layouts.admin')

@section('title', 'Laporan')
@section('content')

@php
    $totalBookings = \App\Models\Booking::count();
    $totalRevenue = \App\Models\Booking::where('status', 'completed')->sum('total_price');
    $totalAgencies = \App\Models\Agency::count();
    $totalCustomers = \App\Models\User::where('role', 'customer')->count();
    $totalWarungs = \App\Models\PaymentAgent::count();

    // Data Revenue Bulanan (6 bulan terakhir)
    $monthlyRevenue = [];
    $monthlyLabels = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = now()->subMonths($i);
        $monthlyLabels[] = $date->translatedFormat('M Y');
        $monthlyRevenue[] = \App\Models\Booking::where('status', 'completed')
            ->whereMonth('completed_at', $date->month)
            ->whereYear('completed_at', $date->year)
            ->sum('total_price');
    }

    // Data Booking per Rute (Top 5) - melalui relasi Schedule
    $topRoutes = \App\Models\Route::withCount(['schedules as bookings_count' => function($q) {
            $q->whereHas('bookings');
        }])
        ->orderByDesc('bookings_count')
        ->limit(5)
        ->get();
    $routeLabels = $topRoutes->pluck('route_name')->toArray();
    $routeCounts = $topRoutes->pluck('bookings_count')->toArray();
@endphp

<div>
    <h1 class="text-lg font-bold text-secondary mb-6">Laporan</h1>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Total Booking</p>
            <p class="text-2xl font-bold text-secondary mt-1">{{ $totalBookings }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Total Revenue</p>
            <p class="text-lg font-bold text-primary-600 mt-1">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Agency</p>
            <p class="text-2xl font-bold text-secondary mt-1">{{ $totalAgencies }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Customer</p>
            <p class="text-2xl font-bold text-secondary mt-1">{{ $totalCustomers }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Warung</p>
            <p class="text-2xl font-bold text-secondary mt-1">{{ $totalWarungs }}</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid md:grid-cols-2 gap-6">
        {{-- Revenue Bulanan --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-secondary mb-4">💰 Revenue 6 Bulan Terakhir</h3>
            <div class="relative" style="height: 320px;">
                <canvas id="monthlyRevenueChart"></canvas>
            </div>
        </div>

        {{-- Booking per Rute --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-secondary mb-4">🎫 Booking per Rute (Top 5)</h3>
            <div class="relative" style="height: 320px;">
                <canvas id="routeBookingChart"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart 1: Revenue Bulanan (Bar)
    const revenueCtx = document.getElementById('monthlyRevenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: @json($monthlyLabels),
                datasets: [{
                    label: 'Revenue',
                    data: @json($monthlyRevenue),
                    backgroundColor: 'rgba(220, 38, 38, 0.8)',
                    borderColor: '#DC2626',
                    borderWidth: 1,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(ctx.raw);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: { family: 'League Spartan', size: 11 },
                            color: '#6B7280',
                            callback: function(val) {
                                if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                                if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
                                return val;
                            }
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

    // Chart 2: Booking per Rute (Horizontal Bar)
    const routeCtx = document.getElementById('routeBookingChart');
    if (routeCtx) {
        new Chart(routeCtx, {
            type: 'bar',
            data: {
                labels: @json($routeLabels),
                datasets: [{
                    label: 'Booking',
                    data: @json($routeCounts),
                    backgroundColor: ['#DC2626', '#F59E0B', '#3B82F6', '#10B981', '#8B5CF6'],
                    borderWidth: 0,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: { family: 'League Spartan', size: 11 },
                            color: '#6B7280',
                        },
                        grid: { color: '#F3F4F6' },
                    },
                    y: {
                        ticks: {
                            font: { family: 'League Spartan', size: 11 },
                            color: '#4B5563',
                        },
                        grid: { display: false },
                    },
                },
            },
        });
    }
});
</script>
@endpush