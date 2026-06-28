<?php
// File: app/Http/Controllers/Api/Agency/BookingController.php
// Deskripsi: API Controller untuk manajemen booking oleh agency

namespace App\Http\Controllers\Api\Agency;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BookingResource;
use App\Models\Booking;
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
        $agency = $request->user()->agency;

        $filters = $request->only(['status', 'schedule_id', 'date_from', 'date_to']);
        $bookings = $this->bookingService->getAgencyBookings($agency->id, $filters);

        return response()->json([
            'success' => true,
            'message' => 'Daftar booking berhasil diambil.',
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'total' => $bookings->count(),
            ],
        ]);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $agency = $request->user()->agency;

        if ($booking->schedule->agency_id !== $agency->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke booking ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        $booking->load([
            'schedule.route.stops',
            'customer',
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

    public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:confirmed,on_going,completed,cancelled'],
            'cancellation_reason' => ['required_if:status,cancelled', 'string', 'max:500'],
        ]);

        $agency = $request->user()->agency;

        if ($booking->schedule->agency_id !== $agency->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke booking ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        try {
            $newStatus = \App\Enums\BookingStatus::from($request->status);

            if (!$newStatus->canTransitionTo($newStatus)) {
                // Check if current status can transition
                $currentStatus = \App\Enums\BookingStatus::tryFrom($booking->status);
                if ($currentStatus && !$currentStatus->canTransitionTo($newStatus)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Status tidak dapat diubah dari ' . $booking->status_label . ' ke ' . $newStatus->label(),
                        'data' => null,
                        'meta' => null,
                    ], 422);
                }
            }

            $updateData = ['status' => $newStatus->value];

            if ($newStatus->value === 'cancelled') {
                $updateData['cancelled_at'] = now();
            }

            if ($newStatus->value === 'completed') {
                $updateData['completed_at'] = now();
                // Release funds to wallet
                app(\App\Services\WalletService::class)->releaseFunds($booking);
                
                // Send notification
                app(\App\Services\NotificationService::class)->bookingCompleted($booking);
            }

            $booking->update($updateData);

            if ($newStatus->value === 'cancelled') {
                app(\App\Services\NotificationService::class)->bookingCancelled(
                    $booking,
                    $request->cancellation_reason ?? 'Dibatalkan oleh agency'
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Status booking berhasil diupdate.',
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
}

// End of file