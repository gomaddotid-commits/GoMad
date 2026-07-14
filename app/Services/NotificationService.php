<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    public function sendWhatsApp(string $phone, string $message): void
    {
        if (empty($phone)) {
            Log::warning('WhatsApp: Empty phone number, message skipped.');
            return;
        }

        $phone = $this->normalizePhone($phone);
        $driver = $this->getWhatsAppDriver();

        Log::info("WhatsApp: Sending via [{$driver}] to {$phone}");

        match ($driver) {
            'baileys' => $this->sendViaBaileys($phone, $message),  // 👈 Tambah ini
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
     * Prioritas: PlatformSetting > config > 'log'
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
            Log::warning('Baileys: Not configured. Using log fallback.');
            $this->sendViaLog($phone, $message);
            return;
        }

        // Retry 2x kalau timeout
        $retries = 2;
        $attempt = 0;

        while ($attempt <= $retries) {
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'X-API-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->timeout(10) // 👈 Kurangi timeout dari 30 ke 10
                ->connectTimeout(5)
                ->post("{$apiUrl}/send", [
                    'phone' => $phone,
                    'message' => $message,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    Log::info("Baileys: ✅ Sent to {$phone}");
                    return;
                }
                
                Log::error('Baileys: ❌ Failed', ['status' => $response->status()]);
                return;

            } catch (\Exception $e) {
                $attempt++;
                if ($attempt <= $retries) {
                    Log::warning("Baileys: Retry {$attempt}/{$retries} for {$phone}");
                    sleep(2);
                } else {
                    Log::error('Baileys: ❌ All retries failed', [
                        'to' => $phone,
                        'error' => $e->getMessage(),
                    ]);
                    // Fallback ke log
                    $this->sendViaLog($phone, $message);
                }
            }
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
            Log::warning('Fonnte: Token not configured. Falling back to log.', ['to' => $phone]);
            $this->sendViaLog($phone, $message);
            return;
        }

        try {
            $response = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->timeout(10)
                ->post("{$apiUrl}/send", [
                    'target' => $phone,
                    'message' => $message,
                    'delay' => '2',
                    'countryCode' => '62',
                ]);

            $body = $response->json();

            if ($response->successful() && ($body['status'] ?? false)) {
                Log::info("Fonnte: ✅ Message sent to {$phone}");
            } else {
                Log::error('Fonnte: ❌ Send failed', [
                    'to' => $phone,
                    'status' => $response->status(),
                    'body' => $body,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Fonnte: ❌ Exception', [
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
            Log::warning('Meta: Credentials not configured. Falling back to log.', ['to' => $phone]);
            $this->sendViaLog($phone, $message);
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
                ->timeout(10)
                ->post("{$apiUrl}/{$phoneNumberId}/messages", $payload);

            $body = $response->json();

            if ($response->successful()) {
                Log::info("Meta: ✅ Message sent to {$phone}", ['wa_id' => $body['messages'][0]['id'] ?? 'unknown']);
            } else {
                $errorMsg = $body['error']['message'] ?? 'Unknown error';
                Log::error('Meta: ❌ Send failed', [
                    'to' => $phone,
                    'status' => $response->status(),
                    'error' => $errorMsg,
                ]);

                // Fallback ke log jika butuh template
                if (str_contains($errorMsg, 'template')) {
                    Log::warning('Meta: Template required for new chats. Message logged.', [
                        'to' => $phone,
                        'message' => $message,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Meta: ❌ Exception', [
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
            Log::warning('Twilio: Credentials not configured. Falling back to log.', ['to' => $phone]);
            $this->sendViaLog($phone, $message);
            return;
        }

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->timeout(10)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => "whatsapp:+{$from}",
                    'To' => "whatsapp:+{$phone}",
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                Log::info("Twilio: ✅ Message sent to {$phone}");
            } else {
                Log::error('Twilio: ❌ Send failed', [
                    'to' => $phone,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Twilio: ❌ Exception', [
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
        Log::info('WhatsApp [LOG Driver]', [
            'to' => $phone,
            'message' => $message,
        ]);

        if (app()->environment('local')) {
            Log::info('📱 WhatsApp Simulation', [
                'to' => $phone,
                'message_preview' => \Illuminate\Support\Str::limit($message, 100),
            ]);
        }
    }

    // ═══════════════════════════════════════════
    // PUSH NOTIFICATION (FCM)
    // ═══════════════════════════════════════════

    public function sendPushNotification(User $user, string $title, string $body, array $data = []): void
    {
        $this->createNotification($user->id, $title, $body, $data);
        $this->deviceService->sendToUser($user, $title, $body, $data);
    }

    // ═══════════════════════════════════════════
    // IN-APP NOTIFICATION
    // ═══════════════════════════════════════════

    public function createNotification(int $userId, string $title, string $body, array $data = []): Notification
    {
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

        // In-app notification
        $this->createNotification(
            $customer->id,
            'Booking Berhasil',
            "Booking {$booking->booking_code} berhasil dibuat. Silakan lakukan pembayaran.",
            ['type' => 'booking_created', 'booking_id' => $booking->id]
        );

        // WhatsApp ke customer
        $this->sendWhatsApp(
            $customer->phone,
            "Halo {$customer->name},\n\n" .
            "🎫 Booking GoMad *{$booking->booking_code}* berhasil dibuat.\n\n" .
            "📅 Rute: {$booking->originStop->city_name} → {$booking->destinationStop->city_name}\n" .
            "🕐 Tanggal: {$schedule->departure_date->format('d M Y')} {$schedule->departure_time}\n" .
            "💰 Total: Rp " . number_format($booking->total_price, 0, ',', '.') . "\n\n" .
            "Segera lakukan pembayaran untuk konfirmasi booking."
        );

        // Push notification
        $this->sendPushNotification(
            $customer,
            'Booking Berhasil',
            "Booking {$booking->booking_code} berhasil dibuat.",
            ['type' => 'booking_created', 'booking_id' => $booking->id]
        );
    }

    public function paymentConfirmed(\App\Models\Booking $booking): void
    {
        $customer = $booking->customer;

        $this->createNotification(
            $customer->id,
            'Pembayaran Berhasil',
            "Pembayaran booking {$booking->booking_code} telah dikonfirmasi.",
            ['type' => 'payment_confirmed', 'booking_id' => $booking->id]
        );

        // Notifikasi ke agency
        $agencyUser = $booking->schedule->agency->user;
        if ($agencyUser) {
            $this->createNotification(
                $agencyUser->id,
                'Booking Baru Dibayar',
                "Booking {$booking->booking_code} telah dibayar.",
                ['type' => 'new_paid_booking', 'booking_id' => $booking->id]
            );

            $this->sendWhatsApp(
                $agencyUser->phone,
                "📋 *Booking Dibayar!*\n\n" .
                "Kode: *{$booking->booking_code}*\n" .
                "Customer: {$customer->name}\n" .
                "Total: Rp " . number_format($booking->total_price, 0, ',', '.')
            );
        }

        // WhatsApp ke customer
        $this->sendWhatsApp(
            $customer->phone,
            "✅ Pembayaran untuk booking *{$booking->booking_code}* telah dikonfirmasi.\n\n" .
            "E-Ticket dapat diunduh di aplikasi GoMad."
        );

        $this->sendPushNotification(
            $customer,
            'Pembayaran Berhasil',
            "Pembayaran {$booking->booking_code} dikonfirmasi.",
            ['type' => 'payment_confirmed', 'booking_id' => $booking->id]
        );

        // Proses referral reward
        try {
            app(\App\Services\PromoService::class)->processReferralReward($booking);
        } catch (\Exception $e) {
            Log::error('Referral reward error: ' . $e->getMessage());
        }
    }

    public function bookingCancelled(\App\Models\Booking $booking, string $reason): void
    {
        $customer = $booking->customer;

        $this->createNotification(
            $customer->id,
            'Booking Dibatalkan',
            "Booking {$booking->booking_code} telah dibatalkan.",
            ['type' => 'booking_cancelled', 'booking_id' => $booking->id]
        );

        $this->sendWhatsApp(
            $customer->phone,
            "❌ Booking *{$booking->booking_code}* telah dibatalkan.\n" .
            "Alasan: {$reason}"
        );

        $this->sendPushNotification(
            $customer,
            'Booking Dibatalkan',
            "Booking {$booking->booking_code} dibatalkan.",
            ['type' => 'booking_cancelled', 'booking_id' => $booking->id]
        );
    }

    public function bookingCompleted(\App\Models\Booking $booking): void
    {
        $customer = $booking->customer;

        $this->createNotification(
            $customer->id,
            'Perjalanan Selesai',
            "Perjalanan {$booking->booking_code} telah selesai.",
            ['type' => 'booking_completed', 'booking_id' => $booking->id]
        );

        $this->sendWhatsApp(
            $customer->phone,
            "🎉 Perjalanan GoMad *{$booking->booking_code}* telah selesai.\n" .
            "Terima kasih! Beri ulasan untuk membantu traveler lain."
        );
    }

    public function driverAssigned(\App\Models\Schedule $schedule, \App\Models\User $driver): void
    {
        $this->createNotification(
            $driver->id,
            'Jadwal Baru',
            "Anda ditugaskan jadwal {$schedule->route->route_name}.",
            ['type' => 'driver_assigned', 'schedule_id' => $schedule->id]
        );

        $this->sendWhatsApp(
            $driver->phone,
            "👨‍✈️ *Jadwal Baru!*\n\n" .
            "📅 Tanggal: {$schedule->departure_date->format('d M Y')}\n" .
            "🕐 Jam: {$schedule->departure_time}\n" .
            "🚗 Rute: {$schedule->route->route_name}\n" .
            "🚙 Kendaraan: {$schedule->vehicle->plate_number}"
        );

        $this->sendPushNotification(
            $driver,
            'Jadwal Baru',
            "Anda ditugaskan jadwal {$schedule->route->route_name}.",
            ['type' => 'driver_assigned', 'schedule_id' => $schedule->id]
        );
    }

    public function agencyVerified(\App\Models\Agency $agency): void
    {
        $user = $agency->user;
        if (!$user) return;

        $this->createNotification(
            $user->id,
            'Agency Terverifikasi',
            "Agency {$agency->agency_name} telah terverifikasi!",
            ['type' => 'agency_verified', 'agency_id' => $agency->id]
        );

        $this->sendWhatsApp(
            $user->phone,
            "🎉 Selamat! Agency *{$agency->agency_name}* telah *TERVERIFIKASI*!\n\n" .
            "Anda sekarang dapat membuat jadwal dan menerima booking."
        );
    }

    public function scheduleReminder(\App\Models\Schedule $schedule): void
    {
        // Reminder ke customer
        $bookings = $schedule->bookings()
            ->whereIn('status', ['paid', 'confirmed'])
            ->with('customer')
            ->get();

        foreach ($bookings as $booking) {
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

        // Reminder ke driver
        if ($schedule->driver) {
            $this->sendWhatsApp(
                $schedule->driver->phone,
                "⏰ *PENGINGAT JADWAL*\n\n" .
                "Besok Anda bertugas:\n" .
                "📅 {$schedule->departure_date->format('d M Y')} {$schedule->departure_time}\n" .
                "🚗 {$schedule->route->route_name}\n" .
                "🚙 {$schedule->vehicle->plate_number}"
            );
        }
    }

    public function rentalDriverAssigned(\App\Models\Rental $rental): void
    {
        $driver = $rental->driver;
        $customer = $rental->customer;

        if (!$driver || !$customer) return;

        // Ke customer
        $this->sendWhatsApp(
            $customer->phone,
            "👨‍✈️ *Supir Telah Ditugaskan!*\n\n" .
            "Kode Rental: *{$rental->rental_code}*\n" .
            "Supir: *{$driver->name}*\n" .
            "Telepon: *{$driver->phone}*\n" .
            "Mobil: {$rental->vehicle->plate_number}\n\n" .
            "Supir akan menjemput di: {$rental->pickup_address}"
        );

        // Ke driver
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

    // ═══════════════════════════════════════════
    // WELCOME NOTIFICATIONS
    // ═══════════════════════════════════════════

    /**
     * Notifikasi selamat datang untuk customer baru
     */
    public function welcomeCustomer(\App\Models\User $user): void
    {
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

        // In-app notification
        $this->createNotification(
            $user->id,
            '🎉 Selamat Datang di GoMad!',
            "Halo {$user->name}! Nomor WhatsApp Anda telah terhubung. Jelajahi layanan travel & rental kami.",
            ['type' => 'welcome', 'action' => 'home']
        );
    }

    /**
     * Notifikasi selamat datang untuk agency baru
     */
    public function welcomeAgency(\App\Models\User $user, \App\Models\Agency $agency): void
    {
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

        $this->createNotification(
            $user->id,
            '🏢 Selamat Datang di GoMad Agency!',
            "Lengkapi profil dan upload dokumen untuk verifikasi.",
            ['type' => 'welcome_agency', 'action' => 'agency_setup']
        );
    }

    /**
     * Notifikasi selamat datang untuk driver baru
     */
    public function welcomeDriver(\App\Models\User $user): void
    {
        $agency = $user->agencyBelongTo;

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

        $this->createNotification(
            $user->id,
            '👨‍✈️ Selamat Datang di GoMad Driver!',
            "Anda telah terdaftar sebagai driver" . ($agency ? " di {$agency->agency_name}" : "") . ".",
            ['type' => 'welcome_driver', 'action' => 'driver_schedule']
        );
    }

    /**
     * Notifikasi selamat datang untuk payment agent baru
     */
    public function welcomePaymentAgent(\App\Models\User $user, \App\Models\PaymentAgent $agent): void
    {
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

        $this->createNotification(
            $user->id,
            '🏪 Selamat Datang di GoMad Warung!',
            "Lengkapi profil dan tunggu verifikasi admin.",
            ['type' => 'welcome_agent', 'action' => 'payment_agent_setup']
        );
    }
}