<?php
// File: config/deeplink.php
// Deskripsi: Konfigurasi deep link untuk mobile app

return [

    /*
    |--------------------------------------------------------------------------
    | Deep Link Scheme
    |--------------------------------------------------------------------------
    */
    'scheme' => env('DEEPLINK_SCHEME', 'gomad'),

    /*
    |--------------------------------------------------------------------------
    | Deep Link Host
    |--------------------------------------------------------------------------
    */
    'host' => env('DEEPLINK_HOST', 'app.gomad.id'),

    /*
    |--------------------------------------------------------------------------
    | Deep Link Path Mappings
    |--------------------------------------------------------------------------
    */
    'paths' => [
        'booking' => '/booking/{booking_code}',
        'schedule' => '/schedule/{schedule_id}',
        'agency' => '/agency/{slug}',
        'payment' => '/payment/{booking_code}',
        'e-ticket' => '/e-ticket/{booking_code}',
        'driver-schedule' => '/driver/schedule/{schedule_id}',
        'settlement' => '/settlement/{settlement_id}',
        'wallet' => '/wallet',
        'withdrawal' => '/withdrawal/{withdrawal_id}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Android Configuration
    |--------------------------------------------------------------------------
    */
    'android' => [
        'package_name' => 'id.gomad.app',
        'sha256_cert_fingerprints' => [
            // Development
            'DE:AD:BE:EF:...',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | iOS Configuration
    |--------------------------------------------------------------------------
    */
    'ios' => [
        'app_store_id' => '123456789',
        'bundle_id' => 'id.gomad.app',
        'team_id' => 'TEAM123456',
    ],

    /*
    |--------------------------------------------------------------------------
    | Universal Link Configuration
    |--------------------------------------------------------------------------
    */
    'universal_links' => [
        'enabled' => true,
        'domains' => [
            'gomad.id',
            'app.gomad.id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | URL Resolver
    |--------------------------------------------------------------------------
    */
    'resolver' => [
        'api_endpoint' => '/api/v1/deeplink/resolve',
        'fallback_url' => env('APP_URL', 'http://web.gomad.test'),
    ],
];

// End of file