<?php
declare (strict_types = 1);

/**
 * Bootstrap application
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Core\Config;
use App\Core\Router;
use App\Core\Navigation;

/**
 * Load env
 */
Config::load(dirname(__DIR__) . '/.env');

/**
 * Secure session
 */
$isHttps = (
    (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) === '443')
);

session_name('TPSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * App config
 */
$appConfig = require dirname(__DIR__) . '/config/app.php';

/**
 * Error handling
 */
if ($appConfig['debug']) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

/**
 * Router
 */
$router = new Router();

/**
 * Routes
 */
require __DIR__ . '/Routes/web.php';

/**
 * Run
 */
$app = new App($router);
$app->run();