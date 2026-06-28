<?php
// File: app/Enums/WalletTransactionType.php
// Deskripsi: Enum untuk tipe transaksi wallet

namespace App\Enums;

enum WalletTransactionType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';

    public function label(): string
    {
        return match($this) {
            self::CREDIT => 'Masuk',
            self::DEBIT => 'Keluar',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::CREDIT => 'green',
            self::DEBIT => 'red',
        };
    }
}

// End of file