<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case EXPIRED = 'expired';
    case COD_PENDING = 'cod_pending';
    case COD_CONFIRMED = 'cod_confirmed';
    case REFUND_PENDING = 'refund_pending';
    case REFUND_APPROVED = 'refund_approved';
    case REFUND_REJECTED = 'refund_rejected';

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
            self::REFUND_PENDING => 'Refund (Menunggu Approval)',
            self::REFUND_APPROVED => 'Refund (Disetujui)',
            self::REFUND_REJECTED => 'Refund (Ditolak)',
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
            self::REFUND_PENDING => 'yellow',
            self::REFUND_APPROVED => 'blue',
            self::REFUND_REJECTED => 'red',
        };
    }

    public function isFinal(): bool
    {
        return match($this) {
            self::PAID, self::FAILED, self::REFUNDED, self::EXPIRED, 
            self::COD_CONFIRMED, self::REFUND_APPROVED, self::REFUND_REJECTED => true,
            self::PENDING, self::COD_PENDING, self::REFUND_PENDING => false,
        };
    }
}