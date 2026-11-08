<?php

use App\Core\Config;

return [
    'name'     => Config::get('APP_NAME', 'Traiteur'),
    'env'      => Config::get('APP_ENV', 'prod'),
    'debug'    => filter_var(
        Config::get('APP_DEBUG', false),
        FILTER_VALIDATE_BOOLEAN
    ),
    'url'      => Config::get('APP_URL'),
    'timezone' => Config::get('APP_TIMEZONE', 'Europe/Paris'),
];