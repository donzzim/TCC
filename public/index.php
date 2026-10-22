<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__ . '/../vendor/autoload.php';
define('EMES_AUTOLOAD_END', microtime(true));

// Bootstrap Laravel and handle the request...
$application = require_once __DIR__ . '/../bootstrap/app.php';
define('EMES_APP_BOOTSTRAP_END', microtime(true));

$application->handleRequest(Request::capture());
