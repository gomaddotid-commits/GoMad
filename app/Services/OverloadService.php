<?php
// File: app/Services/OverloadService.php
// Deskripsi: Service untuk validasi kapasitas dan overload

namespace App\Services;

use App\Enums\TravelClass;
use App\Models\Schedule;

class OverloadService
{
    public function validateCapacity(Schedule $schedule, int $requestedSeats): bool
    {
        $maxCapacity = $this->getMaxCapacity($schedule);
        $currentBooked = $this->getCurrentBookedSeats($schedule);
        
        return ($currentBooked + $requestedSeats) <= $maxCapacity;
    }

    public function getMaxCapacity(Schedule $schedule): int
    {
        $vehicleCapacity = $schedule->vehicle ? $schedule->vehicle->capacity : 8;
        
        if ($schedule->travel_class === TravelClass::ECONOMY->value) {
            $maxOverload = min((int) $schedule->max_overload, 2);
            return $vehicleCapacity + $maxOverload;
        }
        
        return $vehicleCapacity;
    }

    public function getCurrentBookedSeats(Schedule $schedule): int
    {
        return (int) $schedule->bookings()
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_passengers');
    }

    public function getOccupancyRate(Schedule $schedule): float
    {
        $maxCapacity = $this->getMaxCapacity($schedule);
        
        if ($maxCapacity <= 0) {
            return 0;
        }
        
        $currentBooked = $this->getCurrentBookedSeats($schedule);
        
        return round(($currentBooked / $maxCapacity) * 100, 2);
    }

    public function getWarningLevel(Schedule $schedule): string
    {
        $occupancyRate = $this->getOccupancyRate($schedule);
        
        if ($occupancyRate >= 100) {
            return 'full';
        } elseif ($occupancyRate >= 80) {
            return 'warning';
        } elseif ($occupancyRate >= 50) {
            return 'normal';
        } else {
            return 'low';
        }
    }

    public function getOverloadStatus(Schedule $schedule): array
    {
        $vehicleCapacity = $schedule->vehicle ? $schedule->vehicle->capacity : 8;
        $currentBooked = $this->getCurrentBookedSeats($schedule);
        $isOverloaded = false;
        $overloadCount = 0;
        
        if ($schedule->travel_class === TravelClass::ECONOMY->value) {
            if ($currentBooked > $vehicleCapacity) {
                $isOverloaded = true;
                $overloadCount = $currentBooked - $vehicleCapacity;
            }
        }
        
        return [
            'vehicle_capacity' => $vehicleCapacity,
            'max_capacity' => $this->getMaxCapacity($schedule),
            'current_booked' => $currentBooked,
            'is_overloaded' => $isOverloaded,
            'overload_count' => $overloadCount,
            'occupancy_rate' => $this->getOccupancyRate($schedule),
            'warning_level' => $this->getWarningLevel($schedule),
        ];
    }
}

// End of file