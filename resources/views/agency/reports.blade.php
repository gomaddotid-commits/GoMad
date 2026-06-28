@extends('layouts.agency')

@section('title', 'Laporan')
@section('content')

@php
    $agency = auth()->user()->agency;
    $totalBookings = \App\Models\Booking::whereHas('schedule', fn($q) => $q->where('agency_id', $agency->id))->count();
    $completedBookings = \App\Models\Booking::whereHas('schedule', fn($q) => $q->where('agency_id', $agency->id))->where('status', 'completed')->count();
    $totalRevenue = \App\Models\Booking::whereHas('schedule', fn($q) => $q->where('agency_id', $agency->id))->where('status', 'completed')->sum('total_price');
    $walletService = app(\App\Services\WalletService::class);
    $balance = $walletService->getBalance($agency);

    // Chart revenue 6 bulan
    $monthlyRevenue = [];
    $monthlyLabels = [];
    for ($i = 5; $i >= 0; $i--) {
        $date = now()->subMonths($i);
        $monthlyLabels[] = $date->translatedFormat('M Y');
        $monthlyRevenue[] = \App\Models\Booking::whereHas('schedule', fn($q) => $q->where('agency_id', $agency->id))
            ->where('status', 'completed')
            ->whereMonth('completed_at', $date->month)
            ->whereYear('completed_at', $date->year)
            ->sum('total_price');
    }

    // Chart booking per rute (top 5)
    $topRoutes = $agency->schedules()
        ->selectRaw('route_id, count(*) as booking_count')
        ->whereHas('bookings')
        ->groupBy('route_id')
        ->orderByDesc('booking_count')
        ->limit(5)
        ->with('route')
        ->get();
    $routeLabels = $topRoutes->pluck('route.route_name')->toArray();
    $routeCounts = $topRoutes->pluck('booking_count')->toArray();
@endphp

<div>
    <h1 class="text-lg font-bold text-secondary mb-6">Laporan</h1>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Total Booking</p>
            <p class="text-2xl font-bold text-secondary mt-1">{{ $totalBookings }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Booking Selesai</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $completedBookings }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Total Revenue</p>
            <p class="text-lg font-bold text-primary-600 mt-1">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Saldo Tersedia</p>
            <p class="text-lg font-bold text-green-600 mt-1">Rp {{ number_format($balance['available_balance'], 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-secondary mb-4">💰 Revenue 6 Bulan Terakhir</h3>
            <div class="relative" style="height: 320px;">
                <canvas id="agencyReportRevenue"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-secondary mb-4">🎫 Booking per Rute</h3>
            <div class="relative" style="height: 320px;">
                <canvas id="agencyReportRoutes"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const revCtx = document.getElementById('agencyReportRevenue');
    if (revCtx) {
        new Chart(revCtx, {
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
                    tooltip: { callbacks: { label: ctx => 'Rp ' + new Intl.NumberFormat('id-ID').format(ctx.raw) } }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { font: { family: 'League Spartan', size: 11 }, color: '#6B7280', callback: v => v >= 1000000 ? (v/1000000).toFixed(1)+'M' : v >= 1000 ? (v/1000).toFixed(0)+'K' : v },
                        grid: { color: '#F3F4F6' },
                    },
                    x: { ticks: { font: { family: 'League Spartan', size: 11 }, color: '#6B7280' }, grid: { display: false } },
                },
            },
        });
    }

    const routeCtx = document.getElementById('agencyReportRoutes');
    if (routeCtx) {
        new Chart(routeCtx, {
            type: 'bar',
            data: {
                labels: @json($routeLabels),
                datasets: [{
                    label: 'Booking',
                    data: @json($routeCounts),
                    backgroundColor: ['#DC2626', '#F59E0B', '#3B82F6', '#10B981', '#8B5CF6'],
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 1, font: { family: 'League Spartan', size: 11 }, color: '#6B7280' }, grid: { color: '#F3F4F6' } },
                    y: { ticks: { font: { family: 'League Spartan', size: 11 }, color: '#4B5563' }, grid: { display: false } },
                },
            },
        });
    }
});
</script>
@endpush