<?php
// File: app/Enums/BookingStatus.php
// Deskripsi: Enum untuk status booking

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
    case ON_GOING = 'on_going';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Menunggu Pembayaran',
            self::CONFIRMED => 'Terkonfirmasi',
            self::PAID => 'Sudah Dibayar',
            self::CANCELLED => 'Dibatalkan',
            self::COMPLETED => 'Selesai',
            self::ON_GOING => 'Sedang Berjalan',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::CONFIRMED => 'blue',
            self::PAID => 'green',
            self::CANCELLED => 'red',
            self::COMPLETED => 'gray',
            self::ON_GOING => 'indigo',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [self::CONFIRMED, self::PAID, self::CANCELLED]),
            self::CONFIRMED => in_array($newStatus, [self::PAID, self::CANCELLED, self::ON_GOING]),
            self::PAID => in_array($newStatus, [self::ON_GOING, self::CANCELLED]),
            self::ON_GOING => in_array($newStatus, [self::COMPLETED]),
            self::CANCELLED => false,
            self::COMPLETED => false,
        };
    }
}

// End of file