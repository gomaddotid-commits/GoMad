<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        // ✅ HANYA domain production yang diizinkan
        env('APP_URL', 'https://web.gomad.id'),
        env('API_URL', 'https://api.gomad.id'),
        env('LANDING_URL', 'https://gomad.id'),
        'capacitor://localhost', // Untuk mobile app
    ],

    'allowed_origins_patterns' => [
        // ✅ Hanya pattern yang diperlukan
        '/^https:\/\/.*\.gomad\.id$/',
        '/^capacitor:\/\/localhost$/',
    ],

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'X-CSRF-TOKEN',
        'Accept',
        'Origin',
        'X-Api-Key',
    ],

    'exposed_headers' => [
        'Cache-Control',
        'Content-Language',
        'Content-Type',
        'Expires',
        'Last-Modified',
        'Pragma',
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
    ],

    'max_age' => 86400,

    'supports_credentials' => true,
];