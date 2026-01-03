<?php

use App\Core\Config;

return [
    'name'   => Config::get('APP_NAME', 'Traiteur'),
    'env'    => Config::get('APP_ENV', 'prod'),
    'debug'  => Config::get('APP_DEBUG', false),
    'url'    => Config::get('APP_URL'),
];