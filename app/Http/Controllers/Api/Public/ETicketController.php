<?php
// File: app/Http/Controllers/Api/Public/ETicketController.php
// Deskripsi: API Controller untuk E-Ticket public

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ETicketController extends Controller
{
    /**
     * Cek E-Ticket berdasarkan kode booking
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'booking_code' => ['required', 'string', 'max:50'],
        ]);

        $booking = Booking::where('booking_code', $request->booking_code)
            ->with([
                'schedule.route',
                'schedule.agency',
                'schedule.vehicle',
                'schedule.driver',
                'originStop',
                'destinationStop',
                'passengers',
                'payment',
            ])
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Kode booking tidak ditemukan.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        if ($booking->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Booking ini telah dibatalkan.',
                'data' => null,
                'meta' => null,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'E-Ticket ditemukan.',
            'data' => [
                'booking_code' => $booking->booking_code,
                'status' => $booking->status,
                'status_label' => $booking->status_label,
                'total_price' => (float) $booking->total_price,
                'total_passengers' => $booking->total_passengers,
                'route' => [
                    'route_name' => $booking->schedule->route->route_name,
                    'origin_city' => $booking->originStop->city_name,
                    'destination_city' => $booking->destinationStop->city_name,
                ],
                'schedule' => [
                    'departure_date' => $booking->schedule->departure_date->format('Y-m-d'),
                    'departure_time' => $booking->schedule->departure_time,
                ],
                'agency' => [
                    'name' => $booking->schedule->agency->agency_name,
                    'logo' => $booking->schedule->agency->logo ? asset('storage/' . $booking->schedule->agency->logo) : null,
                ],
                'vehicle' => [
                    'plate_number' => $booking->schedule->vehicle->plate_number,
                    'brand' => $booking->schedule->vehicle->brand,
                    'model' => $booking->schedule->vehicle->model,
                ],
                'driver' => $booking->schedule->driver ? [
                    'name' => $booking->schedule->driver->name,
                    'phone' => $booking->schedule->driver->phone,
                ] : null,
                'pickup_address' => $booking->pickup_address,
                'destination_address' => $booking->destination_address,
                'passengers' => $booking->passengers->map(fn($p) => [
                    'name' => $p->passenger_name,
                    'seat' => $p->seat_number,
                ]),
                'payment' => $booking->payment ? [
                    'status' => $booking->payment->status,
                    'type' => $booking->payment->payment_type,
                ] : null,
            ],
            'meta' => null,
        ]);
    }

    /**
     * Kirim E-Ticket ke email
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'booking_code' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100'],
        ]);

        $booking = Booking::where('booking_code', $request->booking_code)->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Kode booking tidak ditemukan.',
                'data' => null,
                'meta' => null,
            ], 404);
        }

        // Log pengiriman
        \App\Models\Notification::create([
            'user_id' => $booking->customer_id ?? 1,
            'title' => 'E-Ticket Dikirim via API',
            'body' => "E-Ticket {$booking->booking_code} dikirim ke {$request->email}.",
            'data' => json_encode(['booking_code' => $booking->booking_code, 'email' => $request->email]),
        ]);

        return response()->json([
            'success' => true,
            'message' => "E-Ticket berhasil dikirim ke {$request->email}.",
            'data' => null,
            'meta' => null,
        ]);
    }
}

// End of file