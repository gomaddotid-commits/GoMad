<?php
// File: app/Http/Controllers/Api/Driver/ScheduleController.php
// Deskripsi: API Controller untuk jadwal driver

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ScheduleResource;
use App\Models\Schedule;
use App\Services\DriverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly DriverService $driverService,
    ) {}

    /**
     * Jadwal hari ini dengan detail booking + tombol aksi
     */
    public function today(Request $request): JsonResponse
    {
        try {
            $driver = $request->user();
            
            $schedule = Schedule::with([
                'route.stops',
                'vehicle',
                'agency',
                'bookings' => function($q) {
                    $q->whereNotIn('status', ['cancelled'])
                      ->with(['originStop', 'destinationStop', 'passengers', 'customer', 'payment']);
                }
            ])
            ->where('driver_id', $driver->id)
            ->where('departure_date', now()->toDateString())
            ->where('is_active', true)
            ->first();

            if (!$schedule) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tidak ada jadwal hari ini.',
                    'data' => null,
                    'meta' => null,
                ]);
            }

            $isStarted = !is_null($schedule->started_at);
            
            // Cek apakah semua booking selesai (completed atau semua passenger sudah dropoff)
            $allBookingsCompleted = $schedule->bookings->isNotEmpty() && $schedule->bookings->every(function($booking) {
                return $booking->status === 'completed' || 
                       ($booking->passengers->isNotEmpty() && $booking->passengers->every(fn($p) => $p->dropped_off_at !== null));
            });
            
            $data = [
                'id' => $schedule->id,
                'route_name' => $schedule->route->route_name ?? '-',
                'departure_date' => $schedule->departure_date->format('Y-m-d'),
                'departure_date_formatted' => $schedule->departure_date->format('d M Y'),
                'departure_time' => $schedule->departure_time,
                'vehicle' => [
                    'plate_number' => $schedule->vehicle->plate_number ?? '-',
                    'brand' => $schedule->vehicle->brand ?? '',
                    'model' => $schedule->vehicle->model ?? '',
                ],
                'agency' => [
                    'id' => $schedule->agency->id ?? null,
                    'name' => $schedule->agency->agency_name ?? '-',
                ],
                'started_at' => $schedule->started_at?->format('Y-m-d H:i:s'),
                'finished_at' => $schedule->finished_at?->format('Y-m-d H:i:s'),
                'is_finished' => !is_null($schedule->finished_at),
                'can_start' => $isStarted,
                'can_finish' => $isStarted && !$schedule->finished_at && $allBookingsCompleted,
                'total_bookings' => $schedule->bookings->count(),
                'completed_bookings' => $schedule->bookings->where('status', 'completed')->count(),
                'total_passengers' => $schedule->bookings->sum('total_passengers'),
                'bookings' => $schedule->bookings->map(function($booking) use ($isStarted) {
                    $allPickedUp = $booking->passengers->isNotEmpty() && $booking->passengers->every(fn($p) => $p->picked_up_at !== null);
                    $allDroppedOff = $booking->passengers->isNotEmpty() && $booking->passengers->every(fn($p) => $p->dropped_off_at !== null);
                    $isCOD = $booking->payment?->payment_type === 'cod';
                    $codPending = $booking->payment?->status === 'cod_pending';
                    $isCompleted = $booking->status === 'completed';
                    
                    $data = [
                        'id' => $booking->id,
                        'booking_code' => $booking->booking_code,
                        'customer_name' => $booking->customer->name ?? '-',
                        'customer_phone' => $booking->customer->phone ?? '-',
                        'total_passengers' => $booking->total_passengers,
                        'total_price' => (float) $booking->total_price,
                        'status' => $booking->status,
                        'is_completed' => $isCompleted,
                        'payment_type' => $booking->payment?->payment_type,
                        'payment_status' => $booking->payment?->status,
                        'is_cod' => $isCOD,
                        'passengers' => $booking->passengers->map(fn($p) => [
                            'id' => $p->id,
                            'name' => $p->passenger_name,
                            'phone' => $p->passenger_phone,
                            'seat' => $p->seat_number,
                            'baggage_weight' => (float) ($p->baggage_weight ?? 0),
                            'is_picked_up' => !is_null($p->picked_up_at),
                            'is_dropped_off' => !is_null($p->dropped_off_at),
                            'picked_up_at' => $p->picked_up_at?->format('H:i:s'),
                            'dropped_off_at' => $p->dropped_off_at?->format('H:i:s'),
                        ]),
                    ];
                    
                    // Detail hanya jika jadwal sudah dimulai
                    if ($isStarted) {
                        $data['pickup_address'] = $booking->pickup_address;
                        $data['destination_address'] = $booking->destination_address;
                        $data['pickup_maps_link'] = $booking->pickup_maps_link;
                        $data['destination_maps_link'] = $booking->destination_maps_link;
                        
                        // Tombol aksi per booking (tanpa complete)
                        $data['can_pickup'] = !$allPickedUp && !$isCompleted;
                        $data['can_dropoff'] = $allPickedUp && !$allDroppedOff && !$isCompleted;
                        // KONFIRMASI COD: hanya setelah semua penumpang diturunkan + COD pending
                        $data['can_confirm_cod'] = $isCOD && $codPending && $allDroppedOff && !$isCompleted;
                    } else {
                        $data['can_pickup'] = false;
                        $data['can_dropoff'] = false;
                        $data['can_confirm_cod'] = false;
                    }
                    
                    return $data;
                }),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil diambil.',
                'data' => $data,
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Driver today API error: ' . $e->getMessage(), [
                'driver_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat jadwal.',
                'data' => null,
                'meta' => null,
            ], 500);
        }
    }

    /**
     * Jadwal mendatang
     */
    public function upcoming(Request $request): JsonResponse
    {
        $driver = $request->user();
        $days = (int) ($request->days ?? 7);

        $schedules = $this->driverService->getDriverUpcomingSchedules($driver, $days);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal mendatang berhasil diambil.',
            'data' => $schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'route_name' => $schedule->route->route_name ?? '-',
                    'departure_date' => $schedule->departure_date->format('Y-m-d'),
                    'departure_date_formatted' => $schedule->departure_date->format('d M Y'),
                    'departure_time' => $schedule->departure_time,
                    'vehicle' => [
                        'plate_number' => $schedule->vehicle->plate_number ?? '-',
                        'brand' => $schedule->vehicle->brand ?? '',
                        'model' => $schedule->vehicle->model ?? '',
                    ],
                    'available_seats' => $schedule->available_seats ?? 0,
                    'max_capacity' => $schedule->max_capacity ?? 0,
                    'started_at' => $schedule->started_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'total' => $schedules->count(),
                'days' => $days,
            ],
        ]);
    }

    /**
     * Detail jadwal (dengan booking)
     */
    public function show(Request $request, $schedule): JsonResponse
    {
        $driver = $request->user();

        $schedule = Schedule::with([
            'route.stops',
            'vehicle',
            'agency',
            'scheduleStops.routeStop',
            'bookings' => function ($query) {
                $query->whereNotIn('status', ['cancelled'])
                    ->with(['originStop', 'destinationStop', 'passengers', 'customer', 'payment']);
            },
        ])
        ->where('driver_id', $driver->id)
        ->findOrFail($schedule);

        return response()->json([
            'success' => true,
            'message' => 'Detail jadwal berhasil diambil.',
            'data' => new ScheduleResource($schedule),
            'meta' => null,
        ]);
    }

    /**
     * Driver menyelesaikan seluruh jadwal (API)
     */
    public function finish(Request $request, Schedule $schedule): JsonResponse
    {
        $driver = $request->user();
        
        if ($schedule->driver_id !== $driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak bertugas di jadwal ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        if (!$schedule->started_at) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal belum dimulai oleh agency.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        if ($schedule->finished_at) {
            return response()->json([
                'success' => false,
                'message' => 'Jadwal sudah selesai.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($schedule) {
                $bookings = $schedule->bookings()
                    ->where('status', '!=', 'completed')
                    ->where('status', '!=', 'cancelled')
                    ->get();

                $walletService = app(\App\Services\WalletService::class);
                $notificationService = app(\App\Services\NotificationService::class);

                foreach ($bookings as $booking) {
                    // Pastikan semua penumpang sudah dijemput & diturunkan
                    \App\Models\BookingPassenger::where('booking_id', $booking->id)
                        ->whereNull('picked_up_at')
                        ->update(['picked_up_at' => now()]);
                        
                    \App\Models\BookingPassenger::where('booking_id', $booking->id)
                        ->whereNull('dropped_off_at')
                        ->update(['dropped_off_at' => now()]);

                    $booking->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);

                    $walletService->releaseFunds($booking);
                    $booking->schedule->agency->increment('total_bookings');
                    $notificationService->bookingCompleted($booking);
                }

                // Tandai jadwal selesai
                $schedule->update(['finished_at' => now()]);

                // Release saldo COD yang di-hold untuk jadwal ini
                if ($schedule->allow_cod && $schedule->cod_min_balance > 0) {
                    $walletService->releaseCodDeposit(
                        $schedule->agency,
                        $schedule->cod_min_balance,
                        $schedule->id
                    );
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Seluruh perjalanan selesai!',
                'data' => null,
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            \Log::error('Driver finish schedule error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyelesaikan jadwal: ' . $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 500);
        }
    }
}

// End of file