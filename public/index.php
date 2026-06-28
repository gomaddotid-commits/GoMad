<?php
// File: public/index.php
// Deskripsi: Application entry point dengan domain detection untuk API dan Web mode

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
$app = require_once __DIR__.'/../bootstrap/app.php';

// Domain Detection
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$port = $_SERVER['SERVER_PORT'] ?? '80';
$isApi = str_starts_with($host, 'api.') || $port === '8001';

if ($isApi) {
    // API Mode
    $_ENV['SESSION_DRIVER'] = 'array';
    $_ENV['SESSION_LIFETIME'] = '120';
    
    // Force JSON response untuk API
    if (!isset($_SERVER['HTTP_ACCEPT']) || !str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
    }
}

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);