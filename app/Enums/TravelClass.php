<?php
// File: app/Enums/TravelClass.php
// Deskripsi: Enum untuk kelas layanan travel

namespace App\Enums;

enum TravelClass: string
{
    case ECONOMY = 'economy';
    case PREMIUM = 'premium';
    case CHARTER = 'charter';

    public function label(): string
    {
        return match($this) {
            self::ECONOMY => 'Ekonomi',
            self::PREMIUM => 'Premium',
            self::CHARTER => 'Charter',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::ECONOMY => 'Mobil 8 seat, max overload +2 (total max 10), bagasi 15kg/orang',
            self::PREMIUM => 'Sesuai kapasitas (8 seat strict), bagasi 20kg/orang',
            self::CHARTER => 'Sewa mobil + supir (harga flat per mobil)',
        };
    }

    public function maxOverload(int $capacity): int
    {
        return match($this) {
            self::ECONOMY => 2,
            self::PREMIUM => 0,
            self::CHARTER => 0,
        };
    }

    public function maxBaggage(): float
    {
        return match($this) {
            self::ECONOMY => 15.00,
            self::PREMIUM => 20.00,
            self::CHARTER => 25.00,
        };
    }
}

// End of file