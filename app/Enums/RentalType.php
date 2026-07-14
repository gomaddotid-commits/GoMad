<?php

namespace App\Enums;

enum RentalType: string
{
    case SELF_DRIVE = 'self_drive';
    case WITH_DRIVER = 'with_driver';

    public function label(): string
    {
        return match($this) {
            self::SELF_DRIVE => 'Lepas Kunci (Self Drive)',
            self::WITH_DRIVER => 'Dengan Supir',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::SELF_DRIVE => 'Anda menyetir sendiri. Wajib memiliki SIM yang sesuai.',
            self::WITH_DRIVER => 'Termasuk supir profesional dari agency.',
        };
    }
}