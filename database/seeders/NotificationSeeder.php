<?php

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

        $templates = [
            'admin' => [
                ['title' => 'Agency baru perlu verifikasi', 'body' => 'Ada agency baru yang mendaftar.'],
                ['title' => 'Withdrawal request masuk', 'body' => 'Permintaan penarikan dana baru.'],
                ['title' => 'Laporan transaksi harian', 'body' => 'Laporan transaksi hari ini tersedia.'],
                ['title' => 'Update sistem berhasil', 'body' => 'Sistem berhasil diupdate.'],
                ['title' => 'Payment agent baru mendaftar', 'body' => 'Ada payment agent baru.'],
            ],
            'agency' => [
                ['title' => 'Booking baru diterima!', 'body' => 'Ada booking baru untuk jadwal Anda.'],
                ['title' => 'Withdrawal berhasil diproses', 'body' => 'Penarikan dana berhasil.'],
                ['title' => 'Saldo deposit menipis', 'body' => 'Saldo deposit hampir habis.'],
                ['title' => 'Review baru dari customer', 'body' => 'Customer memberikan review.'],
                ['title' => 'Jadwal hampir penuh', 'body' => 'Jadwal Anda hampir penuh.'],
                ['title' => 'Promo baru tersedia', 'body' => 'Ada promo baru untuk jadwal Anda.'],
            ],
            'customer' => [
                ['title' => 'Booking berhasil!', 'body' => 'Pemesanan tiket berhasil.'],
                ['title' => 'Pembayaran diterima', 'body' => 'Pembayaran telah diterima.'],
                ['title' => 'Jadwal keberangkatan besok', 'body' => 'Keberangkatan Anda besok pagi.'],
                ['title' => 'Promo spesial', 'body' => 'Dapatkan diskon spesial!'],
                ['title' => 'Review perjalanan', 'body' => 'Beri review perjalanan Anda.'],
                ['title' => 'Referral berhasil!', 'body' => 'Teman berhasil daftar dengan kode Anda.'],
            ],
            'driver' => [
                ['title' => 'Jadwal baru ditugaskan', 'body' => 'Anda mendapat jadwal baru.'],
                ['title' => 'Jadwal besok', 'body' => 'Keberangkatan besok pukul 08:00.'],
                ['title' => 'Booking penuh', 'body' => 'Semua kursi terisi.'],
                ['title' => 'Pengingat keberangkatan', 'body' => 'Keberangkatan 1 jam lagi!'],
            ],
            'payment_agent' => [
                ['title' => 'Kode bayar baru', 'body' => 'Customer generate kode bayar.'],
                ['title' => 'Pembayaran terkonfirmasi', 'body' => 'Pembayaran terkonfirmasi.'],
                ['title' => 'Settlement mingguan siap', 'body' => 'Laporan settlement tersedia.'],
                ['title' => 'Warung terverifikasi', 'body' => 'Warung Anda terverifikasi!'],
                ['title' => 'Komisi diterima', 'body' => 'Komisi ditambahkan ke saldo.'],
            ],
        ];

        foreach ($allUsers as $user) {
            $role = $user->role;
            $roleTemplates = $templates[$role] ?? $templates['customer'];

            $numNotif = rand(5, 10);

            for ($i = 0; $i < $numNotif; $i++) {
                $template = $roleTemplates[array_rand($roleTemplates)];
                $isRead = rand(1, 100) <= 70;
                $createdDays = rand(0, 30);

                Notification::create([
                    'user_id' => $user->id,
                    'title' => $template['title'],
                    'body' => $template['body'],
                    'data' => json_encode([
                        'role' => $role,
                        'type' => strtolower(str_replace(' ', '_', $template['title'])),
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

        $readCount = Notification::where('is_read', true)->count();
        $unreadCount = Notification::where('is_read', false)->count();
        echo "📊 BREAKDOWN:\n";
        echo "📖 Read: {$readCount}\n";
        echo "📬 Unread: {$unreadCount}\n";
    }
}
