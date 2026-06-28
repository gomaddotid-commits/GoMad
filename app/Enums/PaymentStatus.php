<?php
// File: app/Enums/PaymentStatus.php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case EXPIRED = 'expired';
    case COD_PENDING = 'cod_pending';    // COD: menunggu konfirmasi driver
    case COD_CONFIRMED = 'cod_confirmed'; // COD: sudah dikonfirmasi driver

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Menunggu',
            self::PAID => 'Dibayar',
            self::FAILED => 'Gagal',
            self::REFUNDED => 'Dikembalikan',
            self::EXPIRED => 'Kadaluarsa',
            self::COD_PENDING => 'COD (Menunggu Sopir)',
            self::COD_CONFIRMED => 'COD (Terkonfirmasi)',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::PAID => 'green',
            self::FAILED => 'red',
            self::REFUNDED => 'purple',
            self::EXPIRED => 'gray',
            self::COD_PENDING => 'orange',
            self::COD_CONFIRMED => 'green',
        };
    }

    public function isFinal(): bool
    {
        return match($this) {
            self::PAID, self::FAILED, self::REFUNDED, self::EXPIRED, self::COD_CONFIRMED => true,
            self::PENDING, self::COD_PENDING => false,
        };
    }
}