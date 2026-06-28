<?php
// File: app/Enums/TravelClass.php
// Deskripsi: Enum untuk kelas layanan travel

namespace App\Enums;

enum TravelClass: string
{
    case ECONOMY = 'economy';
    case PREMIUM = 'premium';
    case CHARTER = 'charter';
    case RENTAL = 'rental';

    public function label(): string
    {
        return match($this) {
            self::ECONOMY => 'Ekonomi',
            self::PREMIUM => 'Premium',
            self::CHARTER => 'Charter',
            self::RENTAL => 'Rental',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::ECONOMY => 'Mobil 8 seat, max overload +2 (total max 10), bagasi 15kg/orang',
            self::PREMIUM => 'Sesuai kapasitas (8 seat strict), bagasi 20kg/orang',
            self::CHARTER => 'Sewa mobil + supir (harga flat per mobil)',
            self::RENTAL => 'Sewa mobil tanpa supir (harga flat)',
        };
    }

    public function maxOverload(int $capacity): int
    {
        return match($this) {
            self::ECONOMY => 2,
            self::PREMIUM => 0,
            self::CHARTER => 0,
            self::RENTAL => 0,
        };
    }

    public function maxBaggage(): float
    {
        return match($this) {
            self::ECONOMY => 15.00,
            self::PREMIUM => 20.00,
            self::CHARTER => 25.00,
            self::RENTAL => 0,
        };
    }
}

// End of file