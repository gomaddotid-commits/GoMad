<?php
// File: database/seeders/PlatformSettingSeeder.php
// Deskripsi: Seeder untuk pengaturan platform default

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'commission_rate',
                'value' => '5',
                'description' => 'Komisi platform per transaksi (dalam persen)',
            ],
            [
                'key' => 'warung_commission_rate',
                'value' => '2',
                'description' => 'Komisi warung per transaksi cash (dalam persen)',
            ],
            [
                'key' => 'payment_timeout',
                'value' => '30',
                'description' => 'Batas waktu pembayaran Midtrans (dalam menit)',
            ],
            [
                'key' => 'payment_code_expiry_hours',
                'value' => '24',
                'description' => 'Batas waktu kode bayar Warung GoMad (dalam jam)',
            ],
            [
                'key' => 'schedule_min_days',
                'value' => '30',
                'description' => 'Minimal hari sebelum keberangkatan untuk membuat jadwal',
            ],
            [
                'key' => 'overload_economy_max',
                'value' => '10',
                'description' => 'Maksimal penumpang kelas ekonomi (termasuk overload)',
            ],
            [
                'key' => 'baggage_economy',
                'value' => '15',
                'description' => 'Batas bagasi kelas ekonomi (kg per orang)',
            ],
            [
                'key' => 'baggage_premium',
                'value' => '20',
                'description' => 'Batas bagasi kelas premium (kg per orang)',
            ],
            [
                'key' => 'minimal_withdrawal',
                'value' => '100000',
                'description' => 'Minimal penarikan dana agency (Rupiah)',
            ],
            [
                'key' => 'withdrawal_admin_fee',
                'value' => '5000',
                'description' => 'Biaya admin penarikan dana (Rupiah)',
            ],
            [
                'key' => 'auto_approve_limit',
                'value' => '5000000',
                'description' => 'Batas auto-approve penarikan dana (Rupiah)',
            ],
            [
                'key' => 'driver_min_rating',
                'value' => '3.0',
                'description' => 'Rating minimum untuk driver',
            ],
            [
                'key' => 'support_phone',
                'value' => '081234567890',
                'description' => 'Nomor telepon support GoMad',
            ],
            [
                'key' => 'support_email',
                'value' => 'support@gomad.id',
                'description' => 'Email support GoMad',
            ],
            [
                'key' => 'total_app_downloads',
                'value' => '0',
                'description' => 'Total download aplikasi',
            ],
            [
                'key' => 'topup_admin_fee',
                'value' => '3500',
                'description' => 'Biaya admin setiap top up saldo deposit (Rupiah)',
            ],
            [
                'key' => 'topup_min_amount',
                'value' => '50000',
                'description' => 'Minimal nominal top up saldo deposit (Rupiah)',
            ],
            [
                'key' => 'service_fee',
                'value' => '5000',
                'description' => 'Biaya layanan per booking (Rupiah)',
            ],
            [
                'key' => 'platform_fee_percent',
                'value' => '3',
                'description' => 'Biaya platform per booking (persen dari total)',
            ],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}

// End of file