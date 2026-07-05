<?php
// File: app/Services/DriverService.php
// Deskripsi: Service untuk manajemen driver oleh agency

namespace App\Services;

use App\Models\Agency;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DriverService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function createDriver(Agency $agency, array $data): User
    {
        return DB::transaction(function () use ($agency, $data) {
            $driver = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => 'driver',
                'agency_id' => $agency->id,
                'is_active' => true,
            ]);

            return $driver;
        });
    }

    public function updateDriver(User $driver, array $data): User
    {
        if ($driver->role !== 'driver') {
            throw new \Exception('User bukan driver.');
        }

        $updateData = [];
        
        $allowedFields = ['name', 'email', 'phone', 'is_active'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (isset($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        if (!empty($updateData)) {
            $driver->update($updateData);
        }

        return $driver->fresh();
    }

    public function deleteDriver(User $driver): void
    {
        if ($driver->role !== 'driver') {
            throw new \Exception('User bukan driver.');
        }

        // Check if driver has active schedules
        $hasActiveSchedules = Schedule::where('driver_id', $driver->id)
            ->where('departure_date', '>=', now()->toDateString())
            ->where('is_active', true)
            ->exists();

        if ($hasActiveSchedules) {
            throw new \Exception('Driver masih memiliki jadwal aktif. Selesaikan atau reassign jadwal terlebih dahulu.');
        }

        $driver->update(['is_active' => false]);
        $driver->delete();
    }

    public function getAgencyDrivers(Agency $agency): Collection
    {
        return User::where('agency_id', $agency->id)
            ->where('role', 'driver')
            ->orderBy('name')
            ->get();
    }

    public function getActiveDrivers(Agency $agency): Collection
    {
        return User::where('agency_id', $agency->id)
            ->where('role', 'driver')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function assignToSchedule(Schedule $schedule, User $driver): void
    {
        if ($driver->role !== 'driver') {
            throw new \Exception('User bukan driver.');
        }

        if ($driver->agency_id !== $schedule->agency_id) {
            throw new \Exception('Driver harus dari agency yang sama.');
        }

        $scheduleService = app(ScheduleService::class);
        
        if (!$scheduleService->checkDriverAvailability($driver, $schedule->departure_date, $schedule->id)) {
            throw new \Exception('Driver sudah ditugaskan di jadwal lain pada tanggal tersebut.');
        }

        $schedule->update(['driver_id' => $driver->id]);
        
        $this->notificationService->driverAssigned($schedule, $driver);
    }

    public function getDriverTodaySchedule(User $driver): ?Schedule
    {
        if ($driver->role !== 'driver') {
            throw new \Exception('User bukan driver.');
        }

        return Schedule::with([
            'route.stops',
            'scheduleStops.routeStop',
            'vehicle',
            'agency',
            'bookings' => function ($query) {
                $query->whereNotIn('status', ['cancelled'])
                    ->with(['originStop', 'destinationStop', 'passengers']);
            },
        ])
        ->where('driver_id', $driver->id)
        ->where('departure_date', now()->toDateString())
        ->where('is_active', true)
        ->first();
    }

    public function getDriverUpcomingSchedules(User $driver, int $days = 7): Collection
    {
        if ($driver->role !== 'driver') {
            throw new \Exception('User bukan driver.');
        }

        return Schedule::with(['route', 'vehicle'])
            ->where('driver_id', $driver->id)
            ->where('departure_date', '>=', now()->toDateString())
            ->where('departure_date', '<=', now()->addDays($days)->toDateString())
            ->where('is_active', true)
            ->orderBy('departure_date')
            ->orderBy('departure_time')
            ->get();
    }

    public function isDriverAvailable(User $driver, Carbon $date, ?int $excludeScheduleId = null): bool
    {
        if ($driver->role !== 'driver') {
            return false;
        }

        $query = Schedule::where('driver_id', $driver->id)
            ->where('departure_date', $date->toDateString())
            ->where('is_active', true);

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        return !$query->exists();
    }

    public function getDriverStats(User $driver): array
    {
        if ($driver->role !== 'driver') {
            throw new \Exception('User bukan driver.');
        }

        $totalTrips = Schedule::where('driver_id', $driver->id)->count();
        $completedTrips = Schedule::where('driver_id', $driver->id)
            ->whereHas('bookings', function ($q) {
                $q->where('status', 'completed');
            })
            ->count();
        
        $totalPassengers = \App\Models\Booking::whereHas('schedule', function ($q) use ($driver) {
            $q->where('driver_id', $driver->id);
        })->where('status', 'completed')->sum('total_passengers');

        $averageRating = \App\Models\Review::whereHas('booking.schedule', function ($q) use ($driver) {
            $q->where('driver_id', $driver->id);
        })->avg('rating') ?? 0;

        return [
            'total_trips' => $totalTrips,
            'completed_trips' => $completedTrips,
            'total_passengers' => $totalPassengers,
            'average_rating' => round((float) $averageRating, 1),
        ];
    }
}

// End of file