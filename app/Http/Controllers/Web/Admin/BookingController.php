<?php
// File: app/Http/Controllers/Web/Admin/BookingController.php
// Deskripsi: Web Controller untuk monitoring booking oleh admin

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\User;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $query = Booking::with(['schedule.agency', 'schedule.route', 'customer', 'payment']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->booking_code) {
            $query->where('booking_code', 'like', '%' . $request->booking_code . '%');
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking): View
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

        return view('admin.bookings.show', compact('booking'));
    }

    public function approveRefund(Booking $booking): RedirectResponse
    {
        try {
            $paymentService = app(\App\Services\PaymentService::class);
            $result = $paymentService->approveRefund($booking, auth()->user());
            
            if ($result['success']) {
                return back()->with('success', 'Refund berhasil disetujui dan diproses.');
            }
            
            return back()->with('error', $result['message']);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses refund: ' . $e->getMessage());
        }
    }

    public function rejectRefund(Request $request, Booking $booking): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);
        
        try {
            $paymentService = app(\App\Services\PaymentService::class);
            $paymentService->rejectRefund($booking, auth()->user(), $request->reason);
            
            return back()->with('success', 'Refund ditolak.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

}

// End of file