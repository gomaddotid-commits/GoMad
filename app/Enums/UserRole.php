<?php
// File: app/Enums/UserRole.php
// Deskripsi: Enum untuk role user

namespace App\Enums;

enum UserRole: string
{
    case CUSTOMER = 'customer';
    case AGENCY = 'agency';
    case DRIVER = 'driver';
    case ADMIN = 'admin';
    case PAYMENT_AGENT = 'payment_agent';

    public function label(): string
    {
        return match($this) {
            self::CUSTOMER => 'Customer',
            self::AGENCY => 'Agency',
            self::DRIVER => 'Driver',
            self::ADMIN => 'Admin',
            self::PAYMENT_AGENT => 'Payment Agent',
        };
    }

    public function canAccessDashboard(): bool
    {
        return match($this) {
            self::CUSTOMER => false,
            self::AGENCY, self::DRIVER, self::ADMIN, self::PAYMENT_AGENT => true,
        };
    }

    public function defaultRedirectRoute(): string
    {
        return match($this) {
            self::CUSTOMER => 'customer.home',
            self::AGENCY => 'agency.dashboard',
            self::DRIVER => 'driver.schedule',
            self::ADMIN => 'admin.dashboard',
            self::PAYMENT_AGENT => 'payment-agent.dashboard',
        };
    }
}

// End of file