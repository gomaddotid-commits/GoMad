<?php
// File: app/Http/Controllers/Web/Agency/ScheduleController.php
// Deskripsi: Web Controller untuk manajemen jadwal agency (FULL dengan Transfer)

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
        $schedules = Schedule::with(['route', 'vehicle', 'driver'])
            ->where('agency_id', $agency->id)
            ->latest()
            ->paginate(10);
        return view('agency.schedules.index', compact('schedules'));
    }

    public function create(): View
    {
        return view('agency.schedules.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->all();
        $data['agency_id'] = auth()->user()->agency->id;

        try {
            $schedule = $this->scheduleService->createSchedule($data);
            return redirect()->route('agency.schedules.show', $schedule)
                ->with('success', 'Jadwal berhasil dibuat!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(Schedule $schedule): View
    {
        $scheduleData = $this->scheduleService->getScheduleWithPricing($schedule);
        return view('agency.schedules.show', $scheduleData);
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        if ($schedule->bookings()->whereNotIn('status', ['cancelled'])->exists()) {
            return back()->with('error', 'Jadwal memiliki booking aktif, tidak dapat dihapus.');
        }

        $schedule->update(['is_active' => false]);
        $schedule->delete();
        return back()->with('success', 'Jadwal berhasil dihapus.');
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

    /**
     * Halaman daftar transfer (masuk & keluar)
     */
    public function transfersIndex(): View
    {
        $agency = auth()->user()->agency;
        return view('agency.transfers.index', compact('agency'));
    }

    /**
     * Halaman transfer untuk jadwal tertentu (cari mobil tujuan)
     */
    public function transferPage(Schedule $schedule): View|RedirectResponse
    {
        $agency = auth()->user()->agency;
        
        // Pastikan jadwal milik agency ini
        if ($schedule->agency_id !== $agency->id) {
            abort(403);
        }

        // Cek apakah jadwal bisa ditransfer
        if (!$this->passengerTransferService->canTransfer($schedule)) {
            return redirect()->route('agency.schedules.index')
                ->with('error', 'Jadwal ini tidak dapat ditransfer. Pastikan jadwal aktif, mengizinkan transfer, dan ada booking.');
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

    /**
     * Cari jadwal tujuan transfer
     */
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

        // Simpan selected bookings ke session untuk digunakan di view
        session()->flash('selectedBookings', $selectedBookings);

        return view('agency.transfers.create', compact('schedule', 'bookings', 'availableSchedules', 'selectedBookings'));
    }

    /**
     * Buat permintaan transfer
     */
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
                ->with('success', 'Permintaan transfer berhasil dikirim! Menunggu persetujuan agency penerima.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Setujui transfer (agency penerima)
     */
    public function approveTransfer(PassengerTransfer $transfer): RedirectResponse
    {
        $agency = auth()->user()->agency;
        
        // Pastikan transfer ditujukan ke agency ini
        if ($transfer->to_agency_id !== $agency->id) {
            abort(403);
        }

        try {
            $this->passengerTransferService->approveTransfer($transfer, auth()->id());
            return back()->with('success', 'Transfer disetujui! Penumpang telah dipindahkan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Tolak transfer (agency penerima)
     */
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

    /**
     * Batalkan transfer (agency pengirim)
     */
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

        $schedule->update(['started_at' => now()]);

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

// End of file