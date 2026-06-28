<?php
// File: app/Http/Controllers/Api/Driver/PassengerController.php
// Deskripsi: API Controller untuk manajemen penumpang oleh driver

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\BookingPassenger;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PassengerController extends Controller
{
    public function index(Request $request, Schedule $schedule): JsonResponse
    {
        $driver = $request->user();

        if ($schedule->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak ditugaskan di jadwal ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        $passengers = BookingPassenger::whereHas('booking', function ($query) use ($schedule) {
            $query->where('schedule_id', $schedule->id)
                ->whereNotIn('status', ['cancelled']);
        })
        ->with(['booking.originStop', 'booking.destinationStop', 'booking.customer'])
        ->get()
        ->groupBy(function ($passenger) {
            return $passenger->booking->originStop->city_name;
        });

        $passengerData = [];
        foreach ($passengers as $stopCity => $stopPassengers) {
            $passengerData[] = [
                'stop_city' => $stopCity,
                'total' => $stopPassengers->count(),
                'passengers' => $stopPassengers->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'passenger_name' => $p->passenger_name,
                        'passenger_phone' => $p->passenger_phone,
                        'seat_number' => $p->seat_number,
                        'baggage_weight' => (float) ($p->baggage_weight ?? 0),
                        'booking_code' => $p->booking->booking_code,
                        'origin_city' => $p->booking->originStop->city_name,
                        'destination_city' => $p->booking->destinationStop->city_name,
                        'pickup_address' => $p->booking->pickup_address,
                        'destination_address' => $p->booking->destination_address,
                        'is_picked_up' => !is_null($p->picked_up_at),
                        'is_dropped_off' => !is_null($p->dropped_off_at),
                        'picked_up_at' => $p->picked_up_at?->format('H:i:s'),
                        'dropped_off_at' => $p->dropped_off_at?->format('H:i:s'),
                    ];
                })->values(),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar penumpang berhasil diambil.',
            'data' => [
                'schedule_id' => $schedule->id,
                'total_passengers' => $passengers->flatten(1)->count(),
                'stops' => $passengerData,
            ],
            'meta' => null,
        ]);
    }

    public function pickup(Request $request, BookingPassenger $passenger): JsonResponse
    {
        $driver = $request->user();

        $booking = $passenger->booking;
        if ($booking->schedule->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak ditugaskan di jadwal ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        if ($passenger->picked_up_at) {
            return response()->json([
                'success' => false,
                'message' => 'Penumpang sudah dijemput.',
                'data' => null,
                'meta' => null,
            ], 422);
        }

        $passenger->update(['picked_up_at' => now()]);

        // Check if all passengers for this booking are picked up, update booking status to on_going
        $allPickedUp = BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('picked_up_at')
            ->doesntExist();

        if ($allPickedUp && $booking->status === 'paid') {
            $booking->update(['status' => 'on_going']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Penumpang berhasil dijemput.',
            'data' => [
                'passenger_id' => $passenger->id,
                'passenger_name' => $passenger->passenger_name,
                'picked_up_at' => $passenger->picked_up_at->format('Y-m-d H:i:s'),
            ],
            'meta' => null,
        ]);
    }

    public function dropoff(Request $request, BookingPassenger $passenger): JsonResponse
    {
        $driver = $request->user();

        $booking = $passenger->booking;
        if ($booking->schedule->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak ditugaskan di jadwal ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        if (!$passenger->picked_up_at) {
            return response()->json([
                'success' => false,
                'message' => 'Penumpang belum dijemput.',
                'data' => null,
                'meta' => null,
            ], 422);
        }

        if ($passenger->dropped_off_at) {
            return response()->json([
                'success' => false,
                'message' => 'Penumpang sudah diturunkan.',
                'data' => null,
                'meta' => null,
            ], 422);
        }

        $passenger->update(['dropped_off_at' => now()]);

        // Check if all passengers are dropped off
        $allDroppedOff = BookingPassenger::where('booking_id', $booking->id)
            ->whereNull('dropped_off_at')
            ->doesntExist();

        if ($allDroppedOff && $booking->status === 'on_going') {
            $booking->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Release funds
            app(\App\Services\WalletService::class)->releaseFunds($booking);

            // Send notification
            app(\App\Services\NotificationService::class)->bookingCompleted($booking);
        }

        return response()->json([
            'success' => true,
            'message' => 'Penumpang berhasil diturunkan.',
            'data' => [
                'passenger_id' => $passenger->id,
                'passenger_name' => $passenger->passenger_name,
                'dropped_off_at' => $passenger->dropped_off_at->format('Y-m-d H:i:s'),
            ],
            'meta' => null,
        ]);
    }
}

// End of file