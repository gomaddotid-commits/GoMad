<?php

namespace App\Http\Controllers\Web\Agency;

use App\Http\Controllers\Controller;
use App\Models\PassengerTransfer;
use App\Models\Schedule;
use App\Services\ScheduleService;
use App\Services\PassengerTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
        private readonly PassengerTransferService $passengerTransferService,
    ) {}

    // ==================== JADWAL ====================

    public function index(): View
    {
        $agency = auth()->user()->agency;
        $schedules = Schedule::with(['route', 'vehicle', 'driver', 'ppSchedule', 'childSchedule'])
            ->where('agency_id', $agency->id)
            ->whereNull('parent_schedule_id') // Hanya tampilkan schedule pergi (bukan PP)
            ->latest()
            ->paginate(10);
        return view('agency.schedules.index', compact('schedules'));
    }

    public function create(): View
    {
        $routes = \App\Models\Route::where('is_active', true)
            ->where('is_system_generated', false)  // 🆕 Exclude rute otomatis
            ->with(['stops.city', 'originCity', 'destinationCity'])
            ->get();
        $vehicles = auth()->user()->agency->vehicles()->where('is_active', true)->get();
        $drivers = auth()->user()->agency->drivers()->where('is_active', true)->get();
        $minDays = app()->environment('local') ? 1 : 30;
        $minDate = now()->addDays($minDays)->toDateString();

        $walletService = app(\App\Services\WalletService::class);
        $agency = auth()->user()->agency;
        $depositBalance = (float) ($agency->wallet->deposit_balance ?? 0);
        $codHold = (float) ($agency->wallet->cod_hold_balance ?? 0);
        $availableDeposit = $depositBalance - $codHold;

        // Data rute untuk JavaScript
        $routesData = $routes->map(function ($route) {
            $stops = $route->stops->map(function ($stop, $index) use ($route) {
                $totalStops = $route->stops->count();
                return [
                    'id' => $stop->id,
                    'city_code' => $stop->city_code,
                    'city_name' => $stop->city_name,
                    'stop_order' => $stop->stop_order,
                    'is_first' => $stop->isFirst(),
                    'is_last' => $stop->isLast(),
                    'latitude' => (float) $stop->latitude,
                    'longitude' => (float) $stop->longitude,
                ];
            })->values()->toArray();

            return [
                'id' => $route->id,
                'route_name' => $route->route_name,
                'origin_city' => $route->origin_city_name,
                'destination_city' => $route->destination_city_name,
                'origin_city_code' => $route->origin_city_code,
                'destination_city_code' => $route->destination_city_code,
                'distance_km' => (float) ($route->distance_km ?? 0),
                'estimated_duration' => $route->estimated_duration,
                'max_price' => (float) ($route->max_price ?? 0),
                'cod_available' => (bool) $route->cod_available,
                'cod_min_deposit' => (float) ($route->cod_min_deposit ?? 500000),
                'payment_methods' => $route->payment_methods_array,
                'stops' => $stops,
            ];
        })->values()->toArray();

        // Data kendaraan untuk JavaScript (rental status)
        $vehiclesData = $vehicles->map(function ($v) {
            $isRental = $v->rentalSetting && $v->rentalSetting->is_available_for_rental;
            return [
                'id' => $v->id,
                'plate_number' => $v->plate_number,
                'brand' => $v->brand,
                'model' => $v->model,
                'capacity' => $v->capacity,
                'is_rental' => $isRental,
            ];
        })->values()->toArray();

        return view('agency.schedules.create', compact(
            'routes',
            'vehicles',
            'drivers',
            'minDays',
            'minDate',
            'availableDeposit',
            'routesData',
            'vehiclesData'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->all();
        $data['agency_id'] = auth()->user()->agency->id;

        try {
            $schedule = $this->scheduleService->createSchedule($data);

            $message = 'Jadwal berhasil dibuat!';
            if (!empty($data['is_pp']) && $data['is_pp'] == '1') {
                $message = 'Jadwal Pergi & Pulang (PP) berhasil dibuat!';
            }

            return redirect()->route('agency.schedules.show', $schedule)
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(Schedule $schedule): View
    {
        $schedule->load(['ppSchedule', 'childSchedule', 'parentSchedule']);

        // Jika ini jadwal pulang (return), tampilkan lewat jadwal perginya
        // dengan tab "Pulang" aktif agar konten & harga PP tetap dirender.
        if ($schedule->parentSchedule) {
            $goSchedule = $schedule->parentSchedule;
            $goSchedule->load(['ppSchedule', 'childSchedule', 'parentSchedule']);
            $scheduleData = $this->scheduleService->getScheduleWithPricing($goSchedule);
            $scheduleData['activeTab'] = 'pulang';
            return view('agency.schedules.show', $scheduleData);
        }

        $scheduleData = $this->scheduleService->getScheduleWithPricing($schedule);
        return view('agency.schedules.show', $scheduleData);
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        if ($schedule->bookings()->whereNotIn('status', ['cancelled'])->exists()) {
            return back()->with('error', 'Jadwal memiliki booking aktif, tidak dapat dihapus.');
        }

        // Release COD deposit
        if ($schedule->allow_cod && $schedule->cod_min_balance > 0) {
            $walletService = app(\App\Services\WalletService::class);
            $walletService->releaseCodDeposit(
                $schedule->agency,
                $schedule->cod_min_balance,
                $schedule->id
            );
        }

        // Hapus juga schedule PP jika ada
        if ($schedule->ppSchedule) {
            $ppSchedule = $schedule->ppSchedule;
            if ($ppSchedule->allow_cod && $ppSchedule->cod_min_balance > 0) {
                $walletService = app(\App\Services\WalletService::class);
                $walletService->releaseCodDeposit(
                    $ppSchedule->agency,
                    $ppSchedule->cod_min_balance,
                    $ppSchedule->id
                );
            }
            $ppSchedule->update(['is_active' => false]);
            $ppSchedule->delete();
        }

        $schedule->update(['is_active' => false]);
        $schedule->delete();

        return redirect()->route('agency.schedules.index')
            ->with('success', 'Jadwal berhasil dihapus. Saldo deposit COD telah dikembalikan.');
    }

    public function assignDriver(Request $request, Schedule $schedule): RedirectResponse
    {
        $request->validate([
            'driver_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            $driver = \App\Models\User::findOrFail($request->driver_id);
            $this->scheduleService->assignDriver($schedule, $driver);
            return back()->with('success', 'Driver berhasil ditugaskan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ==================== TRANSFER PENUMPANG ====================

    public function transfersIndex(): View
    {
        $agency = auth()->user()->agency;
        return view('agency.transfers.index', compact('agency'));
    }

    public function transferPage(Schedule $schedule): View|RedirectResponse
    {
        $agency = auth()->user()->agency;

        if ($schedule->agency_id !== $agency->id) {
            abort(403);
        }

        if (!$this->passengerTransferService->canTransfer($schedule)) {
            return redirect()->route('agency.schedules.index')
                ->with('error', 'Jadwal ini tidak dapat ditransfer.');
        }

        $bookings = $schedule->bookings()
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with(['originStop', 'destinationStop', 'passengers', 'customer'])
            ->get();

        if ($bookings->isEmpty()) {
            return redirect()->route('agency.schedules.index')
                ->with('error', 'Tidak ada booking aktif di jadwal ini.');
        }

        $availableSchedules = $this->passengerTransferService->findAvailableSchedules($schedule);

        return view('agency.transfers.create', compact('schedule', 'bookings', 'availableSchedules'));
    }

    public function searchTransfer(Request $request, Schedule $schedule): View|RedirectResponse
    {
        $agency = auth()->user()->agency;

        if ($schedule->agency_id !== $agency->id) {
            abort(403);
        }

        $request->validate([
            'booking_ids' => ['required', 'array', 'min:1'],
            'booking_ids.*' => ['integer', 'exists:bookings,id'],
        ]);

        $selectedBookings = $request->booking_ids;
        $passengerCount = \App\Models\Booking::whereIn('id', $selectedBookings)
            ->sum('total_passengers');

        $availableSchedules = $this->passengerTransferService->findAvailableSchedules($schedule, $passengerCount);

        $bookings = $schedule->bookings()
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->with(['originStop', 'destinationStop', 'passengers', 'customer'])
            ->get();

        session()->flash('selectedBookings', $selectedBookings);

        return view('agency.transfers.create', compact('schedule', 'bookings', 'availableSchedules', 'selectedBookings'));
    }

    public function createTransferRequest(Request $request): RedirectResponse
    {
        $request->validate([
            'from_schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'to_schedule_id' => ['required', 'integer', 'exists:schedules,id', 'different:from_schedule_id'],
            'booking_ids' => ['required', 'array', 'min:1'],
            'booking_ids.*' => ['integer', 'exists:bookings,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $transfer = $this->passengerTransferService->createTransferRequest($request->all());

            return redirect()->route('agency.transfers.index')
                ->with('success', 'Permintaan transfer berhasil dikirim!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function approveTransfer(PassengerTransfer $transfer): RedirectResponse
    {
        $agency = auth()->user()->agency;

        if ($transfer->to_agency_id !== $agency->id) {
            abort(403);
        }

        try {
            $this->passengerTransferService->approveTransfer($transfer, auth()->id());
            return back()->with('success', 'Transfer disetujui!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function rejectTransfer(Request $request, PassengerTransfer $transfer): RedirectResponse
    {
        $agency = auth()->user()->agency;

        if ($transfer->to_agency_id !== $agency->id) {
            abort(403);
        }

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->passengerTransferService->rejectTransfer($transfer, $request->reason);
            return back()->with('success', 'Transfer ditolak.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancelTransfer(PassengerTransfer $transfer): RedirectResponse
    {
        $agency = auth()->user()->agency;

        if ($transfer->from_agency_id !== $agency->id) {
            abort(403);
        }

        try {
            $this->passengerTransferService->cancelTransfer($transfer);
            return back()->with('success', 'Transfer dibatalkan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Agency klik Mulai - serahkan data ke driver
     */
    public function startSchedule(Schedule $schedule): RedirectResponse
    {
        $agency = auth()->user()->agency;

        if ($schedule->agency_id !== $agency->id) abort(403);
        if ($schedule->started_at) return back()->with('error', 'Jadwal sudah dimulai.');

        if ($schedule->departure_date->toDateString() !== now()->toDateString()) {
            return back()->with('error', 'Jadwal hanya bisa dimulai pada tanggal keberangkatan (' . $schedule->departure_date->format('d M Y') . ').');
        }
        
        $schedule->update(['started_at' => now()]);

        // Mulai juga schedule PP jika ada
        if ($schedule->ppSchedule && !$schedule->ppSchedule->started_at) {
            $schedule->ppSchedule->update(['started_at' => now()]);
        }

        // Notifikasi ke driver
        if ($schedule->driver) {
            app(\App\Services\NotificationService::class)->sendWhatsApp(
                $schedule->driver->phone,
                "🚀 *Jadwal Dimulai!*\n\n" .
                "Rute: {$schedule->route->route_name}\n" .
                "Tanggal: {$schedule->departure_date->format('d M Y')}\n" .
                "Jam: {$schedule->departure_time}\n\n" .
                "Data penumpang sudah bisa diakses. Silakan cek aplikasi."
            );
        }

        return back()->with('success', 'Jadwal dimulai! Data penumpang telah diserahkan ke driver.');
    }
}