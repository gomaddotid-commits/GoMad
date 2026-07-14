<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\VehicleRentalSetting;
use App\Services\RentalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Enums\PaymentStatus;
use Illuminate\View\View;

class RentalController extends Controller
{
    public function __construct(
        private readonly RentalService $rentalService,
    ) {}

    /**
     * Halaman daftar rental customer
     */
    public function index(): View
    {
        $rentals = $this->rentalService->getCustomerRentals(auth()->user());
        return view('customer.rental.index', compact('rentals'));
    }

    /**
     * Halaman browse mobil rental
     */
    public function browse(Request $request): View
    {
        $vehicles = $this->rentalService->getAvailableRentalVehicles(
            $request->only(['type'])
        );

        return view('customer.rental.browse', compact('vehicles'));
    }

    /**
     * Halaman detail & booking rental
     */
    public function create(VehicleRentalSetting $vehicleSetting): View
    {
        $vehicleSetting->load('vehicle.agency');
        $documentStatus = $this->rentalService->getCustomerDocumentStatus(auth()->user());

        return view('customer.rental.create', compact('vehicleSetting', 'documentStatus'));
    }

    /**
     * Proses booking rental
     */
    public function store(Request $request): RedirectResponse
    {
        // Format datetime
        if ($request->start_datetime) {
            $request->merge(['start_datetime' => $this->formatDatetime($request->start_datetime)]);
        }
        if ($request->end_datetime) {
            $request->merge(['end_datetime' => $this->formatDatetime($request->end_datetime)]);
        }

        $rules = [
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'type' => ['required', 'in:self_drive,with_driver'],
            'start_datetime' => ['required', 'date', 'after:now'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'duration_unit' => ['required', 'in:hour,day'],
            'promo_id' => ['nullable', 'integer', 'exists:promos,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        // Jika dengan supir, alamat penjemputan wajib
        if ($request->type === 'with_driver') {
            $rules['pickup_address'] = ['required', 'string', 'max:500'];
            $rules['destination_address'] = ['nullable', 'string', 'max:500'];
            $rules['pickup_maps_link'] = ['nullable', 'url', 'max:500'];
            $rules['destination_maps_link'] = ['nullable', 'url', 'max:500'];
        }

        $request->validate($rules);

        try {
            $data = $request->all();
            $data['customer_id'] = auth()->id();

            $rental = $this->rentalService->createRentalBooking($data);

            return redirect()->route('customer.rental.show', $rental)
                ->with('success', 'Booking rental berhasil! Silakan lakukan pembayaran.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Format datetime dari input HTML ke format MySQL
     */
    private function formatDatetime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Jika sudah format Y-m-d\TH:i (dari datetime-local)
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
            return $value; // Sudah benar
        }

        // Jika format d/m/Y H:i (dari input Indonesia)
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2})[\.:](\d{2})$/', $value, $matches)) {
            return sprintf('%04d-%02d-%02dT%02d:%02d', $matches[3], $matches[2], $matches[1], $matches[4], $matches[5]);
        }

        // Coba parse dengan Carbon
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d\TH:i');
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Halaman detail rental
     */
    public function show(Rental $rental): View
    {
        if ($rental->customer_id !== auth()->id()) {
            abort(403);
        }

        $rental->load(['vehicle.rentalSetting', 'agency', 'payment']);

        return view('customer.rental.show', compact('rental'));
    }

    /**
     * Batalkan rental
     */
    public function cancel(Rental $rental): RedirectResponse
    {
        if ($rental->customer_id !== auth()->id()) {
            abort(403);
        }

        try {
            $this->rentalService->cancelRental($rental);
            
            return redirect()->route('customer.rentals')
                ->with('success', 'Rental berhasil dibatalkan. ' . 
                    ($rental->payment && $rental->payment->status === 'refunded' ? 
                        'Dana akan dikembalikan ke rekening Anda.' : ''));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Halaman upload dokumen
     */
    public function documents(): View
    {
        $documentStatus = $this->rentalService->getCustomerDocumentStatus(auth()->user());
        return view('customer.rental.documents', compact('documentStatus'));
    }

    /**
     * Submit dokumen
     */
    public function submitDocuments(Request $request): RedirectResponse
    {
        $request->validate([
            'ktp_number' => ['required', 'string', 'max:50'],
            'ktp_photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'sim_number' => ['required', 'string', 'max:50'],
            'sim_photo' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'npwp_number' => ['nullable', 'string', 'max:50'],
            'npwp_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        try {
            // Upload foto ke Cloudinary
            $cloudinary = app(\App\Services\CloudinaryService::class);
            
            $data = [
                'ktp_number' => $request->ktp_number,
                'ktp_photo' => $cloudinary->upload($request->file('ktp_photo'), 'documents/ktp')['url'],
                'sim_number' => $request->sim_number,
                'sim_photo' => $cloudinary->upload($request->file('sim_photo'), 'documents/sim')['url'],
                'npwp_number' => $request->npwp_number,
            ];

            if ($request->hasFile('npwp_photo')) {
                $data['npwp_photo'] = $cloudinary->upload($request->file('npwp_photo'), 'documents/npwp')['url'];
            }

            $this->rentalService->submitDocuments(auth()->user(), $data);

            return redirect()->route('customer.rentals')
                ->with('success', 'Dokumen berhasil disubmit! Menunggu verifikasi admin.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Proses pembayaran rental
     */
    public function pay(Request $request, Rental $rental): RedirectResponse
    {
        if ($rental->customer_id !== auth()->id()) {
            abort(403);
        }

        if ($rental->status !== 'pending') {
            return back()->with('error', 'Rental sudah tidak dalam status pending.');
        }

        try {
            // Buat payment record
            $payment = \App\Models\Payment::create([
                'booking_id' => null,
                'rental_id' => $rental->id,
                'amount' => $rental->total_price,
                'commission' => $rental->total_price * 0.05,
                'agency_revenue' => $rental->total_price * 0.95,
                'payment_type' => 'midtrans',
                'status' => \App\Enums\PaymentStatus::PENDING->value,
                'expired_at' => now()->addMinutes(30),
            ]);

            $rental->update(['payment_id' => $payment->id]);

            // Generate Snap Token
            $snapToken = $this->generateRentalSnapToken($rental, $payment);

            return redirect()->route('customer.rental.show', $rental)
                ->with('snap_token', $snapToken)
                ->with('success', 'Silakan selesaikan pembayaran.');
                
        } catch (\Exception $e) {
            \Log::error('Rental Payment Error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Generate Snap Token untuk Midtrans (Rental)
     */
    private function generateRentalSnapToken(Rental $rental, Payment $payment): string
    {
        $serverKey = config('gomad.midtrans.server_key');
        $isProduction = config('gomad.midtrans.is_production', false);
        
        $baseUrl = $isProduction 
            ? 'https://app.midtrans.com/snap/v1/transactions' 
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $payload = [
            'transaction_details' => [
                'order_id' => 'RNTL-' . $rental->id . '-' . time(),
                'gross_amount' => (int) $rental->total_price,
            ],
            'customer_details' => [
                'first_name' => $rental->customer->name,
                'email' => $rental->customer->email,
                'phone' => $rental->customer->phone,
            ],
            'item_details' => [
                [
                    'id' => 'RNTL-' . $rental->id,
                    'price' => (int) $rental->total_price,
                    'quantity' => 1,
                    'name' => 'Rental ' . $rental->vehicle->brand . ' ' . $rental->vehicle->model . ' - ' . $rental->rental_code,
                ],
            ],
            'callbacks' => [
                'finish' => route('customer.rental.show', $rental),
            ],
        ];

        $response = \Illuminate\Support\Facades\Http::withBasicAuth($serverKey, '')
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($baseUrl, $payload);

        if ($response->successful()) {
            $result = $response->json();
            
            $payment->update([
                'payment_detail' => ['snap_response' => $result],
            ]);
            
            return $result['token'] ?? '';
        }

        throw new \Exception('Gagal membuat Snap Token: ' . $response->body());
    }

}