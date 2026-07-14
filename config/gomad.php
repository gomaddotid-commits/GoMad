<?php
// File: config/gomad.php
// Deskripsi: Konfigurasi utama aplikasi GoMad

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name & Branding
    |--------------------------------------------------------------------------
    */
    'name' => env('APP_NAME', 'GoMad'),
    'tagline' => 'Mobilitas orèng Madhurâ',
    'description' => 'Platform booking tiket travel online antar kota. Door-to-door service.',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    */
    'api_url' => env('API_URL', 'http://api.gomad.test'),
    'web_url' => env('APP_URL', 'http://web.gomad.test'),
    'landing_url' => env('LANDING_URL', 'http://gomad.test'),

    /*
    |--------------------------------------------------------------------------
    | Booking Configuration
    |--------------------------------------------------------------------------
    */
    'booking_code_prefix' => 'GM',
    'payment_timeout_minutes' => 30,
    'schedule_min_days_before' => 30,

    /*
    |--------------------------------------------------------------------------
    | Overload Rules
    |--------------------------------------------------------------------------
    */
    'overload_rules' => [
        'economy' => [
            'max_overload' => 2,
            'max_total' => 10,
        ],
        'premium' => [
            'max_overload' => 0,
        ],
        'charter' => [
            'max_overload' => 0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Baggage Limits (kg per person)
    |--------------------------------------------------------------------------
    */
    'baggage_limits' => [
        'economy' => 15.00,
        'premium' => 20.00,
        'charter' => 25.00,
    ],

    /*
    |--------------------------------------------------------------------------
    | Commission Configuration
    |--------------------------------------------------------------------------
    */
    'commission_rate' => env('COMMISSION_RATE', 5),
    'warung_commission_rate' => env('WARUNG_COMMISSION_RATE', 2),

    /*
    |--------------------------------------------------------------------------
    | Withdrawal Configuration
    |--------------------------------------------------------------------------
    */
    'minimal_withdrawal' => env('MINIMAL_WITHDRAWAL', 100000),
    'withdrawal_admin_fee' => env('WITHDRAWAL_ADMIN_FEE', 5000),
    'auto_approve_limit' => env('AUTO_APPROVE_LIMIT', 5000000),

    /*
    |--------------------------------------------------------------------------
    | Payment Code Configuration (Warung GoMad)
    |--------------------------------------------------------------------------
    */
    'payment_code_prefix' => 'WM',
    'payment_code_expiry_hours' => 24,

    /*
    |--------------------------------------------------------------------------
    | Settlement Configuration
    |--------------------------------------------------------------------------
    */
    'settlement_day' => 'monday',

    /*
    |--------------------------------------------------------------------------
    | Driver Configuration
    |--------------------------------------------------------------------------
    */
    'driver_min_rating' => 3.0,

    /*
    |--------------------------------------------------------------------------
    | Support
    |--------------------------------------------------------------------------
    */
    'support_phone' => env('SUPPORT_PHONE', '081234567890'),
    'support_email' => env('SUPPORT_EMAIL', 'support@gomad.id'),

    /*
    |--------------------------------------------------------------------------
    | Midtrans Configuration
    |--------------------------------------------------------------------------
    */
    'midtrans' => [
        'server_key' => env('MIDTRANS_SERVER_KEY'),
        'client_key' => env('MIDTRANS_CLIENT_KEY'),
        'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
        'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
        'snap_url' => env('MIDTRANS_SNAP_URL', 'https://app.sandbox.midtrans.com/snap/snap.js'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Notification Configuration (Multi-Driver)
    |--------------------------------------------------------------------------
    |
    | Driver yang didukung: log, fonnte, meta, twilio
    | Bisa diubah via .env atau Admin Settings (PlatformSettings)
    |
    */
    'whatsapp' => [
        // Driver aktif: fonnte, meta, twilio, log
        'driver' => env('WHATSAPP_DRIVER', 'log'),

        // Baileys WhatsApp Service (Microservice)
        'baileys' => [
            'api_url' => env('BAILEYS_API_URL'),
            'api_key' => env('BAILEYS_API_KEY'),
        ],
        
        // Fonnte - Recommended untuk Indonesia
        // Daftar: https://fonnte.com
        'fonnte' => [
            'api_url' => env('FONNTE_API_URL', 'https://api.fonnte.com'),
            'token' => env('FONNTE_TOKEN'),
        ],
        
        // Meta WhatsApp Cloud API - Gratis 1000 percakapan/bulan
        // Setup: https://developers.facebook.com/docs/whatsapp/cloud-api
        'meta' => [
            'api_url' => env('WHATSAPP_META_API_URL', 'https://graph.facebook.com/v20.0'),
            'phone_number_id' => env('WHATSAPP_META_PHONE_NUMBER_ID'),
            'access_token' => env('WHATSAPP_META_ACCESS_TOKEN'),
        ],
        
        // Twilio - Enterprise (Legacy)
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_WHATSAPP_FROM'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (FCM)
    |--------------------------------------------------------------------------
    */
    'fcm' => [
        'server_key' => env('FCM_SERVER_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Maps
    |--------------------------------------------------------------------------
    */
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mobile App Configuration
    |--------------------------------------------------------------------------
    */
    'mobile_app' => [
        'play_store_url' => env('PLAY_STORE_URL', 'https://play.google.com/store/apps/details?id=id.gomad.app'),
        'app_store_url' => env('APP_STORE_URL', 'https://apps.apple.com/id/app/gomad/id123456789'),
        'deep_link_scheme' => 'gomad://',
        'min_android_version' => '6.0',
        'min_ios_version' => '14.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | Gallery Limits
    |--------------------------------------------------------------------------
    */
    'gallery' => [
        'max_photos' => 10,
        'max_size_kb' => 2048,
    ],

    /*
    |--------------------------------------------------------------------------
    | Review Configuration
    |--------------------------------------------------------------------------
    */
    'review' => [
        'min_rating' => 1,
        'max_rating' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (in minutes)
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => [
        'schedule_search' => 5,
        'agency_profile' => 60,
        'platform_settings' => 60,
        'route_list' => 120,
        'city_list' => 1440,
    ],
];

// End of file