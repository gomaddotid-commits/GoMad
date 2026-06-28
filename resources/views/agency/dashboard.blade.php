@extends('layouts.agency')

@section('title', 'Dashboard')
@section('content')
@php
    $agency = auth()->user()->agency;
@endphp

@if(!$agency)
<div class="text-center py-12">
    <div class="w-20 h-20 bg-primary-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <span class="text-3xl">🏢</span>
    </div>
    <h2 class="text-xl font-bold text-secondary mb-2">Setup Agency</h2>
    <p class="text-gray-600 mb-6">Lengkapi data agency Anda untuk mulai beroperasi.</p>
    <a href="{{ route('agency.setup') }}" class="btn-primary">Setup Sekarang</a>
</div>

@elseif(!$agency->is_verified)
<div class="text-center py-12">
    <div class="w-20 h-20 bg-yellow-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <span class="text-3xl">⏳</span>
    </div>
    <h2 class="text-xl font-bold text-secondary mb-2">Menunggu Verifikasi</h2>
    <p class="text-gray-600 max-w-md mx-auto mb-6">Agency Anda sedang dalam proses verifikasi. Lengkapi profil dan ajukan verifikasi untuk mengakses semua fitur.</p>
    <a href="{{ route('agency.profile.edit') }}" class="btn-primary">Lengkapi Profil</a>
</div>

@else
@php
    $today = \Carbon\Carbon::today();
    $walletService = app(\App\Services\WalletService::class);
    $balance = $walletService->getBalance($agency);
    $todaySchedules = $agency->schedules()->where('departure_date', $today)->where('is_active', true)->count();
    $monthBookings = \App\Models\Booking::whereHas('schedule', fn($q) => $q->where('agency_id', $agency->id))->whereMonth('created_at', now()->month)->count();
    $monthRevenue = \App\Models\Booking::whereHas('schedule', fn($q) => $q->where('agency_id', $agency->id))->where('status', 'completed')->whereMonth('completed_at', now()->month)->sum('total_price');
    $activeVehicles = $agency->vehicles()->where('is_active', true)->count();
    $activeDrivers = $agency->drivers()->where('is_active', true)->count();

    // Chart: Booking 7 hari terakhir
    $dailyBookings = [];
    $dailyLabels = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = now()->subDays($i);
        $dailyLabels[] = $date->translatedFormat('d M');
        $dailyBookings[] = \App\Models\Booking::whereHas('schedule', fn($q) => $q->where('agency_id', $agency->id))
            ->whereDate('created_at', $date)->count();
    }

    // Chart: Revenue 6 bulan terakhir
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
@endphp

<div>
    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Saldo Tersedia</p>
            <p class="text-xl font-bold text-primary-600 mt-1">Rp {{ number_format($balance['available_balance'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Jadwal Hari Ini</p>
            <p class="text-2xl font-bold text-secondary mt-1">{{ $todaySchedules }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Booking Bulan Ini</p>
            <p class="text-2xl font-bold text-secondary mt-1">{{ $monthBookings }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs text-gray-500 uppercase font-medium">Revenue Bulan Ini</p>
            <p class="text-lg font-bold text-green-600 mt-1">Rp {{ number_format($monthRevenue, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-secondary mb-4">📈 Booking 7 Hari Terakhir</h3>
            <div class="relative" style="height: 280px;">
                <canvas id="agencyDailyChart"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-secondary mb-4">💰 Revenue 6 Bulan Terakhir</h3>
            <div class="relative" style="height: 280px;">
                <canvas id="agencyRevenueChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('agency.schedules.create') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition group">
            <div class="w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center text-xl mx-auto mb-3 group-hover:scale-110 transition-transform">📅</div>
            <p class="font-semibold text-secondary text-sm">Buat Jadwal</p>
        </a>
        <a href="{{ route('agency.bookings.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition group">
            <div class="w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center text-xl mx-auto mb-3 group-hover:scale-110 transition-transform">🎫</div>
            <p class="font-semibold text-secondary text-sm">Lihat Booking</p>
        </a>
        <a href="{{ route('agency.wallet.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition group">
            <div class="w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center text-xl mx-auto mb-3 group-hover:scale-110 transition-transform">💰</div>
            <p class="font-semibold text-secondary text-sm">Dompet</p>
        </a>
        <a href="{{ route('agency.transfers.index') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:shadow-md transition group">
            <div class="w-12 h-12 bg-primary-50 rounded-xl flex items-center justify-center text-xl mx-auto mb-3 group-hover:scale-110 transition-transform">🔄</div>
            <p class="font-semibold text-secondary text-sm">Transfer</p>
        </a>
    </div>
</div>
@endif
@endsection

@push('scripts')
@if($agency && $agency->is_verified)
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart Daily Bookings
    const dailyCtx = document.getElementById('agencyDailyChart');
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
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { family: 'League Spartan', size: 11 }, color: '#6B7280' },
                        grid: { color: '#F3F4F6' },
                    },
                    x: {
                        ticks: { font: { family: 'League Spartan', size: 11 }, color: '#6B7280' },
                        grid: { display: false },
                    },
                },
            },
        });
    }

    // Chart Monthly Revenue
    const revCtx = document.getElementById('agencyRevenueChart');
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
                        ticks: { font: { family: 'League Spartan', size: 11 }, color: '#6B7280' },
                        grid: { display: false },
                    },
                },
            },
        });
    }
});
</script>
@endif
@endpush