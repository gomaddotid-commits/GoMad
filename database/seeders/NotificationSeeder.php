<?php
// File: database/seeders/NotificationSeeder.php
// Deskripsi: Seeder untuk data notifikasi semua user

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        echo "🔔 GENERATING NOTIFICATIONS...\n";
        echo "═══════════════════════════════════════════\n\n";

        $allUsers = User::where('is_active', true)->get();

        if ($allUsers->isEmpty()) {
            echo "⚠️  Tidak ada user untuk notifikasi\n";
            return;
        }

        $notifCount = 0;

        // Template notifikasi per role
        $templates = [
            'admin' => [
                ['title' => 'Agency baru perlu verifikasi', 'body' => 'Ada agency baru yang mendaftar dan perlu diverifikasi.'],
                ['title' => 'Withdrawal request masuk', 'body' => 'Permintaan penarikan dana baru menunggu approval.'],
                ['title' => 'Laporan transaksi harian', 'body' => 'Laporan transaksi hari ini telah tersedia.'],
                ['title' => 'Update sistem berhasil', 'body' => 'Sistem berhasil diupdate ke versi terbaru.'],
                ['title' => 'Payment agent baru mendaftar', 'body' => 'Ada payment agent baru yang perlu diverifikasi.'],
            ],
            'agency' => [
                ['title' => 'Booking baru diterima!', 'body' => 'Selamat! Ada booking baru untuk jadwal Anda.'],
                ['title' => 'Withdrawal berhasil diproses', 'body' => 'Penarikan dana Anda telah berhasil diproses.'],
                ['title' => 'Saldo deposit menipis', 'body' => 'Saldo deposit Anda hampir habis. Segera top up.'],
                ['title' => 'Review baru dari customer', 'body' => 'Customer memberikan review untuk layanan Anda.'],
                ['title' => 'Jadwal hampir penuh', 'body' => 'Jadwal keberangkatan Anda hampir penuh.'],
                ['title' => 'Promo baru tersedia', 'body' => 'Ada promo baru yang bisa Anda pasang di jadwal.'],
            ],
            'customer' => [
                ['title' => 'Booking berhasil!', 'body' => 'Pemesanan tiket Anda telah berhasil.'],
                ['title' => 'Pembayaran diterima', 'body' => 'Pembayaran Anda telah kami terima.'],
                ['title' => 'Jadwal keberangkatan besok', 'body' => 'Jangan lupa! Keberangkatan Anda besok pagi.'],
                ['title' => 'Promo spesial untuk Anda', 'body' => 'Dapatkan diskon spesial untuk pemesanan berikutnya.'],
                ['title' => 'Review perjalanan Anda', 'body' => 'Bagaimana perjalanan Anda? Beri review sekarang.'],
                ['title' => 'Referral berhasil!', 'body' => 'Teman Anda berhasil mendaftar menggunakan kode referral.'],
            ],
            'driver' => [
                ['title' => 'Jadwal baru ditugaskan', 'body' => 'Anda mendapat jadwal keberangkatan baru.'],
                ['title' => 'Jadwal besok', 'body' => 'Jadwal keberangkatan Anda besok pukul 08:00.'],
                ['title' => 'Booking penuh', 'body' => 'Semua kursi untuk jadwal Anda telah terisi.'],
                ['title' => 'Pengingat keberangkatan', 'body' => 'Keberangkatan 1 jam lagi. Segera persiapkan!'],
            ],
            'payment_agent' => [
                ['title' => 'Kode bayar baru', 'body' => 'Customer telah generate kode bayar di warung Anda.'],
                ['title' => 'Pembayaran terkonfirmasi', 'body' => 'Pembayaran customer telah terkonfirmasi.'],
                ['title' => 'Settlement mingguan siap', 'body' => 'Laporan settlement mingguan Anda telah tersedia.'],
                ['title' => 'Warung Anda terverifikasi', 'body' => 'Selamat! Warung Anda telah terverifikasi.'],
                ['title' => 'Komisi diterima', 'body' => 'Komisi transaksi telah ditambahkan ke saldo Anda.'],
            ],
        ];

        foreach ($allUsers as $user) {
            $role = $user->role;
            $roleTemplates = $templates[$role] ?? $templates['customer'];

            // 5-10 notifikasi per user
            $numNotif = rand(5, 10);

            for ($i = 0; $i < $numNotif; $i++) {
                $template = fake()->randomElement($roleTemplates);
                $isRead = fake()->boolean(70); // 70% sudah dibaca
                $createdDays = rand(0, 30);

                Notification::create([
                    'user_id' => $user->id,
                    'title' => $template['title'],
                    'body' => $template['body'],
                    'data' => json_encode([
                        'role' => $role,
                        'type' => strtolower(str_replace(' ', '_', $template['title'])),
                        'created_at' => now()->subDays($createdDays)->toDateTimeString(),
                    ]),
                    'is_read' => $isRead,
                    'read_at' => $isRead ? now()->subDays(rand(0, $createdDays)) : null,
                    'created_at' => now()->subDays($createdDays),
                    'updated_at' => now()->subDays($createdDays),
                ]);

                $notifCount++;
            }
        }

        echo "✅ {$notifCount} Notifications created\n\n";

        echo "📊 NOTIFICATION BREAKDOWN:\n";
        echo "──────────────────────────────────────────────\n";
        $readCount = Notification::where('is_read', true)->count();
        $unreadCount = Notification::where('is_read', false)->count();
        echo "📖 Read: {$readCount}\n";
        echo "📬 Unread: {$unreadCount}\n";
        echo "──────────────────────────────────────────────\n";
    }
}