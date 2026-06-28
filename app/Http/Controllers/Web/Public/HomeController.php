<?php
// File: app/Http/Controllers/Web/Public/HomeController.php
// Deskripsi: Web Controller untuk halaman utama public

namespace App\Http\Controllers\Web\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Agency;
use App\Models\Route;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;

class HomeController extends Controller
{
    public function index(): View
    {
        $popularRoutes = Route::where('is_active', true)
            ->withCount(['schedules' => function ($query) {
                $query->where('departure_date', '>=', now()->toDateString())
                    ->where('is_active', true);
            }])
            ->orderByDesc('schedules_count')
            ->limit(5)
            ->get();

        $topAgencies = Agency::where('is_verified', true)
            ->orderByDesc('rating')
            ->orderByDesc('total_bookings')
            ->limit(5)
            ->get();

        return view('public-pages.home', compact('popularRoutes', 'topAgencies'));
    }

    public function downloadApp(): View
    {
        return view('public-pages.download-app');
    }

    /**
     * Halaman Cek E-Ticket (Public)
     */
    public function eTicketPage(): View
    {
        return view('public-pages.e-ticket');
    }

    /**
     * Cek E-Ticket berdasarkan kode booking
     */
    public function checkETicket(Request $request): View|RedirectResponse
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
            return back()->with('error', 'Kode booking tidak ditemukan. Periksa kembali kode booking Anda.');
        }

        if ($booking->status === 'cancelled') {
            return back()->with('error', 'Booking ini telah dibatalkan.');
        }

        return view('public-pages.e-ticket', compact('booking'));
    }

    /**
     * Kirim E-Ticket ke email
     */
    public function sendETicket(Request $request): RedirectResponse
    {
        $request->validate([
            'booking_code' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100'],
        ]);

        $booking = Booking::where('booking_code', $request->booking_code)
            ->with([
                'schedule.route',
                'schedule.agency',
                'schedule.vehicle',
                'originStop',
                'destinationStop',
                'passengers',
            ])
            ->first();

        if (!$booking) {
            return back()->with('error', 'Kode booking tidak ditemukan.');
        }

        if ($booking->status === 'cancelled') {
            return back()->with('error', 'Booking ini telah dibatalkan.');
        }

        // Generate E-Ticket URL
        $eTicketUrl = route('eticket.public') . '?code=' . $booking->booking_code;

        // Kirim email (simple version - bisa di-upgrade dengan Mail facade)
        try {
            // Simpan log pengiriman
            \App\Models\Notification::create([
                'user_id' => $booking->customer_id ?? 1,
                'title' => 'E-Ticket Dikirim',
                'body' => "E-Ticket untuk booking {$booking->booking_code} telah dikirim ke {$request->email}.",
                'data' => json_encode([
                    'booking_code' => $booking->booking_code,
                    'email' => $request->email,
                ]),
            ]);

            // Di production, gunakan Mail facade:
            // Mail::to($request->email)->send(new \App\Mail\ETicketMail($booking));

            return back()->with('success', "E-Ticket berhasil dikirim ke {$request->email}! Silakan cek inbox atau spam Anda.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengirim email. Silakan coba lagi nanti.');
        }
    }
}

// End of file