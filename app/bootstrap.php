<?php
declare(strict_types=1);

/**
 * Bootstrap application
 */

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\App;
use App\Core\Router;
use App\Core\Config;

/**
 * Load env
 */
Config::load(dirname(__DIR__) . '/.env');

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