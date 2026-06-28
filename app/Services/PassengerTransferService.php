<?php
// File: app/Services/PassengerTransferService.php
// Deskripsi: Service untuk transfer penumpang antar jadwal (FIXED)

namespace App\Services;

use App\Models\Booking;
use App\Models\PassengerTransfer;
use App\Models\Schedule;
use App\Models\Agency;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PassengerTransferService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly OverloadService $overloadService,
    ) {}

    /**
     * Cari jadwal yang bisa menerima transfer
     * BEDA AGENCY: hanya yang accept_external_transfer = true
     * SAMA AGENCY: semua jadwal aktif yang searah
     */
    public function findAvailableSchedules(Schedule $fromSchedule, ?int $passengerCount = null): Collection
    {
        $departureDate = Carbon::parse($fromSchedule->departure_date);
        $route = $fromSchedule->route;
        $passengerCount = $passengerCount ?? $fromSchedule->bookings()
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->sum('total_passengers');

        $fromAgencyId = $fromSchedule->agency_id;

        Log::info('Transfer Debug - Mencari jadwal tujuan', [
            'from_schedule_id' => $fromSchedule->id,
            'from_agency_id' => $fromAgencyId,
            'route_id' => $route->id,
            'departure_date' => $departureDate->toDateString(),
            'passenger_count' => $passengerCount,
        ]);

        // PERBAIKAN: Gunakan whereDate() untuk membandingkan tanggal saja (abaikan waktu)
        $allSchedules = Schedule::with(['agency', 'vehicle', 'route.stops'])
            ->where('id', '!=', $fromSchedule->id)
            ->where('is_active', true)
            ->whereDate('departure_date', $departureDate->toDateString()) // ✅ PAKAI whereDate()
            ->where('route_id', $route->id)
            ->get();

        Log::info('Transfer Debug - Semua jadwal se-route', [
            'total' => $allSchedules->count(),
            'schedules' => $allSchedules->map(function($s) {
                return [
                    'id' => $s->id,
                    'agency_id' => $s->agency_id,
                    'agency_name' => $s->agency->agency_name ?? '?',
                    'accept_external' => $s->accept_external_transfer,
                    'available_seats' => $s->available_seats,
                    'max_capacity' => $s->max_capacity,
                ];
            })->toArray(),
        ]);

        // Filter: hanya yang verified dan bisa menerima
        $filtered = $allSchedules->filter(function ($schedule) use ($fromAgencyId, $passengerCount) {
            // Agency harus verified
            if (!$schedule->agency || !$schedule->agency->is_verified) {
                Log::info("Transfer Debug - Schedule #{$schedule->id} ditolak: agency tidak verified");
                return false;
            }

            // Jika beda agency, harus accept_external_transfer = true
            if ($schedule->agency_id !== $fromAgencyId && !$schedule->accept_external_transfer) {
                Log::info("Transfer Debug - Schedule #{$schedule->id} ditolak: tidak accept external transfer");
                return false;
            }

            // Cek kapasitas
            $currentBooked = $schedule->bookings()
                ->whereNotIn('status', ['cancelled'])
                ->sum('total_passengers');
            $maxCapacity = $this->overloadService->getMaxCapacity($schedule);
            $availableSeats = $maxCapacity - $currentBooked;

            if ($availableSeats < $passengerCount) {
                Log::info("Transfer Debug - Schedule #{$schedule->id} ditolak: kursi tidak cukup (tersedia: {$availableSeats}, butuh: {$passengerCount})");
                return false;
            }

            return true;
        });

        Log::info('Transfer Debug - Hasil filter', [
            'total' => $filtered->count(),
        ]);

        return $filtered->values();
    }


    /**
     * Buat permintaan transfer
     */
    public function createTransferRequest(array $data): PassengerTransfer
    {
        return DB::transaction(function () use ($data) {
            $fromSchedule = Schedule::with('agency')->findOrFail($data['from_schedule_id']);
            $toSchedule = Schedule::with('agency')->findOrFail($data['to_schedule_id']);
            $bookings = Booking::whereIn('id', $data['booking_ids'])
                ->where('schedule_id', $fromSchedule->id)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->get();

            if ($bookings->isEmpty()) {
                throw new \Exception('Tidak ada booking yang valid untuk ditransfer.');
            }

            $totalPassengers = $bookings->sum('total_passengers');
            $totalBookingValue = $bookings->sum('total_price');
            
            // Biaya transfer: 0 jika sesama agency, sesuai jadwal jika beda agency
            $isInternalTransfer = $fromSchedule->agency_id === $toSchedule->agency_id;
            $transferFee = $isInternalTransfer ? 0 : (float) ($toSchedule->transfer_fee_per_passenger ?? 20000);
            $totalTransferFee = $transferFee * $totalPassengers;

            // Validasi kapasitas
            if (!$this->overloadService->validateCapacity($toSchedule, $totalPassengers)) {
                throw new \Exception('Mobil tujuan tidak memiliki cukup kursi. Kursi tersedia: ' . $toSchedule->available_seats);
            }

            $transfer = PassengerTransfer::create([
                'from_schedule_id' => $fromSchedule->id,
                'to_schedule_id' => $toSchedule->id,
                'from_agency_id' => $fromSchedule->agency_id,
                'to_agency_id' => $toSchedule->agency_id,
                'total_passengers' => $totalPassengers,
                'transfer_fee_per_passenger' => $transferFee,
                'total_transfer_fee' => $totalTransferFee,
                'total_booking_value' => $totalBookingValue,
                'status' => $isInternalTransfer ? 'approved' : 'pending', // Internal langsung approved
                'notes' => $data['notes'] ?? null,
                'approved_at' => $isInternalTransfer ? now() : null,
                'completed_at' => $isInternalTransfer ? now() : null,
            ]);

            // Attach bookings
            $transfer->bookings()->attach($bookings->pluck('id'));

            // Jika internal transfer, langsung pindahkan booking
            if ($isInternalTransfer) {
                foreach ($transfer->bookings as $booking) {
                    $booking->update([
                        'schedule_id' => $toSchedule->id,
                    ]);
                }

                // Update counter
                $transfer->fromSchedule->increment('transferred_out_count', $transfer->total_passengers);
                $transfer->toSchedule->increment('transferred_in_count', $transfer->total_passengers);

                // Notifikasi ke customer
                foreach ($transfer->bookings as $booking) {
                    $this->notificationService->sendWhatsApp(
                        $booking->customer->phone,
                        "ℹ️ *Perubahan Jadwal - {$booking->booking_code}*\n\n" .
                        "Jadwal Anda diubah oleh agency:\n\n" .
                        "🕗 Jam baru: {$transfer->toSchedule->departure_time}\n" .
                        "🚐 Kendaraan: {$transfer->toSchedule->vehicle->plate_number}\n\n" .
                        "Naik: {$booking->originStop->city_name}\n" .
                        "Turun: {$booking->destinationStop->city_name}\n\n" .
                        "Jika keberatan, hubungi kami."
                    );
                }
            } else {
                // Notifikasi ke agency penerima (beda agency)
                $toAgencyUser = $toSchedule->agency->user;
                if ($toAgencyUser && $toAgencyUser->phone) {
                    $this->notificationService->sendWhatsApp(
                        $toAgencyUser->phone,
                        "🔔 *Permintaan Transfer Masuk*\n\n" .
                        "Dari: {$fromSchedule->agency->agency_name}\n" .
                        "Jadwal: {$toSchedule->route->route_name}\n" .
                        "Tanggal: {$toSchedule->departure_date->format('d M Y')}\n" .
                        "Jumlah: {$totalPassengers} penumpang\n" .
                        "Biaya transfer: Rp " . number_format($totalTransferFee, 0, ',', '.') . "\n\n" .
                        "Login ke dashboard untuk menyetujui/menolak."
                    );
                }
            }

            return $transfer->load(['fromSchedule', 'toSchedule', 'bookings']);
        });
    }

    /**
     * Setujui transfer (oleh agency penerima - hanya untuk external transfer)
     */
    public function approveTransfer(PassengerTransfer $transfer, int $approvedBy): void
    {
        DB::transaction(function () use ($transfer, $approvedBy) {
            if ($transfer->status !== 'pending') {
                throw new \Exception('Transfer tidak dalam status pending.');
            }

            $transfer->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            // Pindahkan booking ke jadwal baru
            $toSchedule = $transfer->toSchedule;
            foreach ($transfer->bookings as $booking) {
                $booking->update([
                    'schedule_id' => $toSchedule->id,
                ]);
            }

            // Update counter
            $transfer->fromSchedule->increment('transferred_out_count', $transfer->total_passengers);
            $transfer->toSchedule->increment('transferred_in_count', $transfer->total_passengers);

            $transfer->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Notifikasi ke customer
            foreach ($transfer->bookings as $booking) {
                $this->notificationService->sendWhatsApp(
                    $booking->customer->phone,
                    "ℹ️ *Perubahan Jadwal - {$booking->booking_code}*\n\n" .
                    "Jadwal Anda diubah oleh agency:\n\n" .
                    "Sebelumnya: {$transfer->fromSchedule->agency->agency_name} ({$transfer->fromSchedule->departure_time})\n" .
                    "Sekarang: {$transfer->toSchedule->agency->agency_name} ({$transfer->toSchedule->departure_time})\n\n" .
                    "Naik: {$booking->originStop->city_name}\n" .
                    "Turun: {$booking->destinationStop->city_name}\n\n" .
                    "Jika keberatan, hubungi kami."
                );
            }

            // Notifikasi ke agency pengirim
            $fromAgencyUser = $transfer->fromSchedule->agency->user;
            if ($fromAgencyUser && $fromAgencyUser->phone) {
                $this->notificationService->sendWhatsApp(
                    $fromAgencyUser->phone,
                    "✅ *Transfer Disetujui*\n\n" .
                    "{$transfer->total_passengers} penumpang telah dipindahkan ke {$transfer->toSchedule->agency->agency_name}.\n" .
                    "Biaya transfer: Rp " . number_format($transfer->total_transfer_fee, 0, ',', '.')
                );
            }
        });
    }

    /**
     * Tolak transfer
     */
    public function rejectTransfer(PassengerTransfer $transfer, string $reason): void
    {
        if ($transfer->status !== 'pending') {
            throw new \Exception('Transfer tidak dalam status pending.');
        }

        $transfer->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        $fromAgencyUser = $transfer->fromSchedule->agency->user;
        if ($fromAgencyUser && $fromAgencyUser->phone) {
            $this->notificationService->sendWhatsApp(
                $fromAgencyUser->phone,
                "❌ *Transfer Ditolak*\n\n" .
                "Transfer ke {$transfer->toSchedule->agency->agency_name} ditolak.\n" .
                "Alasan: {$reason}"
            );
        }
    }

    /**
     * Batalkan transfer
     */
    public function cancelTransfer(PassengerTransfer $transfer): void
    {
        if (!in_array($transfer->status, ['pending'])) {
            throw new \Exception('Transfer tidak dapat dibatalkan.');
        }

        $transfer->update(['status' => 'cancelled']);
    }

    /**
     * Dapatkan riwayat transfer untuk agency
     */
    public function getAgencyTransfers(int $agencyId, ?string $type = null): Collection
    {
        $query = PassengerTransfer::with([
            'fromSchedule.route', 
            'toSchedule.route', 
            'fromAgency', 
            'toAgency', 
            'bookings'
        ])->where(function ($q) use ($agencyId) {
            $q->where('from_agency_id', $agencyId)
              ->orWhere('to_agency_id', $agencyId);
        });

        if ($type === 'outgoing') {
            $query->where('from_agency_id', $agencyId);
        } elseif ($type === 'incoming') {
            $query->where('to_agency_id', $agencyId);
        }

        return $query->latest()->get();
    }

    /**
     * Cek apakah jadwal bisa ditransfer (PENGE CEKAN LEBIH LONGGAR)
     */
    public function canTransfer(Schedule $schedule): bool
    {
        // Jadwal harus aktif
        if (!$schedule->is_active) return false;
        
        // Jadwal belum lewat
        if (Carbon::parse($schedule->departure_date)->isPast()) return false;
        
        // Minimal ada 1 booking yang valid
        $hasBookings = $schedule->bookings()
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->exists();
        
        if (!$hasBookings) return false;

        // Cek apakah ada jadwal lain yang bisa menerima transfer
        $availableSchedules = $this->findAvailableSchedules($schedule);
        
        return $availableSchedules->isNotEmpty();
    }
}

// End of file