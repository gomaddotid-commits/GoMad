<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function __construct(
        private readonly DeviceService $deviceService,
    ) {}

    // ═══════════════════════════════════════════
    // WHATSAPP - MULTI DRIVER
    // ═══════════════════════════════════════════

    /**
     * Kirim WhatsApp dengan driver yang aktif
     */
    public function sendWhatsApp(?string $phone, string $message): void
    {
        if (empty($phone)) {
            Log::warning('🔕 WHATSAPP SKIPPED: Empty phone number', [
                'message_preview' => \Illuminate\Support\Str::limit($message, 80),
            ]);
            return;
        }

        $phone = $this->normalizePhone($phone);
        $driver = $this->getWhatsAppDriver();

        Log::info('📤 WHATSAPP SENDING', [
            'driver' => $driver,
            'to' => $phone,
            'message_preview' => \Illuminate\Support\Str::limit(strip_tags($message), 100),
            'timestamp' => now()->toISOString(),
        ]);

        match ($driver) {
            'baileys' => $this->sendViaBaileys($phone, $message),
            'fonnte' => $this->sendViaFonnte($phone, $message),
            'meta'   => $this->sendViaMeta($phone, $message),
            'twilio' => $this->sendViaTwilio($phone, $message),
            default  => $this->sendViaLog($phone, $message),
        };
    }

    /**
     * Normalize nomor HP ke format internasional (62xxx)
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }

    /**
     * Get WhatsApp driver yang aktif
     */
    private function getWhatsAppDriver(): string
    {
        $settingDriver = \App\Models\PlatformSetting::getValue('whatsapp_driver');
        if (!empty($settingDriver) && in_array($settingDriver, ['log', 'fonnte', 'meta', 'twilio', 'baileys'])) {
            return $settingDriver;
        }
        return config('gomad.whatsapp.driver', 'log');
    }

    // ═══════════════════════════════════
    // DRIVER: BAILEYS (Microservice)
    // ═══════════════════════════════════

    private function sendViaBaileys(string $phone, string $message): void
    {
        $apiUrl = config('gomad.whatsapp.baileys.api_url');
        $apiKey = config('gomad.whatsapp.baileys.api_key');

        if (empty($apiUrl) || empty($apiKey)) {
            Log::warning('Baileys: Not configured. Message skipped.', ['to' => $phone]);
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-API-Key' => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(5)
            ->connectTimeout(3)
            ->post("{$apiUrl}/send", [
                'phone' => $phone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info("📤 WHATSAPP SENT [baileys] ✅", ['to' => $phone]);
            } else {
                Log::warning("📤 WHATSAPP FAILED [baileys] ❌", [
                    'to' => $phone,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("📤 WHATSAPP FAILED [baileys] ❌", [
                'to' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ═══════════════════════════════════
    // DRIVER: FONNTE (Recommended)
    // ═══════════════════════════════════

    private function sendViaFonnte(string $phone, string $message): void
    {
        $token = config('gomad.whatsapp.fonnte.token');
        $apiUrl = config('gomad.whatsapp.fonnte.api_url');

        if (empty($token)) {
            Log::warning('Fonnte: Token not configured. Message skipped.', ['to' => $phone]);
            return;
        }

        try {
            $response = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout(5)
                ->connectTimeout(3)
                ->post("{$apiUrl}/send", [
                    'target' => $phone,
                    'message' => $message,
                    'delay' => '2',
                    'countryCode' => '62',
                ]);

            if ($response->successful()) {
                Log::info("📤 WHATSAPP SENT [fonnte] ✅", ['to' => $phone]);
            } else {
                Log::warning("📤 WHATSAPP FAILED [fonnte] ❌", [
                    'to' => $phone,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("📤 WHATSAPP FAILED [fonnte] ❌", [
                'to' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ═══════════════════════════════════
    // DRIVER: META WHATSAPP CLOUD API
    // ═══════════════════════════════════

    private function sendViaMeta(string $phone, string $message): void
    {
        $phoneNumberId = config('gomad.whatsapp.meta.phone_number_id');
        $accessToken = config('gomad.whatsapp.meta.access_token');
        $apiUrl = config('gomad.whatsapp.meta.api_url');

        if (empty($phoneNumberId) || empty($accessToken)) {
            Log::warning('Meta: Credentials not configured. Message skipped.', ['to' => $phone]);
            return;
        }

        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $phone,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message,
                ],
            ];

            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout(5)
                ->connectTimeout(3)
                ->post("{$apiUrl}/{$phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                Log::info("📤 WHATSAPP SENT [meta] ✅", ['to' => $phone]);
            } else {
                Log::warning("📤 WHATSAPP FAILED [meta] ❌", [
                    'to' => $phone,
                    'status' => $response->status(),
                    'error' => $response->json('error.message') ?? 'Unknown',
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("📤 WHATSAPP FAILED [meta] ❌", [
                'to' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ═══════════════════════════════════
    // DRIVER: TWILIO (Legacy)
    // ═══════════════════════════════════

    private function sendViaTwilio(string $phone, string $message): void
    {
        $sid = config('gomad.whatsapp.twilio.sid');
        $token = config('gomad.whatsapp.twilio.auth_token');
        $from = config('gomad.whatsapp.twilio.from');

        if (empty($sid) || empty($token) || empty($from)) {
            Log::warning('Twilio: Credentials not configured. Message skipped.', ['to' => $phone]);
            return;
        }

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->timeout(5)
                ->connectTimeout(3)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => "whatsapp:+{$from}",
                    'To' => "whatsapp:+{$phone}",
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                Log::info("📤 WHATSAPP SENT [twilio] ✅", ['to' => $phone]);
            } else {
                Log::warning("📤 WHATSAPP FAILED [twilio] ❌", [
                    'to' => $phone,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("📤 WHATSAPP FAILED [twilio] ❌", [
                'to' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ═══════════════════════════════════
    // DRIVER: LOG (Development)
    // ═══════════════════════════════════

    private function sendViaLog(string $phone, string $message): void
    {
        Log::info('📤 WHATSAPP SENT [log] ✅', [
            'to' => $phone,
            'message_preview' => \Illuminate\Support\Str::limit($message, 100),
        ]);
    }

    // ═══════════════════════════════════════════
    // EMAIL
    // ═══════════════════════════════════════════

    /**
     * Kirim email dengan error handling dan logging
     */
    private function sendEmail(string $email, \Illuminate\Mail\Mailable $mailable, string $label = 'Email'): void
    {
        if (empty($email)) {
            Log::warning("🔕 EMAIL SKIPPED: Empty email address", [
                'label' => $label,
            ]);
            return;
        }

        Log::info('📧 EMAIL SENDING', [
            'label' => $label,
            'to' => $email,
            'mailable_class' => get_class($mailable),
            'subject' => $mailable->envelope()->subject ?? 'N/A',
            'timestamp' => now()->toISOString(),
        ]);

        try {
            Mail::to($email)->send($mailable);
            
            Log::info('📧 EMAIL SENT ✅', [
                'label' => $label,
                'to' => $email,
                'status' => 'success',
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('📧 EMAIL FAILED ❌', [
                'label' => $label,
                'to' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    // ═══════════════════════════════════════════
    // PUSH NOTIFICATION (FCM)
    // ═══════════════════════════════════════════

    public function sendPushNotification(User $user, string $title, string $body, array $data = []): void
    {
        Log::info('🔔 PUSH NOTIFICATION SENDING', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'title' => $title,
            'body' => \Illuminate\Support\Str::limit($body, 100),
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]);

        $this->createNotification($user->id, $title, $body, $data);
        $this->deviceService->sendToUser($user, $title, $body, $data);

        Log::info('🔔 PUSH NOTIFICATION SENT ✅', [
            'user_id' => $user->id,
            'status' => 'dispatched',
        ]);
    }

    // ═══════════════════════════════════════════
    // IN-APP NOTIFICATION
    // ═══════════════════════════════════════════

    public function createNotification(int $userId, string $title, string $body, array $data = []): Notification
    {
        Log::info('📬 IN-APP NOTIFICATION CREATED', [
            'user_id' => $userId,
            'title' => $title,
            'body' => \Illuminate\Support\Str::limit($body, 100),
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]);

        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    // ═══════════════════════════════════════════
    // BUSINESS NOTIFICATIONS
    // ═══════════════════════════════════════════

    public function bookingCreated(\App\Models\Booking $booking): void
    {
        $customer = $booking->customer;
        $schedule = $booking->schedule;

        Log::info('🎫 NOTIFICATION: bookingCreated START', [
            'booking_code' => $booking->booking_code,
            'customer_id' => $customer->id,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
        ]);

        // In-app notification
        $this->createNotification(
            $customer->id,
            'Booking Berhasil',
            "Booking {$booking->booking_code} berhasil dibuat. Silakan lakukan pembayaran.",
            ['type' => 'booking_created', 'booking_id' => $booking->id]
        );

        // WhatsApp ke customer
        if ($customer->phone) {
            $this->sendWhatsApp(
                $customer->phone,
                "Halo {$customer->name},\n\n" .
                "🎫 Booking GoMad *{$booking->booking_code}* berhasil dibuat.\n\n" .
                "📅 Rute: {$booking->originStop->city_name} → {$booking->destinationStop->city_name}\n" .
                "🕐 Tanggal: {$schedule->departure_date->format('d M Y')} {$schedule->departure_time}\n" .
                "💰 Total: Rp " . number_format($booking->total_price, 0, ',', '.') . "\n\n" .
                "Segera lakukan pembayaran untuk konfirmasi booking."
            );
        } else {
            Log::info('🔕 NOTIFICATION: bookingCreated - No customer phone, WhatsApp skipped');
        }

        // Email ke customer
        if ($customer->email) {
            $this->sendEmail(
                $customer->email,
                new \App\Mail\BookingCreatedMail($booking),
                'Booking Created'
            );
        } else {
            Log::info('🔕 NOTIFICATION: bookingCreated - No customer email, Email skipped');
        }

        // WhatsApp ke agency
        $agencyUser = $booking->schedule->agency->user ?? null;
        if ($agencyUser && $agencyUser->phone) {
            $this->sendWhatsApp(
                $agencyUser->phone,
                "📋 *Booking Baru!*\n\n" .
                "Kode: *{$booking->booking_code}*\n" .
                "Customer: {$customer->name}\n" .
                "Rute: {$booking->originStop->city_name} → {$booking->destinationStop->city_name}\n" .
                "Tanggal: {$schedule->departure_date->format('d M Y')} {$schedule->departure_time}\n" .
                "Penumpang: {$booking->total_passengers} orang\n" .
                "Total: Rp " . number_format($booking->total_price, 0, ',', '.') . "\n" .
                "Status: *Menunggu Pembayaran*\n\n" .
                "Cek dashboard untuk detail."
            );
        }

        // Push notification
        $this->sendPushNotification(
            $customer,
            'Booking Berhasil',
            "Booking {$booking->booking_code} berhasil dibuat.",
            ['type' => 'booking_created', 'booking_id' => $booking->id]
        );

        Log::info('🎫 NOTIFICATION: bookingCreated COMPLETED', [
            'booking_code' => $booking->booking_code,
        ]);
    }

    public function paymentConfirmed(\App\Models\Booking $booking): void
    {
        $customer = $booking->customer;

        Log::info('💰 NOTIFICATION: paymentConfirmed START', [
            'booking_code' => $booking->booking_code,
            'customer_id' => $customer->id,
        ]);

        // In-app notification
        $this->createNotification(
            $customer->id,
            'Pembayaran Berhasil',
            "Pembayaran booking {$booking->booking_code} telah dikonfirmasi.",
            ['type' => 'payment_confirmed', 'booking_id' => $booking->id]
        );

        // WhatsApp ke customer
        if ($customer->phone) {
            $this->sendWhatsApp(
                $customer->phone,
                "✅ Pembayaran untuk booking *{$booking->booking_code}* telah dikonfirmasi.\n\n" .
                "E-Ticket dapat diunduh di aplikasi GoMad."
            );
        }

        // Email ke customer
        if ($customer->email) {
            $this->sendEmail(
                $customer->email,
                new \App\Mail\PaymentConfirmedMail($booking),
                'Payment Confirmed'
            );
        }

        // Notifikasi ke agency
        $agencyUser = $booking->schedule->agency->user;
        if ($agencyUser) {
            $this->createNotification(
                $agencyUser->id,
                'Booking Baru Dibayar',
                "Booking {$booking->booking_code} telah dibayar.",
                ['type' => 'new_paid_booking', 'booking_id' => $booking->id]
            );

            if ($agencyUser->phone) {
                $this->sendWhatsApp(
                    $agencyUser->phone,
                    "📋 *Booking Dibayar!*\n\n" .
                    "Kode: *{$booking->booking_code}*\n" .
                    "Customer: {$customer->name}\n" .
                    "Total: Rp " . number_format($booking->total_price, 0, ',', '.')
                );
            }
        }

        // Push notification
        $this->sendPushNotification(
            $customer,
            'Pembayaran Berhasil',
            "Pembayaran {$booking->booking_code} dikonfirmasi.",
            ['type' => 'payment_confirmed', 'booking_id' => $booking->id]
        );

        Log::info('💰 NOTIFICATION: paymentConfirmed COMPLETED');
    }

    public function cashPaymentConfirmed(\App\Models\Booking $booking): void
    {
        $customer = $booking->customer;

        Log::info('💵 NOTIFICATION: cashPaymentConfirmed START', [
            'booking_code' => $booking->booking_code,
            'customer_id' => $customer->id,
        ]);

        // In-app notification
        $this->createNotification(
            $customer->id,
            'Pembayaran Berhasil',
            "Pembayaran booking {$booking->booking_code} telah dikonfirmasi.",
            ['type' => 'payment_confirmed', 'booking_id' => $booking->id]
        );

        // WhatsApp ke customer
        if ($customer->phone) {
            $this->sendWhatsApp(
                $customer->phone,
                "✅ Pembayaran untuk booking *{$booking->booking_code}* telah dikonfirmasi.\n\n" .
                "E-Ticket dapat diunduh di aplikasi GoMad."
            );
        }

        // Email ke customer
        if ($customer->email) {
            $this->sendEmail(
                $customer->email,
                new \App\Mail\PaymentConfirmedMail($booking),
                'Cash Payment Confirmed'
            );
        }

        Log::info('💵 NOTIFICATION: cashPaymentConfirmed COMPLETED');
    }

    public function bookingCancelled(\App\Models\Booking $booking, string $reason): void
    {
        $customer = $booking->customer;

        Log::info('❌ NOTIFICATION: bookingCancelled START', [
            'booking_code' => $booking->booking_code,
            'customer_id' => $customer->id,
            'reason' => $reason,
        ]);

        // In-app notification
        $this->createNotification(
            $customer->id,
            'Booking Dibatalkan',
            "Booking {$booking->booking_code} telah dibatalkan.",
            ['type' => 'booking_cancelled', 'booking_id' => $booking->id]
        );

        // WhatsApp ke customer
        if ($customer->phone) {
            $this->sendWhatsApp(
                $customer->phone,
                "❌ Booking *{$booking->booking_code}* telah dibatalkan.\n" .
                "Alasan: {$reason}"
            );
        }

        // Email ke customer
        if ($customer->email) {
            $this->sendEmail(
                $customer->email,
                new \App\Mail\BookingCancelledMail($booking, $reason),
                'Booking Cancelled'
            );
        }

        // Push notification
        $this->sendPushNotification(
            $customer,
            'Booking Dibatalkan',
            "Booking {$booking->booking_code} dibatalkan.",
            ['type' => 'booking_cancelled', 'booking_id' => $booking->id]
        );

        Log::info('❌ NOTIFICATION: bookingCancelled COMPLETED');
    }

    public function bookingCompleted(\App\Models\Booking $booking): void
    {
        $customer = $booking->customer;

        Log::info('✅ NOTIFICATION: bookingCompleted START', [
            'booking_code' => $booking->booking_code,
            'customer_id' => $customer->id,
        ]);

        // In-app notification
        $this->createNotification(
            $customer->id,
            'Perjalanan Selesai',
            "Perjalanan {$booking->booking_code} telah selesai.",
            ['type' => 'booking_completed', 'booking_id' => $booking->id]
        );

        // WhatsApp ke customer
        if ($customer->phone) {
            $this->sendWhatsApp(
                $customer->phone,
                "🎉 Perjalanan GoMad *{$booking->booking_code}* telah selesai.\n" .
                "Terima kasih! Beri ulasan untuk membantu traveler lain."
            );
        }

        Log::info('✅ NOTIFICATION: bookingCompleted COMPLETED');
    }

    public function driverAssigned(\App\Models\Schedule $schedule, \App\Models\User $driver): void
    {
        Log::info('👨‍✈️ NOTIFICATION: driverAssigned START', [
            'schedule_id' => $schedule->id,
            'driver_id' => $driver->id,
        ]);

        // In-app notification
        $this->createNotification(
            $driver->id,
            'Jadwal Baru',
            "Anda ditugaskan jadwal {$schedule->route->route_name}.",
            ['type' => 'driver_assigned', 'schedule_id' => $schedule->id]
        );

        // WhatsApp ke driver
        if ($driver->phone) {
            $this->sendWhatsApp(
                $driver->phone,
                "👨‍✈️ *Jadwal Baru!*\n\n" .
                "📅 Tanggal: {$schedule->departure_date->format('d M Y')}\n" .
                "🕐 Jam: {$schedule->departure_time}\n" .
                "🚗 Rute: {$schedule->route->route_name}\n" .
                "🚙 Kendaraan: {$schedule->vehicle->plate_number}"
            );
        }

        // Push notification
        $this->sendPushNotification(
            $driver,
            'Jadwal Baru',
            "Anda ditugaskan jadwal {$schedule->route->route_name}.",
            ['type' => 'driver_assigned', 'schedule_id' => $schedule->id]
        );

        Log::info('👨‍✈️ NOTIFICATION: driverAssigned COMPLETED');
    }

    public function agencyVerified(\App\Models\Agency $agency): void
    {
        $user = $agency->user;
        if (!$user) {
            Log::warning('🔕 NOTIFICATION: agencyVerified - No user found');
            return;
        }

        Log::info('✅ NOTIFICATION: agencyVerified START', [
            'agency_id' => $agency->id,
            'user_id' => $user->id,
        ]);

        // In-app notification
        $this->createNotification(
            $user->id,
            'Agency Terverifikasi',
            "Agency {$agency->agency_name} telah terverifikasi!",
            ['type' => 'agency_verified', 'agency_id' => $agency->id]
        );

        // WhatsApp ke agency owner
        if ($user->phone) {
            $this->sendWhatsApp(
                $user->phone,
                "🎉 Selamat! Agency *{$agency->agency_name}* telah *TERVERIFIKASI*!\n\n" .
                "Anda sekarang dapat membuat jadwal dan menerima booking."
            );
        }

        Log::info('✅ NOTIFICATION: agencyVerified COMPLETED');
    }

    public function agencyRejected(\App\Models\Agency $agency, string $reason): void
    {
        $user = $agency->user;
        if (!$user) return;

        Log::info('❌ NOTIFICATION: agencyRejected START', [
            'agency_id' => $agency->id,
            'reason' => $reason,
        ]);

        // WhatsApp ke agency owner
        if ($user->phone) {
            $this->sendWhatsApp(
                $user->phone,
                "❌ Maaf, pengajuan verifikasi agency *{$agency->agency_name}* ditolak.\n\n" .
                "📝 *Alasan:* {$reason}\n\n" .
                "Silakan perbaiki data dan ajukan kembali."
            );
        }

        Log::info('❌ NOTIFICATION: agencyRejected COMPLETED');
    }

    public function scheduleReminder(\App\Models\Schedule $schedule): void
    {
        Log::info('⏰ NOTIFICATION: scheduleReminder START', [
            'schedule_id' => $schedule->id,
        ]);

        $bookings = $schedule->bookings()
            ->whereIn('status', ['paid', 'confirmed'])
            ->with('customer')
            ->get();

        foreach ($bookings as $booking) {
            if ($booking->customer->phone) {
                $this->sendWhatsApp(
                    $booking->customer->phone,
                    "⏰ *PENGINGAT JADWAL*\n\n" .
                    "Besok jadwal keberangkatan GoMad Anda:\n" .
                    "📅 {$schedule->departure_date->format('d M Y')} {$schedule->departure_time}\n" .
                    "📍 Jemput: {$booking->pickup_address}\n" .
                    "🚗 Kendaraan: {$schedule->vehicle->plate_number}\n" .
                    "Kode: {$booking->booking_code}"
                );

                $this->sendPushNotification(
                    $booking->customer,
                    'Pengingat Jadwal',
                    "Besok keberangkatan Anda. Kode: {$booking->booking_code}",
                    ['type' => 'schedule_reminder', 'booking_id' => $booking->id]
                );
            }
        }

        if ($schedule->driver && $schedule->driver->phone) {
            $this->sendWhatsApp(
                $schedule->driver->phone,
                "⏰ *PENGINGAT JADWAL*\n\n" .
                "Besok Anda bertugas:\n" .
                "📅 {$schedule->departure_date->format('d M Y')} {$schedule->departure_time}\n" .
                "🚗 {$schedule->route->route_name}\n" .
                "🚙 {$schedule->vehicle->plate_number}"
            );
        }

        Log::info('⏰ NOTIFICATION: scheduleReminder COMPLETED');
    }

    public function settlementGenerated(\App\Models\Settlement $settlement): void
    {
        $agent = $settlement->paymentAgent;
        if ($agent && $agent->owner_phone) {
            Log::info('📋 NOTIFICATION: settlementGenerated START', [
                'settlement_id' => $settlement->id,
            ]);

            $this->sendWhatsApp(
                $agent->owner_phone,
                "📋 *Settlement Mingguan*\n\n" .
                "Periode: {$settlement->period_start->format('d M')} - {$settlement->period_end->format('d M Y')}\n" .
                "Total: Rp " . number_format($settlement->amount_to_settle, 0, ',', '.') . "\n\n" .
                "Silakan lakukan pembayaran sebelum jatuh tempo."
            );

            Log::info('📋 NOTIFICATION: settlementGenerated COMPLETED');
        }
    }

    public function settlementPaid(\App\Models\Settlement $settlement): void
    {
        $agent = $settlement->paymentAgent;
        if ($agent && $agent->owner_phone) {
            $this->sendWhatsApp(
                $agent->owner_phone,
                "✅ *Pembayaran Settlement Diterima*\n\n" .
                "Periode: {$settlement->period_start->format('d M')} - {$settlement->period_end->format('d M Y')}\n" .
                "Total: Rp " . number_format($settlement->amount_to_settle, 0, ',', '.') . "\n\n" .
                "Menunggu verifikasi admin."
            );
        }
    }

    public function withdrawalApproved(\App\Models\Withdrawal $withdrawal): void
    {
        $agency = $withdrawal->agency;
        if ($agency && $agency->user && $agency->user->phone) {
            $this->sendWhatsApp(
                $agency->user->phone,
                "✅ *Penarikan Disetujui*\n\n" .
                "Jumlah: Rp " . number_format($withdrawal->amount, 0, ',', '.') . "\n" .
                "Bank: {$withdrawal->bank_name}\n" .
                "Dana akan diproses dalam 1-3 hari kerja."
            );
        }
    }

    public function withdrawalRejected(\App\Models\Withdrawal $withdrawal, string $reason): void
    {
        $agency = $withdrawal->agency;
        if ($agency && $agency->user && $agency->user->phone) {
            $this->sendWhatsApp(
                $agency->user->phone,
                "❌ *Penarikan Ditolak*\n\n" .
                "Jumlah: Rp " . number_format($withdrawal->amount, 0, ',', '.') . "\n" .
                "Alasan: {$reason}\n\n" .
                "Dana telah dikembalikan ke saldo."
            );
        }
    }

    public function rentalDriverAssigned(\App\Models\Rental $rental): void
    {
        $driver = $rental->driver;
        $customer = $rental->customer;

        if (!$driver || !$customer) return;

        if ($customer->phone) {
            $this->sendWhatsApp(
                $customer->phone,
                "👨‍✈️ *Supir Telah Ditugaskan!*\n\n" .
                "Kode Rental: *{$rental->rental_code}*\n" .
                "Supir: *{$driver->name}*\n" .
                "Telepon: *{$driver->phone}*\n" .
                "Mobil: {$rental->vehicle->plate_number}\n\n" .
                "Supir akan menjemput di: {$rental->pickup_address}"
            );
        }

        if ($driver->phone) {
            $this->sendWhatsApp(
                $driver->phone,
                "🔔 *Tugas Rental Baru!*\n\n" .
                "Kode: *{$rental->rental_code}*\n" .
                "Customer: *{$customer->name}*\n" .
                "Telepon: *{$customer->phone}*\n" .
                "Mobil: {$rental->vehicle->plate_number}\n" .
                "Jemput di: {$rental->pickup_address}\n" .
                "Tanggal: {$rental->start_datetime->format('d M Y H:i')}"
            );
        }
    }

    public function overloadWarning(\App\Models\Schedule $schedule): void
    {
        $agencyUser = $schedule->agency->user;
        if ($agencyUser && $agencyUser->phone) {
            $this->sendWhatsApp(
                $agencyUser->phone,
                "⚠️ *PERINGATAN OVERLOAD*\n\n" .
                "Rute: {$schedule->route->route_name}\n" .
                "Tanggal: {$schedule->departure_date->format('d M Y')}\n" .
                "Okupansi: {$schedule->occupancy_rate}%\n\n" .
                "Pertimbangkan untuk menambah jadwal atau transfer penumpang."
            );
        }
    }

    // ═══════════════════════════════════════════
    // WELCOME NOTIFICATIONS
    // ═══════════════════════════════════════════

    public function welcomeCustomer(\App\Models\User $user): void
    {
        Log::info('👋 NOTIFICATION: welcomeCustomer START', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_phone' => $user->phone,
        ]);

        // WhatsApp
        if ($user->phone) {
            $message = "🎉 *Selamat Datang di GoMad, {$user->name}!*\n\n" .
                "Nomor WhatsApp Anda *{$user->phone}* telah terhubung dengan akun GoMad.\n\n" .
                "📱 *Yang bisa Anda lakukan:*\n" .
                "✅ Booking travel antar kota (door-to-door)\n" .
                "✅ Sewa mobil lepas kunci atau dengan supir\n" .
                "✅ Bayar online atau di Warung GoMad terdekat\n" .
                "✅ Lacak booking & dapatkan E-Ticket\n\n" .
                "🔗 Login di: " . config('app.url') . "\n" .
                "📞 Support: " . config('gomad.support_phone', '081234567890') . "\n\n" .
                "Selamat bepergian! 🚐🚗";

            $this->sendWhatsApp($user->phone, $message);
        } else {
            Log::info('🔕 NOTIFICATION: welcomeCustomer - No phone, WhatsApp skipped');
        }

        // Email Welcome Customer
        if ($user->email) {
            $this->sendEmail(
                $user->email,
                new \App\Mail\WelcomeCustomerMail($user),
                'Welcome Customer'
            );
        } else {
            Log::info('🔕 NOTIFICATION: welcomeCustomer - No email, Email skipped');
        }

        // In-app notification
        $this->createNotification(
            $user->id,
            '🎉 Selamat Datang di GoMad!',
            "Halo {$user->name}! Nomor WhatsApp Anda telah terhubung. Jelajahi layanan travel & rental kami.",
            ['type' => 'welcome', 'action' => 'home']
        );

        Log::info('👋 NOTIFICATION: welcomeCustomer COMPLETED');
    }

    public function welcomeAgency(\App\Models\User $user, \App\Models\Agency $agency): void
    {
        Log::info('🏢 NOTIFICATION: welcomeAgency START', [
            'user_id' => $user->id,
            'agency_id' => $agency->id,
        ]);

        // WhatsApp
        if ($user->phone) {
            $message = "🏢 *Selamat Datang di GoMad, {$agency->agency_name}!*\n\n" .
                "Nomor WhatsApp *{$user->phone}* telah terhubung dengan akun Agency.\n\n" .
                "📋 *Langkah selanjutnya:*\n" .
                "1️⃣ Lengkapi profil agency\n" .
                "2️⃣ Upload dokumen verifikasi\n" .
                "3️⃣ Tambah kendaraan & driver\n" .
                "4️⃣ Buat jadwal perjalanan\n" .
                "5️⃣ Setup kendaraan rental\n\n" .
                "🔗 Dashboard: " . config('app.url') . "/agency/dashboard\n" .
                "📞 Support: " . config('gomad.support_phone', '081234567890') . "\n\n" .
                "Admin akan memverifikasi agency Anda dalam 1-3 hari kerja.";

            $this->sendWhatsApp($user->phone, $message);
        }

        // Email Welcome Agency
        if ($user->email) {
            $this->sendEmail(
                $user->email,
                new \App\Mail\WelcomeAgencyMail($user, $agency),
                'Welcome Agency'
            );
        }

        // In-app notification
        $this->createNotification(
            $user->id,
            '🏢 Selamat Datang di GoMad Agency!',
            "Lengkapi profil dan upload dokumen untuk verifikasi.",
            ['type' => 'welcome_agency', 'action' => 'agency_setup']
        );

        Log::info('🏢 NOTIFICATION: welcomeAgency COMPLETED');
    }

    public function welcomeDriver(\App\Models\User $user): void
    {
        Log::info('👨‍✈️ NOTIFICATION: welcomeDriver START', [
            'user_id' => $user->id,
        ]);

        $agency = $user->agencyBelongTo;

        // WhatsApp
        if ($user->phone) {
            $message = "👨‍✈️ *Selamat Datang di GoMad Driver, {$user->name}!*\n\n" .
                "Nomor WhatsApp *{$user->phone}* telah terhubung dengan akun Driver.\n\n" .
                ($agency ? "🏢 Agency: *{$agency->agency_name}*\n\n" : "") .
                "📱 *Yang bisa Anda lakukan:*\n" .
                "✅ Lihat jadwal perjalanan\n" .
                "✅ Kelola penumpang (jemput & antar)\n" .
                "✅ Konfirmasi pembayaran COD\n" .
                "✅ Lacak perjalanan\n\n" .
                "🔗 Login di: " . config('app.url') . "/driver/schedule\n" .
                "📞 Support: " . config('gomad.support_phone', '081234567890');

            $this->sendWhatsApp($user->phone, $message);
        }

        // Email Welcome Driver
        if ($user->email) {
            $this->sendEmail(
                $user->email,
                new \App\Mail\WelcomeDriverMail($user),
                'Welcome Driver'
            );
        }

        // In-app notification
        $this->createNotification(
            $user->id,
            '👨‍✈️ Selamat Datang di GoMad Driver!',
            "Anda telah terdaftar sebagai driver" . ($agency ? " di {$agency->agency_name}" : "") . ".",
            ['type' => 'welcome_driver', 'action' => 'driver_schedule']
        );

        Log::info('👨‍✈️ NOTIFICATION: welcomeDriver COMPLETED');
    }

    public function welcomePaymentAgent(\App\Models\User $user, \App\Models\PaymentAgent $agent): void
    {
        Log::info('🏪 NOTIFICATION: welcomePaymentAgent START', [
            'user_id' => $user->id,
            'agent_id' => $agent->id,
        ]);

        // WhatsApp
        if ($user->phone) {
            $message = "🏪 *Selamat Datang di GoMad Warung, {$agent->agent_name}!*\n\n" .
                "Nomor WhatsApp *{$user->phone}* telah terhubung dengan akun Warung GoMad.\n\n" .
                "📋 *Langkah selanjutnya:*\n" .
                "1️⃣ Lengkapi profil warung\n" .
                "2️⃣ Tunggu verifikasi admin (1-3 hari)\n" .
                "3️⃣ Setelah diverifikasi, Anda bisa:\n" .
                "   ✅ Terima pembayaran customer\n" .
                "   ✅ Konfirmasi dengan kode bayar + PIN\n" .
                "   ✅ Lihat riwayat transaksi\n" .
                "   ✅ Settlement mingguan\n\n" .
                "💰 Komisi: *{$agent->commission_rate}%* per transaksi\n" .
                "🔗 Dashboard: " . config('app.url') . "/payment-agent/dashboard\n" .
                "📞 Support: " . config('gomad.support_phone', '081234567890');

            $this->sendWhatsApp($user->phone, $message);
        }

        // Email Welcome Payment Agent
        if ($user->email) {
            $this->sendEmail(
                $user->email,
                new \App\Mail\WelcomePaymentAgentMail($user, $agent),
                'Welcome Payment Agent'
            );
        }

        // In-app notification
        $this->createNotification(
            $user->id,
            '🏪 Selamat Datang di GoMad Warung!',
            "Lengkapi profil dan tunggu verifikasi admin.",
            ['type' => 'welcome_agent', 'action' => 'payment_agent_setup']
        );

        Log::info('🏪 NOTIFICATION: welcomePaymentAgent COMPLETED');
    }
}