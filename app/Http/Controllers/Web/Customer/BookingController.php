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
use Illuminate\Support\Facades\DB; 

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
            try { 
                // Selalu generate Snap Token baru, jangan andalkan session
                $snapToken = $this->paymentService->getSnapToken($booking); 
            } catch (\Exception $e) {
                \Log::error('Snap Token generation failed: ' . $e->getMessage());
            }
        }
        return view('customer.booking.detail', compact('booking', 'snapToken'));
    }

    public function detail(Booking $booking): View
    {
        return $this->show($booking);
    }

    public function payProcess(Request $request, Booking $booking): RedirectResponse
    {
        if ($booking->customer_id !== auth()->id()) {
            abort(403);
        }

        if ($booking->status !== 'pending') {
            return back()->with('error', 'Booking sudah tidak pending.');
        }

        $request->validate([
            'payment_method' => ['required', 'in:midtrans,cash,cod'],
            'promo_id' => ['nullable', 'integer', 'exists:promos,id'],
        ], [
            'payment_method.required' => 'Silakan pilih metode pembayaran terlebih dahulu.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
        ]);

        // Validasi metode pembayaran sesuai rute
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

        // ═══════════════════════════════════════════
        // 🔧 APPLY PROMO SEKARANG (SEBELUM PAYMENT)
        // ═══════════════════════════════════════════
        if ($request->filled('promo_id')) {
            $promo = \App\Models\Promo::find($request->promo_id);

            \Log::info('payProcess - promo received', [
                'booking_code' => $booking->booking_code,
                'promo_id' => $request->promo_id,
                'promo_exists' => !is_null($promo),
                'promo_is_active' => $promo ? ($promo->isActiveNow() ? 'yes' : 'no') : 'N/A',
                'promo_start_date' => $promo ? $promo->start_date->toDateString() : 'N/A',
                'promo_end_date' => $promo ? $promo->end_date->toDateString() : 'N/A',
                'now_date' => now()->toDateString(),
            ]);

            if ($promo && $promo->isActiveNow()) {
                \Log::info('payProcess - promo is active, checking applicable', [
                    'promo_applicable_methods' => $promo->applicable_payment_methods,
                    'request_method' => $request->payment_method,
                    'is_applicable' => $promo->isApplicableFor($request->payment_method),
                ]);

                // Validasi promo dengan metode pembayaran
                if (!$promo->isApplicableFor($request->payment_method)) {
                    \Log::warning('payProcess - promo not applicable for payment method', [
                        'promo_id' => $promo->id,
                        'promo_methods' => $promo->applicable_payment_methods,
                        'request_method' => $request->payment_method,
                    ]);
                    return back()->with('error', 'Promo "' . $promo->name . '" tidak berlaku untuk metode pembayaran yang dipilih.');
                }

                // Simpan ke session (fallback)
                session()->put('selected_promo_' . $booking->id, $request->promo_id);

                // APPLY promo langsung
                $promoService = app(\App\Services\PromoService::class);

                $canUse = $promoService->canUsePromo($booking->customer, $promo);
                \Log::info('payProcess - canUsePromo check', [
                    'can_use' => $canUse ? 'yes' : 'no',
                    'customer_id' => $booking->customer_id,
                    'promo_id' => $promo->id,
                ]);

                if ($canUse) {
                    $basePrice = (float) ($booking->base_price ?? $booking->total_price);
                    $discount = $promoService->calculateDiscount($promo, $basePrice);

                    \Log::info('payProcess - discount calculation', [
                        'base_price' => $basePrice,
                        'discount_percent' => $promo->discount_percent,
                        'max_discount' => $promo->max_discount,
                        'calculated_discount' => $discount,
                    ]);

                    if ($discount > 0) {
                        $newTotal = max(0, (float) $booking->total_price - $discount);

                        \Log::info('payProcess - applying promo to booking', [
                            'booking_code' => $booking->booking_code,
                            'promo_name' => $promo->name,
                            'old_total' => $booking->total_price,
                            'discount' => $discount,
                            'new_total' => $newTotal,
                        ]);

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

                        \Log::info('payProcess - promo applied successfully', [
                            'booking_code' => $booking->booking_code,
                            'new_total_price' => $newTotal,
                            'new_discount_amount' => $booking->fresh()->discount_amount,
                        ]);
                    } else {
                        \Log::warning('payProcess - discount is zero after calculation', [
                            'base_price' => $basePrice,
                            'discount_percent' => $promo->discount_percent,
                            'max_discount' => $promo->max_discount,
                            'min_purchase' => $promo->min_purchase,
                        ]);
                    }
                } else {
                    \Log::warning('payProcess - canUsePromo returned false', [
                        'customer_id' => $booking->customer_id,
                        'promo_id' => $promo->id,
                        'promo_type' => $promo->type,
                        'promo_created_by' => $promo->created_by,
                    ]);
                }
            } else {
                \Log::warning('payProcess - promo not active or not found', [
                    'promo_id' => $request->promo_id,
                    'promo_found' => !is_null($promo),
                    'is_active' => $promo ? ($promo->isActiveNow() ? 'yes' : 'no') : 'N/A',
                    'promo_start' => $promo ? $promo->start_date->toDateString() : 'N/A',
                    'promo_end' => $promo ? $promo->end_date->toDateString() : 'N/A',
                    'promo_is_active_field' => $promo ? ($promo->is_active ? 'yes' : 'no') : 'N/A',
                    'now' => now()->toDateString(),
                ]);
            }
        }

        // ⚡ Refresh booking untuk dapatkan harga terbaru
        $booking->refresh();

        \Log::info('payProcess - before routing to payment', [
            'booking_code' => $booking->booking_code,
            'total_price' => $booking->total_price,
            'discount_amount' => $booking->discount_amount,
            'promo_id' => $request->promo_id,
            'method' => $request->payment_method,
        ]);

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
            $booking->refresh();

            \Log::info('processMidtrans - starting', [
                'booking_code' => $booking->booking_code,
                'total_price' => $booking->total_price,
                'discount_amount' => $booking->discount_amount,
            ]);

            $this->cleanupOldPayments($booking);
            $this->paymentService->createPayment($booking);
            $this->paymentService->getSnapToken($booking);

        } catch (\Exception $e) {
            \Log::error('ProcessMidtrans error: ' . $e->getMessage());
            return back()->with('error', 'Gagal membuat pembayaran: ' . $e->getMessage());
        }

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
        if (!$booking->schedule->allow_cod) {
            return back()->with('error', 'Jadwal ini tidak menyediakan opsi COD.');
        }
        
        if (!$booking->schedule->route->cod_available) {
            return back()->with('error', 'Rute ini tidak mendukung COD.');
        }

        $walletService = app(\App\Services\WalletService::class);
        $agency = $booking->schedule->agency;
        $minBalance = $booking->schedule->cod_min_balance ?? 500000;
        
        // ✅ TAMBAHKAN: Cek dengan locking untuk mencegah race condition
        $canUseCod = DB::transaction(function () use ($agency, $minBalance, $walletService) {
            return $walletService->canUseCod($agency, $minBalance);
        });
        
        if (!$canUseCod) {
            // ✅ TAMBAHKAN: Informasi lebih detail
            $summary = $walletService->getBalanceSummary($agency);
            return back()->with('error', 
                'Saldo jaminan agency tidak mencukupi untuk COD. ' .
                'Dibutuhkan: Rp ' . number_format($minBalance, 0, ',', '.') . ', ' .
                'Tersedia: Rp ' . number_format($summary['available_deposit'], 0, ',', '.')
            );
        }

        // ✅ TAMBAHKAN: Gunakan transaction untuk atomic operation
        try {
            DB::transaction(function () use ($booking, $walletService, $agency, $minBalance) {
                $this->cleanupOldPayments($booking);
                
                \App\Models\Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $booking->total_price,
                    'commission' => $booking->total_price * 0.05,
                    'agency_revenue' => $booking->total_price * 0.95,
                    'payment_type' => 'cod',
                    'status' => \App\Enums\PaymentStatus::COD_PENDING->value,
                ]);
                
                $booking->update(['status' => \App\Enums\BookingStatus::CONFIRMED->value]);
                
                $walletService->holdCodBalance($booking);
            });
        } catch (\Exception $e) {
            \Log::error('COD processing failed: ' . $e->getMessage(), [
                'booking_code' => $booking->booking_code,
                'agency_id' => $agency->id,
            ]);
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
        if (!$promoId) {
            \Log::info('applyPromoFromSession - no promo in session', [
                'booking_id' => $booking->id,
            ]);
            return;
        }

        try {
            $promo = \App\Models\Promo::find($promoId);
            if (!$promo || !$promo->isActiveNow()) {
                session()->forget('selected_promo_' . $booking->id);
                \Log::warning('applyPromoFromSession - promo not active', ['promo_id' => $promoId]);
                return;
            }

            $promoService = app(\App\Services\PromoService::class);
            if (!$promoService->canUsePromo($booking->customer, $promo)) {
                session()->forget('selected_promo_' . $booking->id);
                \Log::warning('applyPromoFromSession - cannot use promo', ['promo_id' => $promoId]);
                return;
            }

            $basePrice = (float) ($booking->base_price ?? $booking->total_price);
            $discount = $promoService->calculateDiscount($promo, $basePrice);

            if ($discount > 0) {
                $newTotal = max(0, (float) $booking->total_price - $discount);

                \Log::info('applyPromoFromSession - applying discount', [
                    'booking_code' => $booking->booking_code,
                    'promo_name' => $promo->name,
                    'base_price' => $basePrice,
                    'current_total' => $booking->total_price,
                    'discount' => $discount,
                    'new_total' => $newTotal,
                ]);

                // ⚡ Update booking dengan harga baru
                $booking->update([
                    'total_price' => $newTotal,
                    'discount_amount' => ((float) ($booking->discount_amount ?? 0)) + $discount,
                ]);

                // ⚡ Catat penggunaan promo
                \App\Models\PromoUsage::create([
                    'promo_id' => $promo->id,
                    'user_id' => $booking->customer_id,
                    'booking_id' => $booking->id,
                    'discount_amount' => $discount,
                ]);

                // ⚡ JANGAN update payment di sini — getSnapToken() akan buat payment baru
            }
        } catch (\Exception $e) {
            \Log::error('Apply promo error: ' . $e->getMessage(), [
                'booking_id' => $booking->id,
                'promo_id' => $promoId,
            ]);
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