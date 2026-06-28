<?php
// File: app/Services/NotificationService.php
// Deskripsi: Service untuk notifikasi WhatsApp, Push Notification, dan in-app notification

namespace App\Services;

use App\Models\Agency;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\Settlement;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private readonly DeviceService $deviceService,
    ) {}

    public function bookingCreated(Booking $booking): void
    {
        $customer = $booking->customer;
        $agency = $booking->schedule->agency;

        // In-app notification untuk customer
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
            "Booking GoMad *{$booking->booking_code}* berhasil dibuat.\n" .
            "Rute: {$booking->originStop->city_name} → {$booking->destinationStop->city_name}\n" .
            "Tanggal: {$booking->schedule->departure_date->format('d M Y')}\n" .
            "Jam: {$booking->schedule->departure_time}\n" .
            "Total: Rp " . number_format($booking->total_price, 0, ',', '.') . "\n\n" .
            "Segera lakukan pembayaran untuk konfirmasi booking."
        );

        // Push notification ke customer
        $this->sendPushNotification(
            $customer,
            'Booking Berhasil',
            "Booking {$booking->booking_code} berhasil dibuat. Silakan lakukan pembayaran.",
            ['type' => 'booking_created', 'booking_id' => $booking->id]
        );
    }

    public function paymentConfirmed(Booking $booking): void
    {
        $customer = $booking->customer;
        $agency = $booking->schedule->agency;

        $this->createNotification(
            $customer->id,
            'Pembayaran Berhasil',
            "Pembayaran untuk booking {$booking->booking_code} telah dikonfirmasi.",
            ['type' => 'payment_confirmed', 'booking_id' => $booking->id]
        );

        // Notifikasi ke agency
        $agencyUser = $agency->user;
        if ($agencyUser) {
            $this->createNotification(
                $agencyUser->id,
                'Booking Baru Dibayar',
                "Booking {$booking->booking_code} telah dibayar oleh {$customer->name}.",
                ['type' => 'new_paid_booking', 'booking_id' => $booking->id]
            );
        }

        $this->sendWhatsApp(
            $customer->phone,
            "Pembayaran untuk booking *{$booking->booking_code}* telah dikonfirmasi.\n\n" .
            "Rute: {$booking->originStop->city_name} → {$booking->destinationStop->city_name}\n" .
            "Tanggal: {$booking->schedule->departure_date->format('d M Y')}\n" .
            "Jam: {$booking->schedule->departure_time}\n\n" .
            "E-Ticket dapat diunduh di aplikasi GoMad."
        );

        $this->sendPushNotification(
            $customer,
            'Pembayaran Berhasil',
            "Pembayaran untuk booking {$booking->booking_code} telah dikonfirmasi.",
            ['type' => 'payment_confirmed', 'booking_id' => $booking->id]
        );
    }

    public function cashPaymentConfirmed(Booking $booking): void
    {
        $customer = $booking->customer;
        $agent = $booking->cashPayment->paymentAgent ?? null;

        $this->createNotification(
            $customer->id,
            'Pembayaran Cash Dikonfirmasi',
            "Pembayaran cash untuk booking {$booking->booking_code} telah dikonfirmasi oleh warung.",
            ['type' => 'cash_payment_confirmed', 'booking_id' => $booking->id]
        );

        $agentName = $agent ? $agent->agent_name : 'Warung GoMad';
        $this->sendWhatsApp(
            $customer->phone,
            "Pembayaran cash untuk booking *{$booking->booking_code}* telah dikonfirmasi oleh *{$agentName}*.\n\n" .
            "Total: Rp " . number_format($booking->total_price, 0, ',', '.') . "\n" .
            "Terima kasih telah menggunakan GoMad!"
        );
    }

    public function bookingCancelled(Booking $booking, string $reason): void
    {
        $customer = $booking->customer;

        $this->createNotification(
            $customer->id,
            'Booking Dibatalkan',
            "Booking {$booking->booking_code} telah dibatalkan. Alasan: {$reason}",
            ['type' => 'booking_cancelled', 'booking_id' => $booking->id]
        );

        $this->sendWhatsApp(
            $customer->phone,
            "Booking *{$booking->booking_code}* telah dibatalkan.\n" .
            "Alasan: {$reason}\n\n" .
            "Silakan booking kembali di GoMad."
        );

        $this->sendPushNotification(
            $customer,
            'Booking Dibatalkan',
            "Booking {$booking->booking_code} telah dibatalkan.",
            ['type' => 'booking_cancelled', 'booking_id' => $booking->id]
        );
    }

    public function bookingCompleted(Booking $booking): void
    {
        $customer = $booking->customer;

        $this->createNotification(
            $customer->id,
            'Perjalanan Selesai',
            "Perjalanan untuk booking {$booking->booking_code} telah selesai. Berikan ulasan untuk agency.",
            ['type' => 'booking_completed', 'booking_id' => $booking->id]
        );

        $this->sendWhatsApp(
            $customer->phone,
            "Perjalanan GoMad *{$booking->booking_code}* telah selesai.\n\n" .
            "Rute: {$booking->originStop->city_name} → {$booking->destinationStop->city_name}\n" .
            "Terima kasih telah menggunakan GoMad!\n" .
            "Beri ulasan untuk membantu traveler lain."
        );

        $this->sendPushNotification(
            $customer,
            'Perjalanan Selesai',
            "Perjalanan selesai. Berikan ulasan untuk agency.",
            ['type' => 'booking_completed', 'booking_id' => $booking->id]
        );
    }

    public function agencyVerified(Agency $agency): void
    {
        $user = $agency->user;
        if (!$user) return;

        $this->createNotification(
            $user->id,
            'Agency Terverifikasi',
            "Selamat! Agency {$agency->agency_name} telah terverifikasi. Anda sekarang dapat membuat jadwal.",
            ['type' => 'agency_verified', 'agency_id' => $agency->id]
        );

        $this->sendWhatsApp(
            $user->phone,
            "🎉 Selamat! Agency *{$agency->agency_name}* telah *TERVERIFIKASI*.\n\n" .
            "Anda sekarang dapat:\n" .
            "✅ Membuat jadwal perjalanan\n" .
            "✅ Menerima booking dari customer\n" .
            "✅ Mengelola driver dan kendaraan\n\n" .
            "Login ke dashboard agency: " . config('app.url') . "/agency/login"
        );
    }

    public function agencyRejected(Agency $agency, string $reason): void
    {
        $user = $agency->user;
        if (!$user) return;

        $this->createNotification(
            $user->id,
            'Verifikasi Agency Ditolak',
            "Verifikasi agency {$agency->agency_name} ditolak. Alasan: {$reason}",
            ['type' => 'agency_rejected', 'agency_id' => $agency->id]
        );

        $this->sendWhatsApp(
            $user->phone,
            "Maaf, verifikasi agency *{$agency->agency_name}* ditolak.\n\n" .
            "Alasan: {$reason}\n\n" .
            "Silakan perbaiki data agency dan ajukan verifikasi ulang."
        );
    }

    public function driverAssigned(Schedule $schedule, User $driver): void
    {
        $this->createNotification(
            $driver->id,
            'Jadwal Baru',
            "Anda ditugaskan untuk jadwal {$schedule->route->route_name} pada {$schedule->departure_date->format('d M Y')}.",
            ['type' => 'driver_assigned', 'schedule_id' => $schedule->id]
        );

        $this->sendWhatsApp(
            $driver->phone,
            "Halo {$driver->name},\n\n" .
            "Anda ditugaskan untuk jadwal:\n" .
            "📅 Tanggal: {$schedule->departure_date->format('d M Y')}\n" .
            "🕐 Jam: {$schedule->departure_time}\n" .
            "🚗 Rute: {$schedule->route->route_name}\n" .
            "🚙 Kendaraan: {$schedule->vehicle->plate_number}\n\n" .
            "Cek aplikasi GoMad Driver untuk detail."
        );

        $this->sendPushNotification(
            $driver,
            'Jadwal Baru Ditugaskan',
            "Anda ditugaskan jadwal {$schedule->route->route_name} pada {$schedule->departure_date->format('d M Y')}.",
            ['type' => 'driver_assigned', 'schedule_id' => $schedule->id]
        );
    }

    public function withdrawalApproved(Withdrawal $withdrawal): void
    {
        $agency = $withdrawal->agency;
        $user = $agency->user;
        if (!$user) return;

        $this->createNotification(
            $user->id,
            'Penarikan Disetujui',
            "Penarikan dana Rp " . number_format($withdrawal->amount, 0, ',', '.') . " telah disetujui.",
            ['type' => 'withdrawal_approved', 'withdrawal_id' => $withdrawal->id]
        );

        $this->sendWhatsApp(
            $user->phone,
            "Penarikan dana *DISETUJUI*\n\n" .
            "Jumlah: Rp " . number_format($withdrawal->amount, 0, ',', '.') . "\n" .
            "Biaya admin: Rp " . number_format($withdrawal->admin_fee, 0, ',', '.') . "\n" .
            "Diterima: Rp " . number_format($withdrawal->net_amount, 0, ',', '.') . "\n" .
            "Bank: {$withdrawal->bank_name} - {$withdrawal->bank_account_number}\n\n" .
            "Dana akan diproses dan masuk ke rekening Anda."
        );
    }

    public function withdrawalRejected(Withdrawal $withdrawal, string $reason): void
    {
        $agency = $withdrawal->agency;
        $user = $agency->user;
        if (!$user) return;

        $this->createNotification(
            $user->id,
            'Penarikan Ditolak',
            "Penarikan dana Rp " . number_format($withdrawal->amount, 0, ',', '.') . " ditolak. Alasan: {$reason}",
            ['type' => 'withdrawal_rejected', 'withdrawal_id' => $withdrawal->id]
        );

        $this->sendWhatsApp(
            $user->phone,
            "Penarikan dana *DITOLAK*\n\n" .
            "Jumlah: Rp " . number_format($withdrawal->amount, 0, ',', '.') . "\n" .
            "Alasan: {$reason}\n\n" .
            "Dana telah dikembalikan ke saldo Anda."
        );
    }

    public function settlementGenerated(Settlement $settlement): void
    {
        $agent = $settlement->paymentAgent;
        $user = $agent->user;
        if (!$user) return;

        $this->createNotification(
            $user->id,
            'Tagihan Settlement',
            "Tagihan settlement periode {$settlement->period_start->format('d M')} - {$settlement->period_end->format('d M Y')} sebesar Rp " . number_format($settlement->amount_to_settle, 0, ',', '.'),
            ['type' => 'settlement_generated', 'settlement_id' => $settlement->id]
        );

        $this->sendWhatsApp(
            $agent->owner_phone,
            "📋 *TAGIHAN SETTLEMENT GOMAD*\n\n" .
            "Periode: {$settlement->period_start->format('d M')} - {$settlement->period_end->format('d M Y')}\n" .
            "Total transaksi: {$settlement->total_transactions}\n" .
            "Total diterima: Rp " . number_format($settlement->total_amount, 0, ',', '.') . "\n" .
            "Komisi warung: Rp " . number_format($settlement->total_commission, 0, ',', '.') . "\n" .
            "*Harus disetor: Rp " . number_format($settlement->amount_to_settle, 0, ',', '.') . "*\n\n" .
            "Silakan bayar melalui aplikasi Warung GoMad."
        );
    }

    public function settlementPaid(Settlement $settlement): void
    {
        $agent = $settlement->paymentAgent;
        $user = $agent->user;
        if (!$user) return;

        $this->createNotification(
            $user->id,
            'Settlement Dibayar',
            "Pembayaran settlement periode {$settlement->period_start->format('d M')} - {$settlement->period_end->format('d M Y')} telah diterima.",
            ['type' => 'settlement_paid', 'settlement_id' => $settlement->id]
        );
    }

    public function scheduleReminder(Schedule $schedule): void
    {
        $bookings = $schedule->bookings()
            ->whereIn('status', ['paid', 'confirmed'])
            ->with('customer')
            ->get();

        foreach ($bookings as $booking) {
            $customer = $booking->customer;

            $this->sendWhatsApp(
                $customer->phone,
                "⏰ *PENGINGAT JADWAL*\n\n" .
                "Halo {$customer->name},\n" .
                "Besok adalah jadwal keberangkatan GoMad Anda:\n\n" .
                "📅 Tanggal: {$schedule->departure_date->format('d M Y')}\n" .
                "🕐 Jam: {$schedule->departure_time}\n" .
                "📍 Jemput: {$booking->pickup_address}\n" .
                "🎯 Tujuan: {$booking->destinationStop->city_name}\n" .
                "🚗 Kendaraan: {$schedule->vehicle->plate_number}\n\n" .
                "Booking Code: {$booking->booking_code}\n" .
                "Pastikan Anda siap di lokasi penjemputan."
            );

            $this->sendPushNotification(
                $customer,
                'Pengingat Jadwal',
                "Besok jadwal keberangkatan GoMad Anda. Booking: {$booking->booking_code}",
                ['type' => 'schedule_reminder', 'booking_id' => $booking->id]
            );
        }

        // Reminder ke driver
        if ($schedule->driver) {
            $this->sendWhatsApp(
                $schedule->driver->phone,
                "⏰ *PENGINGAT JADWAL*\n\n" .
                "Besok Anda bertugas:\n" .
                "📅 Tanggal: {$schedule->departure_date->format('d M Y')}\n" .
                "🕐 Jam: {$schedule->departure_time}\n" .
                "🚗 Rute: {$schedule->route->route_name}\n" .
                "🚙 Kendaraan: {$schedule->vehicle->plate_number}\n\n" .
                "Cek aplikasi untuk detail penumpang."
            );
        }
    }

    public function overloadWarning(Schedule $schedule): void
    {
        $agency = $schedule->agency;
        $user = $agency->user;
        if (!$user) return;

        $overloadService = app(\App\Services\OverloadService::class);
        $occupancyRate = $overloadService->getOccupancyRate($schedule);

        $this->createNotification(
            $user->id,
            'Peringatan Kapasitas',
            "Jadwal {$schedule->route->route_name} tanggal {$schedule->departure_date->format('d M Y')} sudah terisi {$occupancyRate}%.",
            ['type' => 'overload_warning', 'schedule_id' => $schedule->id]
        );

        $this->sendWhatsApp(
            $user->phone,
            "⚠️ *PERINGATAN KAPASITAS*\n\n" .
            "Jadwal: {$schedule->route->route_name}\n" .
            "Tanggal: {$schedule->departure_date->format('d M Y')}\n" .
            "Jam: {$schedule->departure_time}\n" .
            "Okupansi: {$occupancyRate}%\n\n" .
            "Cek dashboard untuk detail."
        );
    }

    public function sendWhatsApp(string $phone, string $message): void
    {
        if (empty($phone)) {
            Log::warning('Notification: Empty phone number for WhatsApp');
            return;
        }

        // Normalize phone number (remove non-digit, add country code if needed)
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) >= 10 && strlen($phone) <= 13) {
            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            } elseif (!str_starts_with($phone, '62')) {
                $phone = '62' . $phone;
            }
        }

        $twilioSid = config('gomad.twilio.sid');
        $twilioToken = config('gomad.twilio.auth_token');
        $twilioFrom = config('gomad.twilio.whatsapp_from');

        if (empty($twilioSid) || empty($twilioToken) || empty($twilioFrom)) {
            Log::info('WhatsApp Notification (simulated)', [
                'to' => $phone,
                'message' => $message,
            ]);
            return;
        }

        try {
            $response = Http::withBasicAuth($twilioSid, $twilioToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$twilioSid}/Messages.json", [
                    'From' => $twilioFrom,
                    'To' => "whatsapp:+{$phone}",
                    'Body' => $message,
                ]);

            if (!$response->successful()) {
                Log::error('Twilio WhatsApp Error', [
                    'to' => $phone,
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Twilio WhatsApp Exception', [
                'to' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendPushNotification(User $user, string $title, string $body, array $data = []): void
    {
        $this->createNotification($user->id, $title, $body, $data);
        
        // Send via FCM
        $this->deviceService->sendToUser($user, $title, $body, $data);
    }

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
}

// End of file