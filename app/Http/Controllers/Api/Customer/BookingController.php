<?php
// File: app/Http/Controllers/Api/Customer/BookingController.php
// Deskripsi: API Controller untuk booking customer

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateBookingRequest;
use App\Http\Requests\Api\CreateReviewRequest;
use App\Http\Resources\Api\BookingResource;
use App\Models\Booking;
use App\Models\Review;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->getCustomerBookings(
            $request->user(),
            $request->status
        );

        return response()->json([
            'success' => true,
            'message' => 'Daftar booking berhasil diambil.',
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'total' => $bookings->count(),
            ],
        ]);
    }

    public function store(CreateBookingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['customer_id'] = $request->user()->id;

        $booking = $this->bookingService->createBooking($data);

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil dibuat. Silakan lakukan pembayaran.',
            'data' => new BookingResource($booking),
            'meta' => null,
        ], 201);
    }

    public function show(Booking $booking): JsonResponse
    {
        if ($booking->customer_id !== request()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke booking ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        $booking->load([
            'schedule.route.stops',
            'schedule.agency',
            'schedule.vehicle',
            'originStop',
            'destinationStop',
            'passengers',
            'payment',
            'cashPayment',
            'review',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Detail booking berhasil diambil.',
            'data' => new BookingResource($booking),
            'meta' => null,
        ]);
    }

    public function cancel(Booking $booking): JsonResponse
    {
        if ($booking->customer_id !== request()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke booking ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        try {
            $this->bookingService->cancelBooking($booking);

            return response()->json([
                'success' => true,
                'message' => 'Booking berhasil dibatalkan.',
                'data' => new BookingResource($booking->fresh()),
                'meta' => null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'meta' => null,
            ], 422);
        }
    }

    public function eTicket(Booking $booking): JsonResponse
    {
        if ($booking->customer_id !== request()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke booking ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        if (!in_array($booking->status, ['paid', 'on_going', 'completed'])) {
            return response()->json([
                'success' => false,
                'message' => 'E-Ticket hanya tersedia untuk booking yang sudah dibayar.',
                'data' => null,
                'meta' => null,
            ], 422);
        }

        $paymentService = app(\App\Services\PaymentService::class);
        $eTicketUrl = $paymentService->generateETicket($booking);

        return response()->json([
            'success' => true,
            'message' => 'E-Ticket berhasil dibuat.',
            'data' => [
                'e_ticket_url' => $eTicketUrl,
                'booking' => new BookingResource($booking->fresh()),
            ],
            'meta' => null,
        ]);
    }

    public function createReview(CreateReviewRequest $request): JsonResponse
    {
        $booking = Booking::where('id', $request->booking_id)
            ->where('customer_id', $request->user()->id)
            ->where('status', 'completed')
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking tidak ditemukan atau belum selesai.',
                'data' => null,
                'meta' => null,
            ], 422);
        }

        $existingReview = Review::where('booking_id', $booking->id)->first();
        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memberikan review untuk booking ini.',
                'data' => null,
                'meta' => null,
            ], 422);
        }

        $review = Review::create([
            'booking_id' => $booking->id,
            'agency_id' => $booking->schedule->agency_id,
            'customer_id' => $request->user()->id,
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        // Update agency rating
        $agency = $booking->schedule->agency;
        $avgRating = Review::where('agency_id', $agency->id)->avg('rating');
        $agency->update(['rating' => round($avgRating, 2)]);

        return response()->json([
            'success' => true,
            'message' => 'Review berhasil disimpan.',
            'data' => $review,
            'meta' => null,
        ], 201);
    }
}

// End of file