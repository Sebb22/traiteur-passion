<?php
declare(strict_types=1);

use App\Core\Router;

/** @var Router $router */
$router->get('/', 'HomeController@index');
$router->get('/menu', 'HomeController@menu');
$router->get('/contact', 'HomeController@contact');
$router->get('/devis', 'QuoteController@show');