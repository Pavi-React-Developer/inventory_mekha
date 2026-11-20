<?php
// public/index.php - Front Controller

declare(strict_types=1);

// Load Autoloader
require_once __DIR__ . '/../app/core/Autoloader.php';

use App\Core\Auth;
use App\Core\Router;
use App\Core\Env;

// Load environment variables from .env if present
Env::load(dirname(__DIR__) . '/.env');

// Configure error reporting based on APP_DEBUG
$debug = filter_var($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?? false, FILTER_VALIDATE_BOOLEAN);
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Set default timezone
$appConfig = require __DIR__ . '/../config/app.php';
date_default_timezone_set($appConfig['timezone'] ?? 'Asia/Kolkata');

// Start secure session
Auth::startSession();

// Initialize Router & Load Web Routes
$router = new Router();
require_once __DIR__ . '/../app/routes.php';

// Dispatch current request
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($requestUri, $requestMethod);
