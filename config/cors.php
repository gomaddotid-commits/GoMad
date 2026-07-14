<?php
// File: config/cors.php
// Deskripsi: Konfigurasi CORS untuk GoMad

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        'http://gomad.test',
        'http://web.gomad.test',
        'http://api.gomad.test',
        'http://localhost:8000',
        'http://localhost:8001',
        'http://localhost:3000',
        'capacitor://localhost',
        'https://gomad.id',
        'https://web.gomad.id',
        'https://api.gomad.id',
        'https://gomad.cloud',
        'https://www.gomad.cloud',
        'http://www.gomad.cloud',
    ],

    'allowed_origins_patterns' => [
        '/^http:\/\/localhost:[0-9]+$/',
        '/^http:\/\/192\.168\.[0-9]+\.[0-9]+:[0-9]+$/',
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

// End of file