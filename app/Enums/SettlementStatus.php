<?php
// File: app/Enums/SettlementStatus.php
// Deskripsi: Enum untuk status settlement warung

namespace App\Enums;

enum SettlementStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case VERIFIED = 'verified';
    case OVERDUE = 'overdue';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Menunggu Pembayaran',
            self::PAID => 'Sudah Dibayar',
            self::VERIFIED => 'Terverifikasi',
            self::OVERDUE => 'Terlambat',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::PAID => 'blue',
            self::VERIFIED => 'green',
            self::OVERDUE => 'red',
        };
    }
}

// End of file