<?php
// File: app/Helpers/DomainHelper.php
// Deskripsi: Helper untuk domain detection dan URL generation

namespace App\Helpers;

class DomainHelper
{
    public static function isApi(): bool
    {
        $host = request()->getHost();
        $port = request()->getPort();
        
        return str_starts_with($host, 'api.') || $port === 8001;
    }

    public static function isWeb(): bool
    {
        return !self::isApi();
    }

    public static function apiUrl(string $path = ''): string
    {
        $baseUrl = config('gomad.api_url', 'http://api.gomad.test');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    public static function webUrl(string $path = ''): string
    {
        $baseUrl = config('app.url', 'http://web.gomad.test');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    public static function landingUrl(string $path = ''): string
    {
        $baseUrl = config('gomad.landing_url', 'http://gomad.test');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    public static function isLocal(): bool
    {
        return app()->environment('local');
    }

    public static function isProduction(): bool
    {
        return app()->environment('production');
    }

    public static function getCurrentDomain(): string
    {
        return request()->getHost();
    }
}

// End of file