<?php
// File: app/Http/Controllers/Web/Customer/BookingController.php
// Deskripsi: Web Controller untuk booking customer (FINAL)

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Schedule;
use App\Services\BookingService;
use App\Services\PaymentService;
use App\Services\CashPaymentService;
use App\Services\ScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly ScheduleService $scheduleService,
        private readonly PaymentService $paymentService,
        private readonly CashPaymentService $cashPaymentService,
    ) {}

    public function index(): View
    {
        $bookings = $this->bookingService->getCustomerBookings(auth()->user());
        return view('customer.booking.my-bookings', compact('bookings'));
    }

    public function create(Schedule $schedule): View
    {
        return view('customer.booking.create', compact('schedule'));
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $data = $request->all();
            $data['customer_id'] = auth()->id();
            $booking = $this->bookingService->createBooking($data);
            return redirect()->route('customer.booking.show', $booking)
                ->with('success', 'Booking berhasil! Silakan pilih metode pembayaran dan promo.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(Booking $booking): View
    {
        if ($booking->customer_id !== auth()->id()) abort(403);
        $booking->load([
            'schedule.agency', 'schedule.vehicle', 'schedule.driver', 'schedule.route',
            'originStop', 'destinationStop', 'passengers', 'payment', 'cashPayment',
        ]);
        $snapToken = null;
        if ($booking->payment && $booking->payment->status === 'pending' && $booking->payment->payment_type === 'midtrans') {
            try { $snapToken = $this->paymentService->getSnapToken($booking); } catch (\Exception $e) {}
        }
        return view('customer.booking.detail', compact('booking', 'snapToken'));
    }

    public function detail(Booking $booking): View
    {
        return $this->show($booking);
    }

    public function payProcess(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->customer_id !== auth()->id()) abort(403);
        if ($booking->status !== 'pending') return back()->with('error', 'Booking sudah tidak pending.');

        $request->validate([
            'payment_method' => ['required', 'in:midtrans,cash,cod'],
            'promo_id' => ['nullable', 'integer', 'exists:promos,id'],
        ], [
            'payment_method.required' => 'Silakan pilih metode pembayaran terlebih dahulu.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
        ]);
        // 👇 TAMBAHKAN: Validasi metode pembayaran sesuai rute
        $routePaymentMethods = $booking->schedule->route->payment_methods_array;
        if (!in_array($request->payment_method, $routePaymentMethods)) {
            return back()->with('error', 'Metode pembayaran ini tidak tersedia untuk rute ini.');
        }
        
        // Validasi khusus COD
        if ($request->payment_method === 'cod') {
            if (!$booking->schedule->route->cod_available) {
                return back()->with('error', 'Rute ini tidak mendukung pembayaran COD.');
            }
            if (!$booking->schedule->allow_cod) {
                return back()->with('error', 'Jadwal ini tidak menyediakan opsi COD.');
            }
        }

        // Validasi promo dengan metode pembayaran
        if ($request->filled('promo_id')) {
            $promo = \App\Models\Promo::find($request->promo_id);
            if ($promo && !$promo->isApplicableFor($request->payment_method)) {
                return back()->with('error', 'Promo "' . $promo->name . '" tidak berlaku untuk metode pembayaran yang dipilih.');
            }
            session()->put('selected_promo_' . $booking->id, $request->promo_id);
        }

        $method = $request->payment_method;

        return match($method) {
            'midtrans' => $this->processMidtrans($booking),
            'cash' => $this->processCash($booking),
            'cod' => $this->processCod($booking),
            default => back()->with('error', 'Metode tidak valid.'),
        };
    }

    private function processMidtrans(Booking $booking): RedirectResponse
    {
        try {
            $this->cleanupOldPayments($booking);
            $this->paymentService->createPayment($booking);
            $this->paymentService->getSnapToken($booking);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat pembayaran: ' . $e->getMessage());
        }
        $this->applyPromoFromSession($booking);
        return redirect()->route('customer.booking.show', $booking)
            ->with('success', 'Silakan klik tombol BAYAR SEKARANG untuk menyelesaikan pembayaran.');
    }

    private function processCash(Booking $booking): RedirectResponse
    {
        try {
            $this->cleanupOldPayments($booking);
            $this->cashPaymentService->createCashPayment($booking);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat kode bayar: ' . $e->getMessage());
        }
        $this->applyPromoFromSession($booking);
        return redirect()->route('customer.booking.show', $booking)
            ->with('success', 'Kode bayar berhasil dibuat! Tunjukkan ke Warung GoMad terdekat.');
    }

    private function processCod(Booking $booking): RedirectResponse
    {
        if (!$booking->schedule->allow_cod) return back()->with('error', 'Jadwal ini tidak menyediakan opsi COD.');
        if (!$booking->schedule->route->cod_available) return back()->with('error', 'Rute ini tidak mendukung COD.');

        $walletService = app(\App\Services\WalletService::class);
        $agency = $booking->schedule->agency;
        $minBalance = $booking->schedule->cod_min_balance ?? 500000;
        if (!$walletService->canUseCod($agency, $minBalance)) {
            return back()->with('error', 'Saldo jaminan agency tidak mencukupi untuk COD.');
        }

        try {
            $this->cleanupOldPayments($booking);
            \App\Models\Payment::create([
                'booking_id' => $booking->id,
                'amount' => $booking->total_price,
                'commission' => $booking->total_price * 0.05,
                'agency_revenue' => $booking->total_price * 0.95,
                'payment_type' => 'cod',
                'status' => \App\Enums\PaymentStatus::COD_PENDING->value,
            ]);
            
            // 👇 UBAH: paid → confirmed
            $booking->update(['status' => \App\Enums\BookingStatus::CONFIRMED->value]);
            
            $walletService->holdCodBalance($booking);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses COD: ' . $e->getMessage());
        }
        $this->applyPromoFromSession($booking);
        return redirect()->route('customer.booking.show', $booking)
            ->with('success', 'Pembayaran COD dipilih. Bayar langsung ke driver saat penjemputan.');
    }

    private function cleanupOldPayments(Booking $booking): void
    {
        if ($booking->payment) $booking->payment->delete();
        if ($booking->cashPayment) {
            $relatedPayment = \App\Models\Payment::where('cash_payment_id', $booking->cashPayment->id)->first();
            if ($relatedPayment) $relatedPayment->delete();
            $booking->cashPayment->delete();
        }
        if ($booking->status !== 'pending') $booking->update(['status' => 'pending']);
        $booking->refresh();
    }

    private function applyPromoFromSession(Booking $booking): void
    {
        $promoId = session()->get('selected_promo_' . $booking->id);
        if (!$promoId) return;

        try {
            $promo = \App\Models\Promo::find($promoId);
            if (!$promo || !$promo->isActiveNow()) { session()->forget('selected_promo_' . $booking->id); return; }

            $promoService = app(\App\Services\PromoService::class);
            if (!$promoService->canUsePromo($booking->customer, $promo)) { session()->forget('selected_promo_' . $booking->id); return; }

            $basePrice = (float) ($booking->base_price ?? $booking->total_price);
            $discount = $promoService->calculateDiscount($promo, $basePrice);

            if ($discount > 0) {
                $newTotal = max(0, (float) $booking->total_price - $discount);
                $booking->update([
                    'total_price' => $newTotal,
                    'discount_amount' => ((float) ($booking->discount_amount ?? 0)) + $discount,
                ]);
                \App\Models\PromoUsage::create([
                    'promo_id' => $promo->id,
                    'user_id' => $booking->customer_id,
                    'booking_id' => $booking->id,
                    'discount_amount' => $discount,
                ]);
                if ($booking->payment && $booking->payment->exists) {
                    $booking->payment->update(['amount' => $newTotal]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Apply promo error: ' . $e->getMessage());
        }
        session()->forget('selected_promo_' . $booking->id);
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        if ($booking->customer_id !== auth()->id()) abort(403);
        try {
            $this->bookingService->cancelBooking($booking);
            return redirect()->route('customer.bookings')->with('success', 'Booking berhasil dibatalkan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function eTicket(Booking $booking): View
    {
        if ($booking->customer_id !== auth()->id()) abort(403);
        if (!in_array($booking->status, ['paid', 'on_going', 'completed']))
            return redirect()->route('customer.booking.show', $booking)->with('error', 'E-Ticket hanya tersedia setelah pembayaran.');
        $this->paymentService->generateETicket($booking);
        $booking->refresh();
        return view('customer.booking.e-ticket', compact('booking'));
    }

    public function review(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->customer_id !== auth()->id()) abort(403);
        if ($booking->status !== 'completed') return back()->with('error', 'Hanya bisa review booking yang sudah selesai.');
        $request->validate(['rating' => ['required', 'integer', 'min:1', 'max:5'], 'review' => ['nullable', 'string', 'max:1000']]);
        if (\App\Models\Review::where('booking_id', $booking->id)->exists()) return back()->with('error', 'Anda sudah memberikan review.');
        \App\Models\Review::create(['booking_id' => $booking->id, 'agency_id' => $booking->schedule->agency_id, 'customer_id' => auth()->id(), 'rating' => $request->rating, 'review' => $request->review]);
        $avgRating = \App\Models\Review::where('agency_id', $booking->schedule->agency_id)->avg('rating');
        $booking->schedule->agency->update(['rating' => round($avgRating, 2)]);
        return redirect()->route('customer.bookings')->with('success', 'Review berhasil! Terima kasih.');
    }

    public function changePayment(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->customer_id !== auth()->id()) abort(403);
        
        // 👇 UBAH: tambah 'confirmed' ke kondisi canChange
        $canChange = $booking->status === 'pending' 
            || $booking->status === 'confirmed'  // 👈 TAMBAHKAN
            || ($booking->status === 'paid' 
                && $booking->payment 
                && $booking->payment->payment_type === 'cod' 
                && $booking->payment->status === 'cod_pending');
                
        if (!$canChange) return back()->with('error', 'Metode pembayaran tidak dapat diubah.');
        $request->validate(['new_method' => ['required', 'in:midtrans,cash,cod']]);
        $this->cleanupOldPayments($booking);
        $fakeRequest = new Request(['payment_method' => $request->new_method]);
        return $this->payProcess($fakeRequest, $booking);
    }
}

// End of file