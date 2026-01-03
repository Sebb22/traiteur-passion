<?php

use App\Core\Config;

return [
    'host'    => Config::get('DB_HOST'),
    'name'    => Config::get('DB_NAME'),
    'user'    => Config::get('DB_USER'),
    'pass'    => Config::get('DB_PASS'),
    'charset' => Config::get('DB_CHARSET', 'utf8mb4'),
];