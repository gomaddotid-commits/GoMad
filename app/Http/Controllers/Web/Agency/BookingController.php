<?php
// File: app/Http/Controllers/Web/Agency/BookingController.php
// Deskripsi: Web Controller untuk manajemen booking oleh agency

namespace App\Http\Controllers\Web\Agency;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $agency = auth()->user()->agency;

        $query = Booking::with(['schedule.route', 'customer', 'originStop', 'destinationStop', 'payment'])
            ->whereHas('schedule', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            });

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('agency.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking): View
    {
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

        return view('agency.bookings.show', compact('booking'));
    }

    /**
     * Update status booking
     */
    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $agency = auth()->user()->agency;
        
        if ($booking->schedule->agency_id !== $agency->id) {
            abort(403);
        }

        $request->validate([
            'status' => ['required', 'in:confirmed,on_going,completed,cancelled'],
        ]);

        $newStatus = $request->status;

        // Validasi transisi status
        $allowedTransitions = [
            'paid' => ['on_going', 'cancelled'],
            'on_going' => ['completed'],
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['paid', 'cancelled'],
        ];

        $currentStatus = $booking->status;
        if (!isset($allowedTransitions[$currentStatus]) || !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            return back()->with('error', "Tidak dapat mengubah status dari {$currentStatus} ke {$newStatus}.");
        }

        $updateData = ['status' => $newStatus];

        if ($newStatus === 'cancelled') {
            $updateData['cancelled_at'] = now();
        }

        if ($newStatus === 'completed') {
            $updateData['completed_at'] = now();
            // Release funds
            app(\App\Services\WalletService::class)->releaseFunds($booking);
            // Update counter agency
            $agency->increment('total_bookings');
        }

        $booking->update($updateData);

        $messages = [
            'confirmed' => 'Booking dikonfirmasi.',
            'on_going' => 'Perjalanan dimulai.',
            'completed' => 'Perjalanan selesai.',
            'cancelled' => 'Booking dibatalkan.',
        ];

        return back()->with('success', $messages[$newStatus] ?? 'Status diupdate.');
    }
}

// End of file