<?php
// File: app/Http/Controllers/Web/Admin/BookingController.php
// Deskripsi: Web Controller untuk monitoring booking oleh admin

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
}

// End of file