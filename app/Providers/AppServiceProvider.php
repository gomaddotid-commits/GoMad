<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        if (app()->environment('production')) {
            // Force HTTPS (Cloudflare SSL)
            URL::forceScheme('https');
            
            // Trust Cloudflare sebagai reverse proxy
            // Ini penting agar Laravel membaca IP asli, host, dan protocol dari header Cloudflare
            Request::setTrustedProxies(
                ['*'], // Trust semua proxy (Cloudflare IP dinamis)
                Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_PREFIX |
                Request::HEADER_X_FORWARDED_AWS_ELB
            );
        }
    }
}