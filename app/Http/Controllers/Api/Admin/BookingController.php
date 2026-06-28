<?php
// File: app/Http/Controllers/Api/Admin/BookingController.php
// Deskripsi: API Controller untuk monitoring booking oleh admin

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BookingResource;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['schedule.agency', 'schedule.route', 'customer', 'payment']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->agency_id) {
            $query->whereHas('schedule', function ($q) use ($request) {
                $q->where('agency_id', $request->agency_id);
            });
        }

        if ($request->booking_code) {
            $query->where('booking_code', 'like', '%' . $request->booking_code . '%');
        }

        if ($request->date_from && $request->date_to) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Daftar booking berhasil diambil.',
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    public function show(Booking $booking): JsonResponse
    {
        $booking->load([
            'schedule.agency',
            'schedule.route.stops',
            'schedule.vehicle',
            'schedule.driver',
            'customer',
            'originStop',
            'destinationStop',
            'passengers',
            'payment',
            'cashPayment.paymentAgent',
            'review',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detail booking berhasil diambil.',
            'data' => new BookingResource($booking),
            'meta' => null,
        ]);
    }
}

// End of file