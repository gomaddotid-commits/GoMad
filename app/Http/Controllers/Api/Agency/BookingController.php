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

        $user = $request->user();
        $agency = $user->agency;

        // ⚡ VALIDASI: Agency harus verified
        if (!$agency || !$agency->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Agency belum diverifikasi.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        // ⚡ VALIDASI: Booking harus milik schedule agency ini
        if ($booking->schedule->agency_id !== $agency->id) {
            Log::warning('Agency attempted to access another agency booking', [
                'agency_id' => $agency->id,
                'booking_id' => $booking->id,
                'booking_agency_id' => $booking->schedule->agency_id,
                'ip' => request()->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke booking ini.',
                'data' => null,
                'meta' => null,
            ], 403);
        }

        // ⚡ VALIDASI: Transisi status yang valid
        $newStatus = \App\Enums\BookingStatus::from($request->status);
        $currentStatus = \App\Enums\BookingStatus::tryFrom($booking->status);

        if (!$currentStatus || !$currentStatus->canTransitionTo($newStatus)) {
            return response()->json([
                'success' => false,
                'message' => 'Status tidak dapat diubah dari ' . $booking->status_label . ' ke ' . $newStatus->label(),
                'data' => null,
                'meta' => null,
            ], 422);
        }

        $updateData = ['status' => $newStatus->value];

        if ($newStatus->value === 'cancelled') {
            $updateData['cancelled_at'] = now();
        }

        if ($newStatus->value === 'completed') {
            $updateData['completed_at'] = now();

            // ⚡ Hanya release funds jika booking sudah paid
            if ($booking->payment && $booking->payment->status === 'paid') {
                app(\App\Services\WalletService::class)->releaseFunds($booking);
            }

            app(\App\Services\NotificationService::class)->bookingCompleted($booking);
        }

        $booking->update($updateData);

        if ($newStatus->value === 'cancelled') {
            app(\App\Services\NotificationService::class)->bookingCancelled(
                $booking,
                $request->cancellation_reason ?? 'Dibatalkan oleh agency'
            );
        }

        Log::info('Agency updated booking status', [
            'agency_id' => $agency->id,
            'booking_id' => $booking->id,
            'old_status' => $currentStatus->value,
            'new_status' => $newStatus->value,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status booking berhasil diupdate.',
            'data' => new BookingResource($booking->fresh()),
            'meta' => null,
        ]);
    }
}

// End of file