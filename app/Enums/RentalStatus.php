<?php

namespace App\Enums;

enum RentalStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case ACTIVE = 'active';
    case RETURNED = 'returned';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Menunggu Pembayaran',
            self::PAID => 'Siap Diambil',
            self::ACTIVE => 'Sedang Disewa',
            self::RETURNED => 'Menunggu Verifikasi',
            self::COMPLETED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::PAID => 'blue',
            self::ACTIVE => 'indigo',
            self::RETURNED => 'orange',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [self::PAID, self::CANCELLED]),
            self::PAID => in_array($newStatus, [self::ACTIVE, self::CANCELLED]),
            self::ACTIVE => in_array($newStatus, [self::RETURNED]),
            self::RETURNED => in_array($newStatus, [self::COMPLETED]),
            self::COMPLETED, self::CANCELLED => false,
        };
    }
}